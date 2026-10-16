<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\AnimeEditor;
use App\Livewire\Admin\AnimeManager;
use App\Models\Anime;
use App\Models\User;
use App\Services\Metadata\AnimeMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AnimeEditorAndMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function mockAniListSuccess(): void
    {
        Http::fake([
            'https://graphql.anilist.co' => Http::response([
                'data' => [
                    'Media' => [
                        'id' => 154587,
                        'idMal' => 52991,
                        'title' => [
                            'romaji' => 'Sousou no Frieren',
                            'english' => "Frieren: Beyond Journey's End",
                            'native' => '葬送のフリーレン',
                        ],
                        'synonyms' => ['Frieren', 'SnF'],
                        'description' => 'Petualangan elf penyihir setelah pahlawan tiada.',
                        'season' => 'FALL',
                        'seasonYear' => 2023,
                        'format' => 'TV',
                        'status' => 'FINISHED',
                        'episodes' => 28,
                        'duration' => 24,
                        'genres' => ['Adventure', 'Drama', 'Fantasy'],
                        'tags' => [
                            ['name' => 'Magic', 'isMediaSpoiler' => false],
                            ['name' => 'Elf', 'isMediaSpoiler' => false],
                        ],
                        'studios' => [
                            'nodes' => [
                                ['id' => 11, 'name' => 'Madhouse', 'isAnimationStudio' => true],
                            ],
                        ],
                        'staff' => [
                            'edges' => [
                                [
                                    'role' => 'Original Story',
                                    'node' => ['id' => 1, 'name' => ['full' => 'Kanehito Yamada']],
                                ],
                                [
                                    'role' => 'Director',
                                    'node' => ['id' => 2, 'name' => ['full' => 'Keiichirou Saitou']],
                                ],
                            ],
                        ],
                        'coverImage' => [
                            'extraLarge' => 'https://example.com/frieren-large.jpg',
                            'large' => 'https://example.com/frieren.jpg',
                        ],
                        'bannerImage' => 'https://example.com/frieren-banner.jpg',
                        'trailer' => [
                            'id' => 'tR8YH0G67Rk',
                            'site' => 'youtube',
                        ],
                        'averageScore' => 91,
                        'popularity' => 450000,
                        'source' => 'MANGA',
                        'startDate' => ['year' => 2023, 'month' => 9, 'day' => 29],
                        'endDate' => ['year' => 2024, 'month' => 3, 'day' => 22],
                    ],
                ],
            ]),
        ]);
    }

    public function test_anime_metadata_service_fetches_and_formats_correctly(): void
    {
        $this->mockAniListSuccess();

        $service = app(AnimeMetadataService::class);
        $data = $service->fetchMetadata('https://myanimelist.net/anime/52991/Sousou_no_Frieren');

        $this->assertEquals(52991, $data['mal_id']);
        $this->assertEquals('Sousou no Frieren', $data['title']);
        $this->assertEquals("Frieren: Beyond Journey's End", $data['english_title']);
        $this->assertEquals('Madhouse', $data['studio_name']);
        $this->assertEquals('Fall 2023', $data['season_name']);
        $this->assertEquals(9.10, $data['rating_score']);
        $this->assertContains('Fantasy', $data['genres']);
        $this->assertContains('Magic', $data['themes']);
        $this->assertEquals('tR8YH0G67Rk', $data['trailer_youtube_id']);
    }

    public function test_anime_editor_autofills_from_mal_and_saves_anime(): void
    {
        $this->mockAniListSuccess();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AnimeEditor::class)
            ->set('malQueryOrUrl', '52991')
            ->call('fetchFromMal')
            ->assertSet('title', 'Sousou no Frieren')
            ->assertSet('english_title', "Frieren: Beyond Journey's End")
            ->assertSet('studio_name', 'Madhouse')
            ->assertSet('season_name', 'Fall 2023')
            ->assertSet('total_episodes', 28)
            ->assertSet('rating_score', '9.10')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('animes', [
            'slug' => 'sousou-no-frieren',
            'english_title' => "Frieren: Beyond Journey's End",
            'mal_id' => 52991,
        ]);

        $created = Anime::where('slug', 'sousou-no-frieren')->firstOrFail();
        $this->assertEquals('Madhouse', $created->studio->name);
        $this->assertEquals('Fall 2023', $created->season->name);
        $this->assertContains('Fantasy', $created->genres->pluck('name')->toArray());
        $this->assertContains('Kanehito Yamada', $created->people->pluck('name')->toArray());
    }

    public function test_anime_manager_quick_sync_mal(): void
    {
        $this->mockAniListSuccess();

        $user = User::factory()->create();

        $anime = Anime::create([
            'title' => 'Frieren Incomplete',
            'slug' => 'frieren-incomplete',
            'mal_id' => 52991,
            'type' => 'TV',
            'status' => 'Ongoing',
        ]);

        Livewire::actingAs($user)
            ->test(AnimeManager::class)
            ->call('quickSyncMal', $anime->id)
            ->assertSee('Sukses! Metadata anime');

        $anime->refresh();
        $this->assertEquals('Madhouse', $anime->studio->name);
        $this->assertEquals('Fall 2023', $anime->season->name);
        $this->assertGreaterThan(0, $anime->genres()->count());
    }

    public function test_anime_editor_updates_existing_anime(): void
    {
        $user = User::factory()->create();

        $anime = Anime::create([
            'title' => 'Initial Title',
            'slug' => 'initial-title',
            'type' => 'TV',
            'status' => 'Ongoing',
            'duration' => '20 min',
        ]);

        Livewire::actingAs($user)
            ->test(AnimeEditor::class, ['anime' => $anime])
            ->set('title', 'Updated Title')
            ->set('duration', '25 min')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saveType', 'success')
            ->assertSee("Anime 'Updated Title' berhasil diperbarui!");

        $anime->refresh();
        $this->assertEquals('Updated Title', $anime->title);
        $this->assertEquals('25 min', $anime->duration);
    }
}
