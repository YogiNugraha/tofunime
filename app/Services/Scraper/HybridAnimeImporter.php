<?php

declare(strict_types=1);

namespace App\Services\Scraper;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Jobs\ScrapeEpisodeMirrorsJob;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\ScraperLog;
use App\Models\ScraperSource;
use App\Services\Jikan\JikanApiService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HybridAnimeImporter
{
    public function __construct(
        protected JikanApiService $jikanService,
        protected ScraperManager $scraperManager
    ) {}

    /**
     * Import anime hybridly from any supported provider URL (Otakudesu, Oploverz, etc.) and Jikan API v4.
     *
     * @param  string  $sourceUrl  Full URL from supported anime scraper
     * @param  int|null  $malId  Optional MyAnimeList ID
     * @param  bool  $syncMirrorsNow  If true, scrapes mirrors immediately instead of queueing
     * @param  string|null  $provider  Optional provider key (e.g. 'otakudesu', 'oploverz')
     * @param  callable|null  $progressCallback  Optional callback fn(string $tag, string $message, int $percent)
     */
    public function import(
        string $sourceUrl,
        ?int $malId = null,
        bool $syncMirrorsNow = false,
        ?string $provider = null,
        ?callable $progressCallback = null
    ): Anime {
        $startTime = microtime(true);

        if ($progressCallback) {
            $progressCallback('INIT', "Menginisialisasi driver scraper untuk URL: {$sourceUrl}", 10);
        }

        // 1. Resolve appropriate scraper adapter and scrape basic details & episode list
        $scraper = $this->scraperManager->resolve($sourceUrl, $provider);
        $providerName = $scraper->getProviderName();

        if ($progressCallback) {
            $progressCallback('SCRAPE', "Menghubungi provider [{$providerName}] dan mengekstrak struktur HTML/JSON...", 20);
        }

        $scrapedData = $scraper->scrapeAnimeDetails($sourceUrl);
        $episodeCount = count($scrapedData['episodes'] ?? []);

        if ($progressCallback) {
            $progressCallback('METADATA', "Berhasil membaca metadata: '{$scrapedData['title']}' ({$episodeCount} episode terdeteksi)", 35);
        }

        $cleanTitle = $this->cleanTitleForJikanSearch($scrapedData['title']);

        // 2. Determine MAL ID (if provided or search via Jikan)
        $targetMalId = $malId;
        $jikanSearchResult = null;

        if ($targetMalId === null && ! empty($cleanTitle)) {
            if ($progressCallback) {
                $progressCallback('MAL_SEARCH', "Mencari referensi MyAnimeList v4 dengan query: '{$cleanTitle}'...", 45);
            }

            $jikanSearchResult = $this->jikanService->searchAnime($cleanTitle);
            if ($jikanSearchResult && ! empty($jikanSearchResult['mal_id'])) {
                $targetMalId = (int) $jikanSearchResult['mal_id'];
                if ($progressCallback) {
                    $matchedTitle = $jikanSearchResult['title'] ?? $cleanTitle;
                    $progressCallback('MAL_MATCH', "Ditemukan MAL ID #{$targetMalId} ('{$matchedTitle}')", 55);
                }
            } else {
                if ($progressCallback) {
                    $progressCallback('MAL_SEARCH', 'Tidak ada kecocokan judul otomatis di Jikan API. Melanjutkan impor lokal.', 55);
                }
            }
        } elseif ($targetMalId !== null) {
            if ($progressCallback) {
                $progressCallback('MAL_MANUAL', "Menggunakan MAL ID manual: #{$targetMalId}", 55);
            }
        }

        // 3. Create or find local Anime model
        if ($progressCallback) {
            $progressCallback('DB_ANIME', 'Menyiapkan entitas Anime ke basis data...', 60);
        }

        $slug = Str::slug($scrapedData['title']);
        $anime = Anime::firstOrNew(['slug' => $slug]);

        if (! $anime->exists) {
            $anime->fill([
                'title' => $scrapedData['title'],
                'slug' => $slug,
                'japanese_title' => $scrapedData['japanese_title'] ?? null,
                'synopsis' => $scrapedData['synopsis'] ?? null,
                'poster_url' => $scrapedData['poster_url'] ?? null,
                'banner_url' => $scrapedData['poster_url'] ?? null,
                'type' => AnimeType::TV,
                'status' => str_contains(strtolower($scrapedData['status'] ?? ''), 'selesai') || str_contains(strtolower($scrapedData['status'] ?? ''), 'completed')
                    ? AnimeStatus::Completed
                    : AnimeStatus::Ongoing,
                'duration' => $scrapedData['duration'] ?? null,
                'aired_string' => $scrapedData['aired_string'] ?? null,
                'rating_score' => $scrapedData['rating_score'] ?? 0.0,
            ]);
            $anime->save();
        } else {
            // Keep existing Indonesian synopsis if available
            if (! empty($scrapedData['synopsis']) && empty($anime->synopsis)) {
                $anime->synopsis = $scrapedData['synopsis'];
            }
            if (empty($anime->poster_url) && ! empty($scrapedData['poster_url'])) {
                $anime->poster_url = $scrapedData['poster_url'];
            }
            $anime->save();
        }

        if ($progressCallback) {
            $progressCallback('DB_ANIME', "Anime tersimpan dengan ID #{$anime->id} [Slug: {$anime->slug}]", 68);
        }

        // 4. Enrich with Jikan metadata if MAL ID is resolved
        if ($targetMalId !== null) {
            if ($progressCallback) {
                $progressCallback('JIKAN_SYNC', 'Sinkronisasi metadata MyAnimeList (Sinopsis, Studio, Genres, Staff)...', 75);
            }

            try {
                $jikanDetails = $this->jikanService->getFullAnimeDetails($targetMalId);
                $this->jikanService->syncJikanDataToAnime($anime, $jikanDetails);

                $staff = $this->jikanService->getAnimeAuthorsAndStaff($targetMalId);
                if (! empty($staff)) {
                    $this->jikanService->syncStaffToAnime($anime, $staff);
                }

                if ($progressCallback) {
                    $progressCallback('JIKAN_DONE', 'Metadata resmi MyAnimeList berhasil disinkronkan', 82);
                }
            } catch (Exception $e) {
                Log::warning('HybridAnimeImporter: Jikan enrichment failed, continuing with provider data', [
                    'mal_id' => $targetMalId,
                    'error' => $e->getMessage(),
                ]);

                if ($progressCallback) {
                    $progressCallback('JIKAN_WARN', "Peringatan Jikan API: {$e->getMessage()}. Melanjutkan.", 82);
                }
            }
        }

        // 5. Link ScraperSource
        $latestEpNum = null;
        if (! empty($scrapedData['episodes'])) {
            $latestEp = end($scrapedData['episodes']);
            $latestEpNum = (string) $latestEp['episode_number'];
        }

        ScraperSource::updateOrCreate(
            [
                'anime_id' => $anime->id,
                'provider_name' => $providerName,
            ],
            [
                'source_anime_url' => $sourceUrl,
                'last_scraped_episode' => $latestEpNum,
                'last_synced_at' => now(),
                'auto_sync' => true,
            ]
        );

        // 6. Save Episodes and Trigger Mirror Extraction
        if ($progressCallback) {
            $progressCallback('EPISODES', "Menyimpan {$episodeCount} episode dan menyiapkan mirror streaming...", 88);
        }

        foreach ($scrapedData['episodes'] as $epData) {
            $episode = $this->upsertEpisode($anime, $epData);

            if ($syncMirrorsNow) {
                $mirrorJob = new ScrapeEpisodeMirrorsJob($episode->id, $providerName);
                $mirrorJob->handle($this->scraperManager);
            } else {
                ScrapeEpisodeMirrorsJob::dispatch($episode->id, $providerName);
            }
        }

        $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);
        $elapsedSec = number_format($elapsedMs / 1000, 2);

        if ($progressCallback) {
            $mirrorNote = $syncMirrorsNow ? 'Mirrors streaming berhasil diekstrak langsung.' : 'Mirrors streaming dijadwalkan ke queue worker.';
            $progressCallback('EPISODES_DONE', "{$episodeCount} episode tersimpan. {$mirrorNote}", 95);
        }

        ScraperLog::create([
            'provider_name' => $providerName,
            'target_url' => $sourceUrl,
            'action' => 'hybrid_import',
            'status' => 'success',
            'items_processed' => count($scrapedData['episodes']),
            'response_time_ms' => $elapsedMs,
            'payload' => [
                'anime_id' => $anime->id,
                'title' => $anime->title,
                'mal_id' => $anime->mal_id,
                'episodes_count' => count($scrapedData['episodes']),
            ],
        ]);

        if ($progressCallback) {
            $progressCallback('SUCCESS', "Proses import tuntas dalam {$elapsedSec}s ({$elapsedMs} ms)!", 100);
        }

        return $anime->fresh(['studio', 'season', 'genres', 'themes', 'demographics', 'people', 'episodes']);
    }

    /**
     * Clean Indonesian title suffixes (Sub Indo, Subtitle Indonesia) for better Jikan search matching.
     */
    public function cleanTitleForJikanSearch(string $rawTitle): string
    {
        $patterns = [
            '/\b(Sub\s*Indo|Subtitle\s*Indonesia|Indonesian\s*Sub)\b/i',
            '/\b(BD|Batch)\b/i',
            '/\[.*?\]/',
            '/\(.*?\)/',
        ];

        $cleaned = preg_replace($patterns, '', $rawTitle);

        return trim((string) preg_replace('/\s+/', ' ', (string) $cleaned));
    }

    /**
     * Upsert episode record safely avoiding unique constraint collisions.
     *
     * @param  array{episode_number: string|int|float, episode_order: float|int, title: string, source_url: string}  $epData
     */
    public function upsertEpisode(Anime $anime, array $epData): Episode
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
