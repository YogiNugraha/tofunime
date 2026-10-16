<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Episode;
use App\Services\Cache\TofunimeCacheService;
use Illuminate\Support\Facades\Cache;

class EpisodeObserver
{
    /**
     * Handle the Episode "created" event.
     */
    public function created(Episode $episode): void
    {
        $this->updateAnimeLatestEpisode($episode);
        $this->invalidateCache($episode);
    }

    /**
     * Handle the Episode "updated" event.
     */
    public function updated(Episode $episode): void
    {
        $this->invalidateCache($episode);
    }

    /**
     * Handle the Episode "deleted" event.
     */
    public function deleted(Episode $episode): void
    {
        $anime = $episode->anime;

        if ($anime) {
            $latest = $anime->episodes()->latest('aired_at')->first();
            $anime->update([
                'latest_episode_at' => $latest?->aired_at ?? $latest?->created_at,
            ]);
        }

        $this->invalidateCache($episode);
    }

    /**
     * Update the anime latest_episode_at timestamp.
     */
    protected function updateAnimeLatestEpisode(Episode $episode): void
    {
        $anime = $episode->anime;

        if ($anime) {
            $anime->update([
                'latest_episode_at' => $episode->aired_at ?? now(),
            ]);
        }
    }

    /**
     * Invalidate caches affected by episode changes.
     */
    protected function invalidateCache(Episode $episode): void
    {
        app(TofunimeCacheService::class)->flushHomeAndScheduleCache();

        if ($episode->anime_id) {
            Cache::forget("tofunime_anime_{$episode->anime_id}");
            Cache::forget("tofunime_anime_episodes_{$episode->anime_id}");
        }
    }
}
