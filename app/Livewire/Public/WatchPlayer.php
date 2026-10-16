<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Jobs\ScrapeEpisodeMirrorsJob;
use App\Models\BrokenReport;
use App\Models\Episode;
use App\Models\VideoMirror;
use App\Models\WatchHistory;
use App\Services\Seo\TofunimeSeoGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class WatchPlayer extends Component
{
    public Episode $episode;

    public string $selectedQuality = '720p';

    public ?int $activeMirrorId = null;

    public string $currentEmbedUrl = '';

    public bool $reportedBroken = false;

    public string $reportMessage = '';

    public function mount(Episode $episode): void
    {
        $this->episode = $episode->load([
            'anime.episodes',
            'anime.genres',
            'activeMirrors',
            'downloadLinks',
        ]);

        $this->initDefaultMirror();
        $this->recordWatchHistory();
    }

    /**
     * Check whether a video mirror is alive, operational, and accessible.
     */
    public function isMirrorOnline(VideoMirror $mirror): bool
    {
        $url = trim((string) $mirror->embed_url);

        if (empty($url) || str_contains(strtolower($url), 'maintenance') || str_contains($url, 'about:blank')) {
            return false;
        }

        // Cache mirror health for 10 minutes (600s) for blazing-fast page loads
        return (bool) Cache::remember("mirror_health_{$mirror->id}", 600, function () use ($url): bool {
            try {
                $referer = str_contains($url, 'oploverz') ? 'https://oploverz.site/' : 'https://otakudesu.blog/';
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Referer' => $referer,
                ])
                    ->withOptions(['force_ip_resolve' => 'v4'])
                    ->timeout(3)
                    ->get($url);

                if (! $response->successful()) {
                    return false;
                }

                $body = strtolower($response->body());
                $deadIndicators = [
                    'file was deleted',
                    'file not found',
                    'video deleted',
                    'has been removed',
                    'under maintenance',
                    'maintenance mode',
                    'server error',
                    '404 not found',
                ];

                foreach ($deadIndicators as $indicator) {
                    if (str_contains($body, $indicator)) {
                        return false;
                    }
                }

                return true;
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Helper to score mirror priority based on reliability and embedding safety.
     * Direct clean streams (Desustream, Blogger, Google, Mega) are given top priority.
     */
    protected function getMirrorPriorityScore(VideoMirror $mirror): int
    {
        $url = strtolower((string) $mirror->embed_url);
        $raw = strtolower((string) $mirror->server_name);

        // Top Priority: Cleanest direct stream with zero ads (Desustream, Blogger, Google CDN)
        if (str_contains($url, 'desustream.net') || str_contains($url, 'odcloud.net') || str_contains($url, 'blogger.com') || str_contains($raw, 'blogger') || str_contains($raw, 'google')) {
            return 1;
        }

        // High Priority: Mega Cloud (ad-free clean embed)
        if (str_contains($url, 'mega.nz') || str_contains($raw, 'mega')) {
            return 2;
        }

        // Lowest priority: Providers known for aggressive popup/popunder ads (Vidhide)
        if (str_contains($url, 'vidhide')) {
            return 50;
        }

        return $mirror->priority_order ?: 10;
    }

    /**
     * Initialize default active mirror by checking which server is online and operational.
     */
    protected function initDefaultMirror(): void
    {
        $mirrors = $this->episode->activeMirrors;

        if ($mirrors->isEmpty()) {
            return;
        }

        $validMirrors = $mirrors->filter(fn ($m) => ! str_contains(strtolower((string) $m->embed_url), 'maintenance'))
            ->sortBy(fn (VideoMirror $m): int => $this->getMirrorPriorityScore($m));

        $pool = $validMirrors->isNotEmpty() ? $validMirrors : $mirrors;

        // Group candidate mirrors: candidate mirrors for selected quality first, then other qualities
        $matchingQuality = $pool->where('quality.value', $this->selectedQuality)
            ->sortBy(fn (VideoMirror $m): int => $this->getMirrorPriorityScore($m));
        $otherQualities = $pool->where('quality.value', '!=', $this->selectedQuality)
            ->sortBy(fn (VideoMirror $m): int => $this->getMirrorPriorityScore($m));
        $orderedCandidates = $matchingQuality->concat($otherQualities);

        // Pre-check health: select the FIRST server that is VERIFIED ONLINE!
        $healthyMirror = null;
        foreach ($orderedCandidates as $candidate) {
            if ($this->isMirrorOnline($candidate)) {
                $healthyMirror = $candidate;
                break;
            }
        }

        // Fallback to highest priority mirror if none passed or offline
        $selected = $healthyMirror ?? $matchingQuality->first() ?? $pool->first();

        if ($selected) {
            $this->activeMirrorId = $selected->id;
            $this->currentEmbedUrl = $selected->embed_url;
            $this->selectedQuality = $selected->quality->value;
        }
    }

    /**
     * Switch streaming resolution tab with automatic health pre-check.
     */
    public function setQuality(string $quality): void
    {
        $this->selectedQuality = $quality;
        $mirrors = $this->episode->activeMirrors
            ->where('quality.value', $quality)
            ->filter(fn ($m) => ! str_contains(strtolower((string) $m->embed_url), 'maintenance'))
            ->sortBy(fn (VideoMirror $m): int => $this->getMirrorPriorityScore($m));

        $healthyMirror = null;
        foreach ($mirrors as $candidate) {
            if ($this->isMirrorOnline($candidate)) {
                $healthyMirror = $candidate;
                break;
            }
        }

        $selected = $healthyMirror ?? $mirrors->first() ?? $this->episode->activeMirrors->where('quality.value', $quality)->first();

        if ($selected) {
            $this->activeMirrorId = $selected->id;
            $this->currentEmbedUrl = $selected->embed_url;
        }
    }

    /**
     * Switch active server mirror.
     */
    public function setMirror(int $mirrorId): void
    {
        $mirror = $this->episode->activeMirrors->firstWhere('id', $mirrorId);

        if ($mirror) {
            $this->activeMirrorId = $mirror->id;
            $this->currentEmbedUrl = $mirror->embed_url;
            $this->selectedQuality = $mirror->quality->value;
            $this->reportedBroken = false;
        }
    }

    /**
     * Report video error and auto-switch to next healthy backup mirror.
     */
    public function reportBrokenAndFallback(): void
    {
        $currentMirror = $this->episode->activeMirrors->firstWhere('id', $this->activeMirrorId);

        // Mark current mirror as offline in cache immediately
        if ($currentMirror) {
            Cache::put("mirror_health_{$currentMirror->id}", false, 600);
        }

        // Record broken report
        BrokenReport::create([
            'episode_id' => $this->episode->id,
            'video_mirror_id' => $currentMirror?->id,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'issue_description' => 'User or auto-detector triggered fallback in player',
            'status' => 'pending',
        ]);

        // If this mirror reached >= 5 broken reports, disable it and trigger re-scrape
        if ($currentMirror) {
            $reportsCount = BrokenReport::where('video_mirror_id', $currentMirror->id)
                ->where('status', 'pending')
                ->count();

            if ($reportsCount >= 5) {
                $currentMirror->update(['is_active' => false]);
                ScrapeEpisodeMirrorsJob::dispatch($this->episode->id);
            }
        }

        // Auto-fallback: Find next healthy mirror
        $otherMirrors = $this->episode->activeMirrors->where('id', '!=', $this->activeMirrorId);

        $nextHealthy = null;
        foreach ($otherMirrors as $candidate) {
            if ($this->isMirrorOnline($candidate)) {
                $nextHealthy = $candidate;
                break;
            }
        }

        $nextMirror = $nextHealthy ?? $otherMirrors->first();

        if ($nextMirror) {
            $this->setMirror($nextMirror->id);
            $this->reportedBroken = true;
            $this->reportMessage = "Server dialihkan otomatis ke {$nextMirror->display_name} ({$nextMirror->quality->value}) yang terverifikasi aktif.";
        } else {
            $this->reportedBroken = true;
            $this->reportMessage = 'Laporan dicatat! Seluruh server sedang dalam proses verifikasi & perbaikan otomatis.';
        }
    }

    /**
     * Save watch history into database for user or session.
     */
    protected function recordWatchHistory(): void
    {
        try {
            $userId = Auth::id();
            $sessionId = Session::getId();

            WatchHistory::updateOrCreate(
                [
                    'user_id' => $userId,
                    'anime_id' => $this->episode->anime_id,
                    'episode_id' => $this->episode->id,
                ],
                [
                    'session_id' => $userId ? null : $sessionId,
                    'last_watched_at' => now(),
                ]
            );

            // Increment views count safely
            $this->episode->increment('views_count');
            $this->episode->anime->increment('views_count');
        } catch (\Throwable) {
            // Ignore watch history error if guest
        }
    }

    public function render(TofunimeSeoGenerator $seoGenerator)
    {
        $anime = $this->episode->anime;
        $allEpisodes = $anime->episodes;

        // Chronological Navigation: Prev Episode (lower order, left) and Next Episode (higher order, right)
        $prevEpisode = $allEpisodes
            ->where('episode_order', '<', $this->episode->episode_order)
            ->sortByDesc('episode_order')
            ->first();

        $nextEpisode = $allEpisodes
            ->where('episode_order', '>', $this->episode->episode_order)
            ->sortBy('episode_order')
            ->first();

        $availableQualities = $this->episode->activeMirrors
            ->pluck('quality.value')
            ->unique()
            ->values()
            ->toArray();

        $mirrorsForQuality = $this->episode->activeMirrors
            ->where('quality.value', $this->selectedQuality);

        $mirrorHealthMap = [];
        foreach ($mirrorsForQuality as $m) {
            $mirrorHealthMap[$m->id] = Cache::get("mirror_health_{$m->id}");
        }

        $activeMirror = $this->episode->activeMirrors->firstWhere('id', $this->activeMirrorId);

        $seoJsonLd = $seoGenerator->renderJsonLd($seoGenerator->generateWatchSchema($this->episode));

        return view('livewire.public.watch-player', compact(
            'anime',
            'allEpisodes',
            'prevEpisode',
            'nextEpisode',
            'availableQualities',
            'mirrorsForQuality',
            'mirrorHealthMap',
            'activeMirror',
            'seoJsonLd'
        ))->title("Nonton {$anime->title} Episode {$this->episode->episode_number} Sub Indo - Tofunime");
    }
}
