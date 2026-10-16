<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AnimeStatus;
use App\Jobs\ScrapeAnimeEpisodesJob;
use App\Models\Anime;
use App\Models\ScraperLog;
use App\Models\ScraperSource;
use App\Services\Scraper\HybridAnimeImporter;
use App\Services\Scraper\ScraperManager;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Hybrid Grabber Studio - Tofunime CMS')]
class HybridGrabberStudio extends Component
{
    public string $sourceUrl = 'https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/';

    public string $selectedProvider = 'auto';

    public bool $syncMirrorsNow = true;

    public bool $isProcessing = false;

    public ?Anime $importedAnime = null;

    public string $statusMessage = '';

    public string $statusType = 'success'; // 'success' | 'error'

    /**
     * Real-time execution logs for the current/latest scraping operation.
     * Each entry: ['time' => string, 'tag' => string, 'message' => string, 'percent' => int]
     */
    public array $executionLogs = [];

    public float $executionDuration = 0.0;

    public int $progressPercent = 0;

    public string $currentStep = '';

    /**
     * 1-Click Hybrid Importer execution.
     */
    public function importNow(HybridAnimeImporter $importer): void
    {
        $this->validate([
            'sourceUrl' => ['required', 'url'],
            'selectedProvider' => ['required', 'string'],
        ]);

        $this->isProcessing = true;
        $this->statusMessage = '';
        $this->executionLogs = [];
        $this->progressPercent = 5;
        $this->currentStep = 'Memulai proses scraping...';
        $startTime = microtime(true);

        $progressCallback = function (string $tag, string $message, int $percent): void {
            $logEntry = [
                'time' => now()->format('H:i:s.v'),
                'tag' => $tag,
                'message' => $message,
                'percent' => $percent,
            ];

            $this->executionLogs[] = $logEntry;
            $this->progressPercent = $percent;
            $this->currentStep = $message;

            try {
                $this->stream(
                    to: 'scraper-console-stream',
                    content: sprintf(
                        "<div class=\"flex items-start gap-3 py-1 font-mono text-sm border-b border-stone-warm/30\"><span class=\"text-ink-muted shrink-0\">%s</span><span class=\"px-2 py-0.5 rounded text-mana bg-alabaster font-semibold shrink-0\">[%s]</span><span class=\"text-ink break-all\">%s</span></div>\n",
                        e($logEntry['time']),
                        e($logEntry['tag']),
                        e($logEntry['message'])
                    )
                );
            } catch (Exception) {
                // Ignore if streaming is not supported in the current request context
            }
        };

        try {
            $providerKey = $this->selectedProvider !== 'auto' ? $this->selectedProvider : null;

            $this->importedAnime = $importer->import(
                $this->sourceUrl,
                null,
                $this->syncMirrorsNow,
                $providerKey,
                $progressCallback
            );

            $this->executionDuration = round(microtime(true) - $startTime, 2);
            $this->progressPercent = 100;
            $this->currentStep = 'Selesai!';
            $this->statusType = 'success';
            $this->statusMessage = "Sukses! Anime '{$this->importedAnime->title}' berhasil diimpor dalam {$this->executionDuration} detik beserta {$this->importedAnime->episodes()->count()} episode.";
        } catch (Exception $e) {
            $this->executionDuration = round(microtime(true) - $startTime, 2);
            $this->statusType = 'error';
            $this->statusMessage = 'Gagal mengimpor anime: '.$e->getMessage();
            $this->currentStep = 'Terjadi kesalahan saat import';

            $this->executionLogs[] = [
                'time' => now()->format('H:i:s.v'),
                'tag' => 'ERROR',
                'message' => 'Gagal: '.$e->getMessage(),
                'percent' => $this->progressPercent,
            ];
        } finally {
            $this->isProcessing = false;
            $this->dispatch('scraper-finished');
        }
    }

    /**
     * Clear all execution logs from the studio console.
     */
    public function clearLogs(): void
    {
        $this->executionLogs = [];
        $this->executionDuration = 0.0;
        $this->progressPercent = 0;
        $this->currentStep = '';
    }

    /**
     * Trigger check and scrape for an ongoing anime source.
     */
    public function syncOngoingSource(int $animeId, string $sourceUrl, ?string $provider = null): void
    {
        ScrapeAnimeEpisodesJob::dispatch($animeId, $sourceUrl, true, $provider);
        session()->flash('ongoing_message', "Pemeriksaan episode baru untuk anime ID #{$animeId} telah diantrekan.");
    }

    public function render(ScraperManager $scraperManager)
    {
        $availableProviders = $scraperManager->getAvailableProviders();

        $ongoingSources = ScraperSource::with('anime')
            ->whereHas('anime', fn ($q) => $q->where('status', AnimeStatus::Ongoing))
            ->latest('last_synced_at')
            ->get();

        $recentLogs = ScraperLog::latest('id')->take(10)->get();

        $pendingJobsCount = DB::table('jobs')->count();

        return view('livewire.admin.hybrid-grabber-studio', compact('availableProviders', 'ongoingSources', 'recentLogs', 'pendingJobsCount'));
    }
}
