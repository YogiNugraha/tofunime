<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AnimeStatus;
use App\Models\Anime;
use App\Models\BrokenReport;
use App\Models\Episode;
use App\Models\ScraperLog;
use App\Models\VideoMirror;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard Overview - Tofunime CMS')]
class DashboardOverview extends Component
{
    public function render()
    {
        $stats = [
            'total_animes' => Anime::count(),
            'ongoing_animes' => Anime::where('status', AnimeStatus::Ongoing)->count(),
            'total_episodes' => Episode::count(),
            'total_mirrors' => VideoMirror::count(),
            'pending_reports' => BrokenReport::where('status', 'pending')->count(),
        ];

        $recentAnimes = Anime::with('studio')->latest('id')->take(5)->get();
        $recentLogs = ScraperLog::latest('id')->take(6)->get();

        return view('livewire.admin.dashboard-overview', compact('stats', 'recentAnimes', 'recentLogs'));
    }
}
