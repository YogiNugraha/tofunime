<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Anime;
use App\Services\Cache\TofunimeCacheService;
use Illuminate\Support\Facades\Cache;

class AnimeObserver
{
    /**
     * Handle the Anime "saved" event (created or updated).
     */
    public function saved(Anime $anime): void
    {
        $this->invalidateCache($anime);
    }

    /**
     * Handle the Anime "deleted" event.
     */
    public function deleted(Anime $anime): void
    {
        $this->invalidateCache($anime);
    }

    /**
     * Invalidate caches affected by anime changes.
     */
    protected function invalidateCache(Anime $anime): void
    {
        app(TofunimeCacheService::class)->flushHomeAndScheduleCache();

        if ($anime->id) {
            Cache::forget("tofunime_anime_{$anime->id}");
            Cache::forget("tofunime_anime_episodes_{$anime->id}");
        }
    }
}
