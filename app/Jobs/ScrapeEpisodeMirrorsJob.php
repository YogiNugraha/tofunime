<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DownloadFormat;
use App\Enums\VideoQuality;
use App\Models\DownloadLink;
use App\Models\Episode;
use App\Models\ScraperLog;
use App\Models\VideoMirror;
use App\Services\Scraper\ScraperManager;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeEpisodeMirrorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $episodeId,
        public ?string $provider = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScraperManager $scraperManager): void
    {
        $startTime = microtime(true);
        $episode = Episode::with('anime')->find($this->episodeId);

        if (! $episode || empty($episode->source_episode_url)) {
            Log::warning('ScrapeEpisodeMirrorsJob: Episode or source_url missing', ['id' => $this->episodeId]);

            return;
        }

        try {
            $scraper = $scraperManager->resolve($episode->source_episode_url, $this->provider);
            $data = $scraper->scrapeEpisodeDetails($episode->source_episode_url);
            $mirrorsCount = 0;
            $linksCount = 0;

            // 1. Sync Video Mirrors
            if (! empty($data['mirrors'])) {
                foreach ($data['mirrors'] as $mirror) {
                    $qualityEnum = VideoQuality::tryFrom($mirror['quality']) ?? VideoQuality::Q720p;

                    VideoMirror::updateOrCreate(
                        [
                            'episode_id' => $episode->id,
                            'server_name' => $mirror['server_name'],
                            'quality' => $qualityEnum,
                        ],
                        [
                            'embed_url' => $mirror['embed_url'],
                            'is_default' => $mirror['is_default'],
                            'is_active' => true,
                            'priority_order' => $mirror['priority_order'],
                        ]
                    );
                    $mirrorsCount++;
                }
            }

            // 2. Sync Download Links
            if (! empty($data['download_links'])) {
                foreach ($data['download_links'] as $link) {
                    $qualityEnum = VideoQuality::tryFrom($link['quality']) ?? VideoQuality::Q720p;
                    $formatEnum = DownloadFormat::tryFrom($link['format']) ?? DownloadFormat::MP4;

                    DownloadLink::updateOrCreate(
                        [
                            'anime_id' => $episode->anime_id,
                            'episode_id' => $episode->id,
                            'host_name' => $link['host_name'],
                            'quality' => $qualityEnum,
                            'format' => $formatEnum,
                        ],
                        [
                            'url' => $link['url'],
                            'file_size' => $link['file_size'],
                            'is_batch' => false,
                        ]
                    );
                    $linksCount++;
                }
            }

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => $scraper->getProviderName(),
                'target_url' => $episode->source_episode_url,
                'action' => 'scrape_mirrors',
                'status' => 'success',
                'items_processed' => $mirrorsCount + $linksCount,
                'response_time_ms' => $elapsedMs,
                'payload' => [
                    'episode_id' => $episode->id,
                    'mirrors_extracted' => $mirrorsCount,
                    'downloads_extracted' => $linksCount,
                ],
            ]);
        } catch (Exception $e) {
            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => $scraper->getProviderName(),
                'target_url' => $episode->source_episode_url,
                'action' => 'scrape_mirrors',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_time_ms' => $elapsedMs,
                'payload' => ['episode_id' => $episode->id],
            ]);

            throw $e;
        }
    }
}
