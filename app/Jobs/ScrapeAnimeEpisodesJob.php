<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\ScraperLog;
use App\Models\ScraperSource;
use App\Services\Scraper\ScraperManager;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScrapeAnimeEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $animeId,
        public ?string $sourceUrl = null,
        public bool $dispatchMirrors = true,
        public ?string $provider = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScraperManager $scraperManager): void
    {
        $startTime = microtime(true);
        $anime = Anime::with('scraperSources')->find($this->animeId);

        if (! $anime) {
            Log::warning('ScrapeAnimeEpisodesJob: Anime not found', ['id' => $this->animeId]);

            return;
        }

        $targetUrl = $this->sourceUrl;
        if (! $targetUrl) {
            $source = $anime->scraperSources()->first();
            $targetUrl = $source?->source_anime_url;
            $this->provider = $this->provider ?? $source?->provider_name;
        }

        if (! $targetUrl) {
            Log::warning('ScrapeAnimeEpisodesJob: No source URL for anime', ['anime' => $anime->title]);

            return;
        }

        $scraper = $scraperManager->resolve($targetUrl, $this->provider);

        try {
            $scraped = $scraper->scrapeAnimeDetails($targetUrl);
            $newEpisodesCount = 0;
            $latestEpNumber = null;

            foreach ($scraped['episodes'] as $epData) {
                $episode = $this->upsertEpisode($anime, $epData);

                $latestEpNumber = (string) $episode->episode_number;
                $newEpisodesCount++;

                if ($this->dispatchMirrors) {
                    ScrapeEpisodeMirrorsJob::dispatch($episode->id, $scraper->getProviderName());
                }
            }

            // Update ScraperSource
            ScraperSource::updateOrCreate(
                [
                    'anime_id' => $anime->id,
                    'provider_name' => $scraper->getProviderName(),
                ],
                [
                    'source_anime_url' => $targetUrl,
                    'last_scraped_episode' => $latestEpNumber,
                    'last_synced_at' => now(),
                    'auto_sync' => true,
                ]
            );

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => $scraper->getProviderName(),
                'target_url' => $targetUrl,
                'action' => 'scrape_episodes',
                'status' => 'success',
                'items_processed' => $newEpisodesCount,
                'response_time_ms' => $elapsedMs,
                'payload' => [
                    'anime_id' => $anime->id,
                    'episodes_synced' => $newEpisodesCount,
                    'latest_episode' => $latestEpNumber,
                ],
            ]);
        } catch (Exception $e) {
            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => $scraper->getProviderName(),
                'target_url' => $targetUrl,
                'action' => 'scrape_episodes',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_time_ms' => $elapsedMs,
                'payload' => ['anime_id' => $anime->id],
            ]);

            throw $e;
        }
    }

    /**
     * Upsert episode record safely avoiding unique constraint collisions.
     *
     * @param  array{episode_number: string|int|float, episode_order: float|int, title: string, source_url: string}  $epData
     */
    private function upsertEpisode(Anime $anime, array $epData): Episode
    {
        $rawEpNum = trim((string) $epData['episode_number']);
        $normalizedEpNum = is_numeric($rawEpNum)
            ? (str_contains($rawEpNum, '.') ? (string) (float) $rawEpNum : (string) (int) $rawEpNum)
            : ltrim($rawEpNum, '0');
        if ($normalizedEpNum === '') {
            $normalizedEpNum = '0';
        }

        $paddedEpNum = ctype_digit($normalizedEpNum) && (int) $normalizedEpNum < 10
            ? str_pad($normalizedEpNum, 2, '0', STR_PAD_LEFT)
            : $normalizedEpNum;

        $canonicalSlug = Str::slug("{$anime->slug}-episode-{$normalizedEpNum}-sub-indo");

        // 1. Find existing episode by anime_id + (slug OR normalized/padded/raw number OR source_url)
        $episode = Episode::where('anime_id', $anime->id)
            ->where(function ($q) use ($canonicalSlug, $normalizedEpNum, $rawEpNum, $paddedEpNum, $epData): void {
                $q->where('slug', $canonicalSlug)
                    ->orWhere('episode_number', $normalizedEpNum)
                    ->orWhere('episode_number', $rawEpNum)
                    ->orWhere('episode_number', $paddedEpNum);
                if (! empty($epData['source_url'])) {
                    $q->orWhere('source_episode_url', $epData['source_url']);
                }
            })
            ->first();

        // 2. Fallback check by global canonical slug
        if (! $episode) {
            $globalEpisode = Episode::where('slug', $canonicalSlug)->first();
            if ($globalEpisode && $globalEpisode->anime_id === $anime->id) {
                $episode = $globalEpisode;
            }
        }

        // 3. Resolve any foreign slug collision before save
        $slugConflict = Episode::where('slug', $canonicalSlug)
            ->when($episode !== null, fn ($q) => $q->where('id', '!=', $episode->id))
            ->first();

        if ($slugConflict) {
            if ($slugConflict->anime_id === $anime->id) {
                $slugConflict->delete();
            } else {
                $canonicalSlug = "{$canonicalSlug}-{$anime->id}";
            }
        }

        if ($episode) {
            $episode->episode_number = $normalizedEpNum;
            $episode->episode_order = (float) $epData['episode_order'];
            $episode->title = $epData['title'];
            $episode->slug = $canonicalSlug;
            $episode->source_episode_url = $epData['source_url'];
            if (! $episode->aired_at) {
                $episode->aired_at = now();
            }
            $episode->save();

            return $episode;
        }

        return Episode::create([
            'anime_id' => $anime->id,
            'episode_number' => $normalizedEpNum,
            'episode_order' => (float) $epData['episode_order'],
            'title' => $epData['title'],
            'slug' => $canonicalSlug,
            'source_episode_url' => $epData['source_url'],
            'aired_at' => now(),
        ]);
    }
}
