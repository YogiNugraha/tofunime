<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ScrapeAnimeEpisodesJob;
use App\Models\ScraperSource;
use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use App\Services\Scraper\HybridAnimeImporter;
use Exception;
use Illuminate\Console\Command;

class TofunimeScrapeOngoingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tofunime:scrape-ongoing
                            {--pages=1 : Jumlah halaman ongoing yang diperiksa}
                            {--auto-import : Otomatis impor anime jika belum ada di database}';

    /**
     * The console command description.
     */
    protected $description = 'Pantau dan perbarui anime Ongoing dari halaman rilisan terbaru Otakudesu';

    /**
     * Execute the console command.
     */
    public function handle(OtakudesuScraperAdapter $scraper, HybridAnimeImporter $importer): int
    {
        $pages = max(1, (int) $this->option('pages'));
        $autoImport = (bool) $this->option('auto-import');

        $this->info('=== TOFUNIME ONGOING MONITOR ===');
        $this->line("Memeriksa {$pages} halaman anime Ongoing dari {$scraper->getBaseUrl()}...");
        $this->newLine();

        $totalChecked = 0;
        $totalUpdated = 0;
        $totalImported = 0;

        for ($p = 1; $p <= $pages; $p++) {
            $this->line("--- Memproses Halaman {$p} ---");

            try {
                $ongoingList = $scraper->scrapeOngoing($p);

                if (empty($ongoingList)) {
                    $this->warn("Tidak ada anime ditemukan di halaman {$p}.");
                    break;
                }

                foreach ($ongoingList as $item) {
                    $totalChecked++;
                    $source = ScraperSource::where('source_anime_url', $item['url'])->first();

                    if ($source) {
                        // Existing anime: trigger episode check
                        $this->line("• Memperbarui: <comment>{$item['title']}</comment> ({$item['current_episode']})");
                        ScrapeAnimeEpisodesJob::dispatch($source->anime_id, $source->source_anime_url);
                        $totalUpdated++;
                    } elseif ($autoImport) {
                        $this->line("• Mengimpor Baru: <info>{$item['title']}</info>...");
                        try {
                            $importer->import($item['url']);
                            $totalImported++;
                        } catch (Exception $e) {
                            $this->error("  Gagal mengimpor {$item['title']}: ".$e->getMessage());
                        }
                    } else {
                        $this->line("• Belum Terdaftar: {$item['title']} (Gunakan --auto-import untuk menyimpan)");
                    }
                }
            } catch (Exception $e) {
                $this->error("Error saat memproses halaman {$p}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Anime Diperiksa', $totalChecked],
                ['Antrean Update Episode', $totalUpdated],
                ['Anime Baru Diimpor', $totalImported],
            ]
        );

        $this->info('Pemeriksaan anime Ongoing selesai!');

        return Command::SUCCESS;
    }
}
