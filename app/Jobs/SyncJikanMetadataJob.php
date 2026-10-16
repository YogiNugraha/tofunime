<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Anime;
use App\Models\ScraperLog;
use App\Services\Jikan\JikanApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJikanMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function __construct(
        public int $animeId,
        public ?int $malId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(JikanApiService $jikanService): void
    {
        $startTime = microtime(true);
        $anime = Anime::find($this->animeId);

        if (! $anime) {
            Log::warning('SyncJikanMetadataJob: Anime not found', ['id' => $this->animeId]);

            return;
        }

        try {
            $targetMalId = $this->malId ?? $anime->mal_id;

            // If no MAL ID exists, try search by title
            if (! $targetMalId) {
                $searchResult = $jikanService->searchAnime($anime->title);
                if ($searchResult && ! empty($searchResult['mal_id'])) {
                    $targetMalId = (int) $searchResult['mal_id'];
                }
            }

            if (! $targetMalId) {
                Log::info('SyncJikanMetadataJob: No MAL ID found for anime', ['title' => $anime->title]);

                return;
            }

            // Fetch details & staff
            $jikanDetails = $jikanService->getFullAnimeDetails($targetMalId);
            $jikanService->syncJikanDataToAnime($anime, $jikanDetails);

            $staff = $jikanService->getAnimeAuthorsAndStaff($targetMalId);
            if (! empty($staff)) {
                $jikanService->syncStaffToAnime($anime, $staff);
            }

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => 'jikan',
                'target_url' => "https://api.jikan.moe/v4/anime/{$targetMalId}/full",
                'action' => 'sync_metadata',
                'status' => 'success',
                'items_processed' => 1,
                'response_time_ms' => $elapsedMs,
                'payload' => [
                    'anime_id' => $anime->id,
                    'mal_id' => $targetMalId,
                ],
            ]);
        } catch (Exception $e) {
            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            ScraperLog::create([
                'provider_name' => 'jikan',
                'target_url' => $anime->title,
                'action' => 'sync_metadata',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_time_ms' => $elapsedMs,
                'payload' => ['anime_id' => $anime->id],
            ]);

            throw $e;
        }
    }
}
