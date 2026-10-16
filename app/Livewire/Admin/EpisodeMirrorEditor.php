<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\VideoQuality;
use App\Jobs\ScrapeEpisodeMirrorsJob;
use App\Models\Episode;
use App\Models\VideoMirror;
use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Episode & Mirror Editor - Tofunime CMS')]
class EpisodeMirrorEditor extends Component
{
    public Episode $episode;

    public string $newServerName = '';

    public string $newQuality = '720p';

    public string $newEmbedUrl = '';

    public function mount(Episode $episode): void
    {
        $this->episode = $episode->load(['anime', 'mirrors', 'downloadLinks']);
    }

    public function reScrapeNow(OtakudesuScraperAdapter $scraper): void
    {
        $job = new ScrapeEpisodeMirrorsJob($this->episode->id);
        $job->handle($scraper);

        $this->episode->refresh();
        session()->flash('message', 'Episode berhasil di-scrape ulang secara instan!');
    }

    public function addMirror(): void
    {
        $this->validate([
            'newServerName' => 'required|string|max:80',
            'newQuality' => 'required',
            'newEmbedUrl' => 'required|url',
        ]);

        VideoMirror::create([
            'episode_id' => $this->episode->id,
            'server_name' => $this->newServerName,
            'quality' => VideoQuality::tryFrom($this->newQuality) ?? VideoQuality::Q720p,
            'embed_url' => $this->newEmbedUrl,
            'is_default' => $this->episode->mirrors->isEmpty(),
            'is_active' => true,
            'priority_order' => $this->episode->mirrors->count() + 1,
        ]);

        $this->reset(['newServerName', 'newEmbedUrl']);
        $this->episode->refresh();
        session()->flash('message', 'Mirror streaming baru berhasil ditambahkan.');
    }

    public function deleteMirror(int $mirrorId): void
    {
        VideoMirror::where('id', $mirrorId)->where('episode_id', $this->episode->id)->delete();
        $this->episode->refresh();
        session()->flash('message', 'Mirror streaming berhasil dihapus.');
    }

    public function toggleMirrorActive(int $mirrorId): void
    {
        $mirror = VideoMirror::where('id', $mirrorId)->where('episode_id', $this->episode->id)->first();
        if ($mirror) {
            $mirror->update(['is_active' => ! $mirror->is_active]);
            $this->episode->refresh();
        }
    }

    public function render()
    {
        return view('livewire.admin.episode-mirror-editor');
    }
}
