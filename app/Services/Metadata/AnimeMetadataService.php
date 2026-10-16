<?php

declare(strict_types=1);

namespace App\Services\Metadata;

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
use Illuminate\Support\Str;

class AnimeMetadataService
{
    /**
     * Fetch complete anime metadata by MAL ID, MAL URL, or title search.
     *
     * @return array<string, mixed>
     */
    public function fetchMetadata(string $input): array
    {
        $input = trim($input);
        if (empty($input)) {
            throw new Exception('Input URL, ID, atau judul tidak boleh kosong.');
        }

        $malId = null;
        $searchQuery = null;

        if (is_numeric($input)) {
            $malId = (int) $input;
        } elseif (preg_match('/myanimelist\.net\/anime\/(\d+)/i', $input, $matches)) {
            $malId = (int) $matches[1];
        } elseif (preg_match('/anilist\.co\/anime\/(\d+)/i', $input, $matches)) {
            $searchQuery = null;

            // Handle AniList ID
            return $this->fetchFromAniList(null, (int) $matches[1], null);
        } else {
            $searchQuery = $input;
        }

        return $this->fetchFromAniList($malId, null, $searchQuery);
    }

    /**
     * Query AniList GraphQL API (ultra-reliable, zero-rate-limit).
     *
     * @return array<string, mixed>
     */
    protected function fetchFromAniList(?int $malId, ?int $aniListId, ?string $search): array
    {
        $graphql = <<<'GRAPHQL'
query ($idMal: Int, $id: Int, $search: String) {
  Media(idMal: $idMal, id: $id, search: $search, type: ANIME) {
    id
    idMal
    title {
      romaji
      english
      native
    }
    synonyms
    description(asHtml: false)
    season
    seasonYear
    format
    status
    episodes
    duration
    genres
    tags {
      name
      category
      isMediaSpoiler
    }
    studios(isMain: true) {
      nodes {
        id
        name
        isAnimationStudio
      }
    }
    staff(sort: [RELEVANCE], perPage: 25) {
      edges {
        role
        node {
          id
          name {
            full
          }
          image {
            medium
          }
        }
      }
    }
    coverImage {
      extraLarge
      large
    }
    bannerImage
    trailer {
      id
      site
    }
    averageScore
    meanScore
    rankings {
      rank
      type
      allTime
    }
    startDate {
      year
      month
      day
    }
    endDate {
      year
      month
      day
    }
    source
  }
}
GRAPHQL;

        $variables = [];
        if ($malId !== null) {
            $variables['idMal'] = $malId;
        } elseif ($aniListId !== null) {
            $variables['id'] = $aniListId;
        } else {
            $variables['search'] = $search;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Tofunime/1.0 (+https://tofunime.test)',
        ])->timeout(15)->post('https://graphql.anilist.co', [
            'query' => $graphql,
            'variables' => $variables,
        ]);

        if (! $response->successful()) {
            throw new Exception('Gagal menghubungi AniList/MyAnimeList API: HTTP '.$response->status());
        }

        $json = $response->json();
        $media = $json['data']['Media'] ?? null;

        if (! $media) {
            throw new Exception('Data anime tidak ditemukan untuk query/ID tersebut.');
        }

        return $this->formatMediaPayload($media);
    }

    /**
     * Format raw AniList media object into normalized Tofunime metadata structure.
     *
     * @param  array<string, mixed>  $media
     * @return array<string, mixed>
     */
    protected function formatMediaPayload(array $media): array
    {
        $title = $media['title']['romaji'] ?? $media['title']['english'] ?? $media['title']['native'];
        $englishTitle = $media['title']['english'] ?? null;
        $japaneseTitle = $media['title']['native'] ?? null;
        $synonyms = $media['synonyms'] ?? [];

        // Clean description (remove HTML and spoiler tags if any)
        $description = $media['description'] ?? '';
        $description = str_replace(['<br>', '<br/>', '<br />', '<p>', '</p>'], ["\n", "\n", "\n", '', "\n\n"], $description);
        $description = strip_tags($description);
        $description = trim(html_entity_decode($description));

        // Format dates & aired string
        $airedString = null;
        if (! empty($media['startDate']['year'])) {
            $airedString = sprintf('%04d-%02d-%02d', $media['startDate']['year'], $media['startDate']['month'] ?? 1, $media['startDate']['day'] ?? 1);
            if (! empty($media['endDate']['year'])) {
                $airedString .= sprintf(' s/d %04d-%02d-%02d', $media['endDate']['year'], $media['endDate']['month'] ?? 1, $media['endDate']['day'] ?? 1);
            }
        }

        // Season
        $seasonQuarter = ! empty($media['season']) ? ucfirst(strtolower($media['season'])) : null;
        $seasonYear = $media['seasonYear'] ?? $media['startDate']['year'] ?? null;
        $seasonName = ($seasonQuarter && $seasonYear) ? "{$seasonQuarter} {$seasonYear}" : null;

        // Type mapping
        $typeMap = [
            'TV' => AnimeType::TV,
            'TV_SHORT' => AnimeType::TV,
            'MOVIE' => AnimeType::Movie,
            'OVA' => AnimeType::OVA,
            'ONA' => AnimeType::ONA,
            'SPECIAL' => AnimeType::Special,
        ];
        $mappedType = $typeMap[$media['format'] ?? 'TV'] ?? AnimeType::TV;

        // Status mapping
        $statusMap = [
            'FINISHED' => AnimeStatus::Completed,
            'RELEASING' => AnimeStatus::Ongoing,
            'NOT_YET_RELEASED' => AnimeStatus::Upcoming,
            'CANCELLED' => AnimeStatus::Completed,
            'HIATUS' => AnimeStatus::Ongoing,
        ];
        $mappedStatus = $statusMap[$media['status'] ?? 'FINISHED'] ?? AnimeStatus::Completed;

        // Primary studio
        $studioName = $media['studios']['nodes'][0]['name'] ?? null;

        // Staff (Filter to key roles)
        $staff = [];
        $allowedRoles = ['Original Story', 'Original Creator', 'Director', 'Series Composition', 'Character Design', 'Music'];
        foreach ($media['staff']['edges'] ?? [] as $edge) {
            $role = (string) ($edge['role'] ?? '');
            $name = (string) ($edge['node']['name']['full'] ?? '');
            if (empty($name)) {
                continue;
            }

            foreach ($allowedRoles as $allowed) {
                if (str_contains(strtolower($role), strtolower($allowed))) {
                    $staff[] = [
                        'name' => $name,
                        'role' => $allowed,
                        'image_url' => $edge['node']['image']['medium'] ?? null,
                    ];
                    break;
                }
            }
        }

        // De-duplicate staff by name + role
        $uniqueStaff = [];
        foreach ($staff as $item) {
            $key = $item['name'].'|'.$item['role'];
            if (! isset($uniqueStaff[$key])) {
                $uniqueStaff[$key] = $item;
            }
        }

        // Tags / Themes
        $themes = [];
        foreach ($media['tags'] ?? [] as $tag) {
            if (! ($tag['isMediaSpoiler'] ?? false) && ! empty($tag['name'])) {
                $themes[] = $tag['name'];
            }
        }

        // Score
        $score = null;
        if (! empty($media['averageScore'])) {
            $score = round($media['averageScore'] / 10, 2);
        }

        // Trailer
        $trailerYoutubeId = null;
        if (($media['trailer']['site'] ?? '') === 'youtube' && ! empty($media['trailer']['id'])) {
            $trailerYoutubeId = $media['trailer']['id'];
        }

        return [
            'mal_id' => $media['idMal'] ?? null,
            'title' => $title,
            'slug' => Str::slug($title),
            'english_title' => $englishTitle,
            'japanese_title' => $japaneseTitle,
            'synonyms' => $synonyms,
            'synopsis' => $description,
            'poster_url' => $media['coverImage']['extraLarge'] ?? $media['coverImage']['large'] ?? null,
            'banner_url' => $media['bannerImage'] ?? null,
            'trailer_youtube_id' => $trailerYoutubeId,
            'type' => $mappedType->value,
            'status' => $mappedStatus->value,
            'total_episodes' => $media['episodes'] ?? null,
            'duration' => ! empty($media['duration']) ? "{$media['duration']} Menit" : null,
            'release_year' => $seasonYear,
            'season_name' => $seasonName,
            'season_quarter' => $seasonQuarter,
            'studio_name' => $studioName,
            'aired_string' => $airedString,
            'rating_score' => $score,
            'scored_by' => $media['popularity'] ?? null,
            'source_material' => ! empty($media['source']) ? ucfirst(strtolower(str_replace('_', ' ', $media['source']))) : 'Original',
            'genres' => $media['genres'] ?? [],
            'themes' => array_slice($themes, 0, 10),
            'demographics' => [],
            'staff' => array_values($uniqueStaff),
        ];
    }

    /**
     * Persist or sync normalized metadata array directly to an Anime model.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncToAnime(Anime $anime, array $data): Anime
    {
        // 1. Studio
        $studioModel = null;
        if (! empty($data['studio_name'])) {
            $studioModel = Studio::firstOrCreate(
                ['slug' => Str::slug($data['studio_name'])],
                [
                    'name' => $data['studio_name'],
                    'type' => StudioType::Studio,
                ]
            );
        }

        // 2. Season
        $seasonModel = null;
        if (! empty($data['season_name']) && ! empty($data['release_year'])) {
            $quarter = ucfirst(strtolower((string) ($data['season_quarter'] ?? 'Fall')));
            $seasonModel = Season::firstOrCreate(
                ['slug' => Str::slug($data['season_name'])],
                [
                    'name' => $data['season_name'],
                    'year' => (int) $data['release_year'],
                    'quarter' => SeasonQuarter::tryFrom($quarter) ?? SeasonQuarter::Fall,
                ]
            );
        }

        // 3. Update Anime
        $updateFields = [
            'mal_id' => $data['mal_id'] ?? $anime->mal_id,
            'studio_id' => $studioModel?->id ?? $anime->studio_id,
            'season_id' => $seasonModel?->id ?? $anime->season_id,
            'title' => ! empty($data['title']) ? $data['title'] : $anime->title,
            'slug' => ! empty($data['slug']) ? $data['slug'] : $anime->slug,
            'english_title' => array_key_exists('english_title', $data) ? $data['english_title'] : $anime->english_title,
            'japanese_title' => array_key_exists('japanese_title', $data) ? $data['japanese_title'] : $anime->japanese_title,
            'synonyms' => ! empty($data['synonyms']) ? $data['synonyms'] : $anime->synonyms,
            'source_material' => array_key_exists('source_material', $data) ? $data['source_material'] : $anime->source_material,
            'poster_url' => array_key_exists('poster_url', $data) ? $data['poster_url'] : $anime->poster_url,
            'banner_url' => array_key_exists('banner_url', $data) ? $data['banner_url'] : $anime->banner_url,
            'trailer_youtube_id' => array_key_exists('trailer_youtube_id', $data) ? $data['trailer_youtube_id'] : $anime->trailer_youtube_id,
            'type' => $data['type'] instanceof AnimeType ? $data['type'] : (! empty($data['type']) ? AnimeType::tryFrom((string) $data['type']) ?? $anime->type : $anime->type),
            'status' => $data['status'] instanceof AnimeStatus ? $data['status'] : (! empty($data['status']) ? AnimeStatus::tryFrom((string) $data['status']) ?? $anime->status : $anime->status),
            'release_day' => array_key_exists('release_day', $data) ? ($data['release_day'] instanceof ReleaseDay ? $data['release_day'] : (! empty($data['release_day']) ? ReleaseDay::tryFrom((string) $data['release_day']) : null)) : $anime->release_day,
            'total_episodes' => array_key_exists('total_episodes', $data) ? $data['total_episodes'] : $anime->total_episodes,
            'duration' => array_key_exists('duration', $data) ? $data['duration'] : $anime->duration,
            'release_year' => array_key_exists('release_year', $data) ? $data['release_year'] : $anime->release_year,
            'aired_string' => array_key_exists('aired_string', $data) ? $data['aired_string'] : $anime->aired_string,
            'rating_score' => array_key_exists('rating_score', $data) ? (! empty($data['rating_score']) ? (float) $data['rating_score'] : 0.00) : $anime->rating_score,
            'scored_by' => array_key_exists('scored_by', $data) ? (! empty($data['scored_by']) ? (int) $data['scored_by'] : 0) : $anime->scored_by,
            'rank' => array_key_exists('rank', $data) ? $data['rank'] : $anime->rank,
            'age_rating' => array_key_exists('age_rating', $data) ? $data['age_rating'] : $anime->age_rating,
            'is_featured' => array_key_exists('is_featured', $data) ? (bool) $data['is_featured'] : $anime->is_featured,
            'is_recommended' => array_key_exists('is_recommended', $data) ? (bool) $data['is_recommended'] : $anime->is_recommended,
            'synopsis' => array_key_exists('synopsis', $data) ? $data['synopsis'] : $anime->synopsis,
            'background_info' => array_key_exists('background_info', $data) ? $data['background_info'] : $anime->background_info,
        ];

        $anime->update($updateFields);

        // 4. Taxonomies (Genres & Themes)
        $taxIds = [];
        foreach ($data['genres'] ?? [] as $genreName) {
            $tax = Taxonomy::firstOrCreate(
                ['slug' => Str::slug($genreName)],
                ['name' => $genreName, 'type' => TaxonomyType::Genre]
            );
            $taxIds[] = $tax->id;
        }

        foreach ($data['themes'] ?? [] as $themeName) {
            $tax = Taxonomy::firstOrCreate(
                ['slug' => Str::slug($themeName)],
                ['name' => $themeName, 'type' => TaxonomyType::Theme]
            );
            $taxIds[] = $tax->id;
        }

        if (array_key_exists('genres', $data) || array_key_exists('themes', $data)) {
            $anime->taxonomies()->sync(array_unique($taxIds));
        }

        // 5. Staff
        if (array_key_exists('staff', $data) && is_array($data['staff'])) {
            $staffSyncData = [];
            foreach ($data['staff'] as $staffItem) {
                if (empty(trim($staffItem['name'] ?? ''))) {
                    continue;
                }
                $person = Person::firstOrCreate(
                    ['slug' => Str::slug($staffItem['name'])],
                    [
                        'name' => $staffItem['name'],
                        'image_url' => $staffItem['image_url'] ?? null,
                    ]
                );
                $staffSyncData[$person->id] = ['role' => $staffItem['role'] ?? 'Staff'];
            }
            $anime->people()->sync($staffSyncData);
        }

        return $anime->fresh(['studio', 'season', 'taxonomies', 'people']);
    }
}
