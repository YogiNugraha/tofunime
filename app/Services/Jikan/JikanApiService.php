<?php

declare(strict_types=1);

namespace App\Services\Jikan;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\SeasonQuarter;
use App\Enums\StudioType;
use App\Enums\TaxonomyType;
use App\Models\Anime;
use App\Models\Person;
use App\Models\Season;
use App\Models\Studio;
use App\Models\Taxonomy;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JikanApiService
{
    protected string $baseUrl = 'https://api.jikan.moe/v4';

    /**
     * Search anime on MyAnimeList via Jikan API v4.
     *
     * @return array<string, mixed>|null
     */
    public function searchAnime(string $query): ?array
    {
        $this->throttle();

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Tofunime/1.0 (+https://tofunime.test)',
                'Accept' => 'application/json',
            ])
                ->retry(3, 1000)
                ->timeout(15)
                ->get("{$this->baseUrl}/anime", [
                    'q' => $query,
                    'limit' => 5,
                    'sfw' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('JikanApiService: searchAnime failed', [
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json('data');

            return ! empty($data) && is_array($data) ? $data[0] : null;
        } catch (Exception $e) {
            Log::error('JikanApiService: searchAnime exception', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get full anime details from Jikan API v4.
     *
     * @return array<string, mixed>
     */
    public function getFullAnimeDetails(int $malId): array
    {
        $this->throttle();

        $response = Http::withHeaders([
            'User-Agent' => 'Tofunime/1.0 (+https://tofunime.test)',
            'Accept' => 'application/json',
        ])
            ->retry(3, 1000)
            ->timeout(20)
            ->get("{$this->baseUrl}/anime/{$malId}/full");

        if (! $response->successful()) {
            throw new Exception("Jikan API error [{$response->status()}]: {$response->body()}");
        }

        /** @var array<string, mixed> $data */
        $data = $response->json('data') ?? [];

        return $data;
    }

    /**
     * Get authors, original creators, and directors from Jikan API v4 staff endpoint.
     *
     * @return list<array{mal_id: int, name: string, role: string, image_url: ?string}>
     */
    public function getAnimeAuthorsAndStaff(int $malId): array
    {
        $this->throttle();

        $response = Http::withHeaders([
            'User-Agent' => 'Tofunime/1.0 (+https://tofunime.test)',
            'Accept' => 'application/json',
        ])
            ->retry(3, 1000)
            ->timeout(20)
            ->get("{$this->baseUrl}/anime/{$malId}/staff");

        if (! $response->successful()) {
            Log::warning('JikanApiService: getAnimeAuthorsAndStaff failed', [
                'mal_id' => $malId,
                'status' => $response->status(),
            ]);

            return [];
        }

        $staffList = $response->json('data') ?? [];
        $relevantStaff = [];

        foreach ($staffList as $item) {
            $person = $item['person'] ?? [];
            $positions = $item['positions'] ?? [];

            $matchedRole = null;
            foreach ($positions as $pos) {
                if (is_string($pos)) {
                    if (str_contains($pos, 'Original Creator') || str_contains($pos, 'Author')) {
                        $matchedRole = 'Original Creator';
                        break;
                    }
                    if (str_contains($pos, 'Director')) {
                        $matchedRole = 'Director';
                        break;
                    }
                    if (str_contains($pos, 'Script') || str_contains($pos, 'Series Composition')) {
                        $matchedRole = 'Script';
                        break;
                    }
                    if (str_contains($pos, 'Character Design')) {
                        $matchedRole = 'Character Design';
                        break;
                    }
                }
            }

            if ($matchedRole !== null && ! empty($person['mal_id']) && ! empty($person['name'])) {
                $relevantStaff[] = [
                    'mal_id' => (int) $person['mal_id'],
                    'name' => (string) $person['name'],
                    'role' => $matchedRole,
                    'image_url' => $person['images']['jpg']['image_url'] ?? null,
                ];
            }
        }

        return $relevantStaff;
    }

    /**
     * Map full Jikan details array and sync with local models.
     *
     * @param  array<string, mixed>  $jikanData
     */
    public function syncJikanDataToAnime(Anime $anime, array $jikanData): Anime
    {
        // 1. Studio & Producers
        $studioModel = null;
        if (! empty($jikanData['studios']) && is_array($jikanData['studios'])) {
            $primaryStudio = $jikanData['studios'][0];
            $studioModel = Studio::updateOrCreate(
                ['mal_id' => $primaryStudio['mal_id']],
                [
                    'name' => $primaryStudio['name'],
                    'slug' => Str::slug($primaryStudio['name']),
                    'type' => StudioType::Studio,
                ]
            );
        }

        if (! empty($jikanData['producers']) && is_array($jikanData['producers'])) {
            foreach ($jikanData['producers'] as $producer) {
                Studio::updateOrCreate(
                    ['mal_id' => $producer['mal_id']],
                    [
                        'name' => $producer['name'],
                        'slug' => Str::slug($producer['name']),
                        'type' => StudioType::Producer,
                    ]
                );
            }
        }

        // 2. Season
        $seasonModel = null;
        if (! empty($jikanData['season']) && ! empty($jikanData['year'])) {
            $quarter = ucfirst(strtolower((string) $jikanData['season']));
            $year = (int) $jikanData['year'];
            $seasonName = "{$quarter} {$year}";

            $seasonModel = Season::firstOrCreate(
                ['slug' => Str::slug($seasonName)],
                [
                    'name' => $seasonName,
                    'year' => $year,
                    'quarter' => SeasonQuarter::tryFrom($quarter) ?? SeasonQuarter::Fall,
                ]
            );
        }

        // 3. Map Type & Status Enum
        $mappedType = AnimeType::TV;
        if (! empty($jikanData['type'])) {
            $typeStr = strtoupper((string) $jikanData['type']);
            $mappedType = AnimeType::tryFrom($typeStr) ?? AnimeType::TV;
        }

        $mappedStatus = AnimeStatus::Ongoing;
        if (! empty($jikanData['status'])) {
            $statusStr = (string) $jikanData['status'];
            if (str_contains($statusStr, 'Finished')) {
                $mappedStatus = AnimeStatus::Completed;
            } elseif (str_contains($statusStr, 'Not yet')) {
                $mappedStatus = AnimeStatus::Upcoming;
            } else {
                $mappedStatus = AnimeStatus::Ongoing;
            }
        }

        // 4. Update Anime Model
        $posterUrl = $jikanData['images']['webp']['large_image_url']
            ?? $jikanData['images']['jpg']['large_image_url']
            ?? $anime->poster_url;

        $trailerId = $jikanData['trailer']['youtube_id'] ?? $anime->trailer_youtube_id;

        $anime->update([
            'mal_id' => $jikanData['mal_id'] ?? $anime->mal_id,
            'studio_id' => $studioModel?->id ?? $anime->studio_id,
            'season_id' => $seasonModel?->id ?? $anime->season_id,
            'english_title' => $jikanData['title_english'] ?? $anime->english_title,
            'japanese_title' => $jikanData['title_japanese'] ?? $anime->japanese_title,
            'synonyms' => $jikanData['title_synonyms'] ?? $anime->synonyms,
            'source_material' => $jikanData['source'] ?? $anime->source_material,
            'poster_url' => $posterUrl,
            'banner_url' => $posterUrl,
            'trailer_youtube_id' => $trailerId,
            'type' => $mappedType,
            'status' => $mappedStatus,
            'total_episodes' => $jikanData['episodes'] ?? $anime->total_episodes,
            'duration' => $jikanData['duration'] ?? $anime->duration,
            'release_year' => $jikanData['year'] ?? $anime->release_year,
            'aired_string' => $jikanData['aired']['string'] ?? $anime->aired_string,
            'rating_score' => $jikanData['score'] ?? $anime->rating_score,
            'scored_by' => $jikanData['scored_by'] ?? $anime->scored_by,
            'rank' => $jikanData['rank'] ?? $anime->rank,
            'age_rating' => $jikanData['rating'] ?? $anime->age_rating,
            'background_info' => $jikanData['background'] ?? $anime->background_info,
            // Retain existing Indonesian synopsis if present, otherwise fallback to Jikan synopsis
            'synopsis' => ! empty($anime->synopsis) ? $anime->synopsis : ($jikanData['synopsis'] ?? null),
        ]);

        // 5. Taxonomies (Genres, Themes, Demographics)
        $taxonomyIds = [];

        // Genres
        if (! empty($jikanData['genres'])) {
            foreach ($jikanData['genres'] as $genre) {
                $tax = Taxonomy::firstOrCreate(
                    ['slug' => Str::slug($genre['name'])],
                    [
                        'mal_id' => $genre['mal_id'] ?? null,
                        'name' => $genre['name'],
                        'type' => TaxonomyType::Genre,
                    ]
                );
                $taxonomyIds[] = $tax->id;
            }
        }

        // Themes (Isekai, Reincarnation, etc)
        if (! empty($jikanData['themes'])) {
            foreach ($jikanData['themes'] as $theme) {
                $tax = Taxonomy::firstOrCreate(
                    ['slug' => Str::slug($theme['name'])],
                    [
                        'mal_id' => $theme['mal_id'] ?? null,
                        'name' => $theme['name'],
                        'type' => TaxonomyType::Theme,
                    ]
                );
                $taxonomyIds[] = $tax->id;
            }
        }

        // Demographics (Shounen, Seinen, etc)
        if (! empty($jikanData['demographics'])) {
            foreach ($jikanData['demographics'] as $demo) {
                $tax = Taxonomy::firstOrCreate(
                    ['slug' => Str::slug($demo['name'])],
                    [
                        'mal_id' => $demo['mal_id'] ?? null,
                        'name' => $demo['name'],
                        'type' => TaxonomyType::Demographic,
                    ]
                );
                $taxonomyIds[] = $tax->id;
            }
        }

        if (! empty($taxonomyIds)) {
            $anime->taxonomies()->sync(array_unique($taxonomyIds));
        }

        return $anime;
    }

    /**
     * Sync author & staff list to anime_person pivot table.
     *
     * @param  list<array{mal_id: int, name: string, role: string, image_url: ?string}>  $staffList
     */
    public function syncStaffToAnime(Anime $anime, array $staffList): void
    {
        foreach ($staffList as $staff) {
            $person = Person::updateOrCreate(
                ['mal_id' => $staff['mal_id']],
                [
                    'name' => $staff['name'],
                    'slug' => Str::slug($staff['name']),
                    'image_url' => $staff['image_url'],
                ]
            );

            $exists = $anime->people()
                ->where('people.id', $person->id)
                ->wherePivot('role', $staff['role'])
                ->exists();

            if (! $exists) {
                $anime->people()->attach($person->id, ['role' => $staff['role']]);
            }
        }
    }

    /**
     * Rate limit throttling: 0.4 seconds pause (max 3 req/sec).
     */
    protected function throttle(): void
    {
        usleep(400000); // 400ms
    }
}
