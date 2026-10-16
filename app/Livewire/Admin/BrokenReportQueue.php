<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Jobs\ScrapeEpisodeMirrorsJob;
use App\Models\BrokenReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Antrean Laporan Video Rusak - Tofunime CMS')]
class BrokenReportQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public function reScrapeEpisode(int $reportId): void
    {
        $report = BrokenReport::find($reportId);
        if ($report) {
            ScrapeEpisodeMirrorsJob::dispatch($report->episode_id);
            $report->update([
                'status' => 'fixed',
                'resolved_at' => now(),
            ]);

            session()->flash('message', "Episode #{$report->episode_id} telah diantrekan untuk di-scrape ulang dan status laporan ditandai selesai.");
        }
    }

    public function markResolved(int $reportId, string $status = 'fixed'): void
    {
        $report = BrokenReport::find($reportId);
        if ($report) {
            $report->update([
                'status' => $status,
                'resolved_at' => now(),
            ]);

            session()->flash('message', "Status laporan berhasil diubah menjadi {$status}.");
        }
    }

    public function render()
    {
        $query = BrokenReport::with(['episode.anime', 'mirror']);

        if (! empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $reports = $query->latest('id')->paginate(15);

        return view('livewire.admin.broken-report-queue', compact('reports'));
    }
}
