<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\StudioType;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Studio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_episode_creation_updates_anime_latest_episode_at(): void
    {
        $studio = Studio::create([
            'name' => 'Studio Bind',
            'slug' => 'studio-bind',
            'type' => StudioType::Studio,
            'is_featured' => true,
        ]);

        $anime = Anime::create([
            'title' => 'Mushoku Tensei: Isekai Ittara Honki Dasu',
            'slug' => 'mushoku-tensei-isekai-ittara-honki-dasu',
            'studio_id' => $studio->id,
            'type' => AnimeType::TV,
            'status' => AnimeStatus::Ongoing,
            'rating_score' => 8.75,
        ]);

        $this->assertNull($anime->latest_episode_at);

        $now = now();
        $episode = Episode::create([
            'anime_id' => $anime->id,
            'episode_number' => '1',
            'episode_order' => 1.0,
            'title' => 'Episode 1',
            'slug' => 'mushoku-tensei-episode-1-sub-indo',
            'aired_at' => $now,
        ]);

        $anime->refresh();

        $this->assertNotNull($anime->latest_episode_at);
        $this->assertEquals($now->toDateTimeString(), $anime->latest_episode_at->toDateTimeString());
    }
}
