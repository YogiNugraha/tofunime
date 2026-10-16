<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Enums\AnimeStatus;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Studio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TofunimeCacheService
{
    public const TTL_SHORT = 300;     // 5 minutes

    public const TTL_MEDIUM = 3600;   // 1 hour

    public const TTL_LONG = 86400;    // 1 day

    /**
     * Get Featured Hero anime with relationships.
     * Caches ID to prevent PHP object serialization (__PHP_Incomplete_Class) issues.
     */
    public function getFeaturedHero(): ?Anime
    {
        $heroId = Cache::remember('tofunime_featured_hero_id', self::TTL_MEDIUM, function () {
            return Anime::where('is_featured', true)->value('id') ?? Anime::value('id');
        });

        if ($heroId) {
            $anime = Anime::with(['studio', 'season', 'genres', 'themes'])->find($heroId);
            if ($anime) {
                return $anime;
            }
        }

        return Anime::with(['studio', 'season', 'genres', 'themes'])->first();
    }

    /**
     * Get recent updated episodes for homepage.
     *
     * @return Collection<int, Episode>
     */
    public function getRecentEpisodes(int $limit = 8): Collection
    {
        $episodeIds = Cache::remember("tofunime_recent_episode_ids_{$limit}", self::TTL_SHORT, function () use ($limit) {
            return Episode::latest('aired_at')->take($limit)->pluck('id')->toArray();
        });

        if (! empty($episodeIds)) {
            $episodes = Episode::with('anime')->whereIn('id', $episodeIds)->get();

            /** @var Collection<int, Episode> $ordered */
            $ordered = collect($episodeIds)
                ->map(fn ($id) => $episodes->firstWhere('id', $id))
                ->filter()
                ->values();

            return $ordered;
        }

        return Episode::with('anime')->latest('aired_at')->take($limit)->get();
    }

    /**
     * Get iconic featured studios.
     *
     * @return Collection<int, Studio>
     */
    public function getIconicStudios(): Collection
    {
        $studioIds = Cache::remember('tofunime_iconic_studio_ids', self::TTL_LONG, function () {
            return Studio::where('is_featured', true)->take(4)->pluck('id')->toArray();
        });

        if (! empty($studioIds)) {
            return Studio::whereIn('id', $studioIds)->get();
        }

        return Studio::where('is_featured', true)->take(4)->get();
    }

    /**
     * Get weekly release schedule grouped by release day.
     *
     * @return array<string, Collection<int, Anime>>
     */
    public function getWeeklySchedule(): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Random'];

        $scheduleMap = Cache::remember('tofunime_weekly_schedule_ids', self::TTL_MEDIUM, function () use ($days) {
            $animes = Anime::where('status', AnimeStatus::Ongoing)
                ->whereNotNull('release_day')
                ->orderBy('title')
                ->get(['id', 'release_day']);

            $map = [];
            foreach ($days as $day) {
                $map[$day] = $animes->filter(fn (Anime $a): bool => $a->release_day?->value === $day)->pluck('id')->toArray();
            }

            return $map;
        });

        $allIds = array_merge(...array_values($scheduleMap));
        $loadedAnimes = ! empty($allIds)
            ? Anime::with(['studio', 'genres'])->whereIn('id', $allIds)->get()
            : collect();

        $grouped = [];
        foreach ($scheduleMap as $day => $ids) {
            $grouped[$day] = collect($ids)
                ->map(fn ($id) => $loadedAnimes->firstWhere('id', $id))
                ->filter()
                ->values();
        }

        return $grouped;
    }

    /**
     * Invalidate homepage and schedule caches.
     */
    public function flushHomeAndScheduleCache(): void
    {
        Cache::forget('tofunime_featured_hero');
        Cache::forget('tofunime_featured_hero_id');
        Cache::forget('tofunime_recent_episodes_8');
        Cache::forget('tofunime_recent_episode_ids_8');
        Cache::forget('tofunime_recent_episodes_12');
        Cache::forget('tofunime_recent_episode_ids_12');
        Cache::forget('tofunime_iconic_studios');
        Cache::forget('tofunime_iconic_studio_ids');
        Cache::forget('tofunime_weekly_schedule');
        Cache::forget('tofunime_weekly_schedule_ids');
    }
}
