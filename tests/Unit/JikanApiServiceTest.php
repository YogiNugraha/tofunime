<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Models\Anime;
use App\Services\Jikan\JikanApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JikanApiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_anime_returns_first_matching_result(): void
    {
        Http::fake([
            'api.jikan.moe/v4/anime?*' => Http::response([
                'data' => [
                    [
                        'mal_id' => 51179,
                        'title' => 'Mushoku Tensei II: Isekai Ittara Honki Dasu',
                        'score' => 8.72,
                    ],
                ],
            ], 200),
        ]);

        $service = new JikanApiService;
        $result = $service->searchAnime('Mushoku Tensei');

        $this->assertNotNull($result);
        $this->assertEquals(51179, $result['mal_id']);
        $this->assertEquals('Mushoku Tensei II: Isekai Ittara Honki Dasu', $result['title']);
    }

    public function test_sync_jikan_data_maps_all_metadata_and_taxonomies_accurately(): void
    {
        $anime = Anime::create([
            'title' => 'Tensei shitara Ken Deshita Season 2',
            'slug' => 'tensei-shitara-ken-deshita-season-2',
            'synopsis' => 'Sinopsis asli bahasa Indonesia dari Otakudesu.',
        ]);

        $jikanData = [
            'mal_id' => 54321,
            'title' => 'Tensei shitara Ken Deshita 2nd Season',
            'title_english' => 'Reincarnated as a Sword Season 2',
            'title_japanese' => '転生したら剣でした 2',
            'title_synonyms' => ['Fran and Teacher S2'],
            'type' => 'TV',
            'status' => 'Currently Airing',
            'episodes' => 12,
            'duration' => '23 min per ep',
            'year' => 2026,
            'season' => 'fall',
            'score' => 7.85,
            'scored_by' => 45000,
            'rank' => 800,
            'rating' => 'PG-13',
            'source' => 'Light novel',
            'background' => 'Based on light novel by Yuu Tanaka.',
            'synopsis' => 'English synopsis from MyAnimeList.',
            'images' => [
                'webp' => [
                    'large_image_url' => 'https://cdn.myanimelist.net/images/anime/sword.webp',
                ],
            ],
            'trailer' => [
                'youtube_id' => 'abc123xyz',
            ],
            'aired' => [
                'string' => 'Oct 2026 to ?',
            ],
            'studios' => [
                ['mal_id' => 300, 'name' => 'C2C'],
            ],
            'producers' => [
                ['mal_id' => 100, 'name' => 'NBCUniversal Entertainment Japan'],
            ],
            'genres' => [
                ['mal_id' => 1, 'name' => 'Action'],
                ['mal_id' => 10, 'name' => 'Fantasy'],
            ],
            'themes' => [
                ['mal_id' => 62, 'name' => 'Isekai'],
                ['mal_id' => 72, 'name' => 'Reincarnation'],
            ],
            'demographics' => [
                ['mal_id' => 27, 'name' => 'Shounen'],
            ],
        ];

        $service = new JikanApiService;
        $updatedAnime = $service->syncJikanDataToAnime($anime, $jikanData);

        // Assert basic fields
        $this->assertEquals(54321, $updatedAnime->mal_id);
        $this->assertEquals('Reincarnated as a Sword Season 2', $updatedAnime->english_title);
        $this->assertEquals(AnimeType::TV, $updatedAnime->type);
        $this->assertEquals(AnimeStatus::Ongoing, $updatedAnime->status);
        $this->assertEquals(7.85, (float) $updatedAnime->rating_score);
        $this->assertEquals('abc123xyz', $updatedAnime->trailer_youtube_id);
        $this->assertEquals('https://cdn.myanimelist.net/images/anime/sword.webp', $updatedAnime->poster_url);

        // Assert Indonesian synopsis retained
        $this->assertEquals('Sinopsis asli bahasa Indonesia dari Otakudesu.', $updatedAnime->synopsis);

        // Assert Studio
        $this->assertNotNull($updatedAnime->studio);
        $this->assertEquals('C2C', $updatedAnime->studio->name);

        // Assert Season
        $this->assertNotNull($updatedAnime->season);
        $this->assertEquals('Fall 2026', $updatedAnime->season->name);

        // Assert Taxonomies
        $this->assertCount(5, $updatedAnime->taxonomies);
        $this->assertCount(2, $updatedAnime->genres);
        $this->assertCount(2, $updatedAnime->themes);
        $this->assertCount(1, $updatedAnime->demographics);
    }
}
