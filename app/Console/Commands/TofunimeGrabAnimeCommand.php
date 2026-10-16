<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Scraper\HybridAnimeImporter;
use Exception;
use Illuminate\Console\Command;

class TofunimeGrabAnimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tofunime:grab
                            {url : URL anime sumber (Otakudesu)}
                            {--mal-id= : MyAnimeList ID opsional untuk pencocokan presisi}
                            {--sync-now : Langsung ekstrak iframe streaming mirrors tanpa antrean queue}';

    /**
     * The console command description.
     */
    protected $description = 'Ambil dan sinkronkan anime secara hybrid dari Otakudesu dan Jikan API v4';

    /**
     * Execute the console command.
     */
    public function handle(HybridAnimeImporter $importer): int
    {
        $url = (string) $this->argument('url');
        $malIdOption = $this->option('mal-id');
        $malId = $malIdOption !== null ? (int) $malIdOption : null;
        $syncNow = (bool) $this->option('sync-now');

        $this->info('=== TOFUNIME HYBRID GRABBER ===');
        $this->line("Target URL : {$url}");
        if ($malId) {
            $this->line("MAL ID     : {$malId}");
        }
        $this->line('Mode       : '.($syncNow ? 'Sync (Direct Execution)' : 'Async (Background Queue)'));
        $this->newLine();

        $this->output->write('Mengunduh & memproses metadata anime... ');

        try {
            $anime = $importer->import($url, $malId, $syncNow);

            $this->output->writeln('<info>SUKSES</info>');
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $anime->id],
                    ['Judul Utama', $anime->title],
                    ['Judul Jepang', $anime->japanese_title ?? '-'],
                    ['Studio', $anime->studio?->name ?? '-'],
                    ['Musim / Tahun', $anime->season?->name ?? "{$anime->release_year}"],
                    ['Skor Rating', number_format((float) $anime->rating_score, 2)],
                    ['Total Episode', $anime->episodes()->count()],
                    ['Genre & Tag', $anime->taxonomies->pluck('name')->implode(', ')],
                    ['Author / Staff', $anime->people->pluck('name')->implode(', ')],
                ]
            );

            $this->info('Anime berhasil diimpor ke dalam katalog Tofunime!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->output->writeln('<error>GAGAL</error>');
            $this->error('Terjadi kesalahan: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
