<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AdSlot;
use App\Models\SiteSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Pengaturan Sistem & Iklan - Tofunime CMS')]
class SiteSettingsManager extends Component
{
    public string $scraperBaseUrl = '';

    public string $scraperOploverzBaseUrl = '';

    public string $defaultScraperProvider = 'auto';

    public string $siteTitle = '';

    public string $siteTagline = '';

    public function mount(): void
    {
        $this->scraperBaseUrl = (string) SiteSetting::get('scraper_otakudesu_base_url', 'https://otakudesu.blog');
        $this->scraperOploverzBaseUrl = (string) SiteSetting::get('scraper_oploverz_base_url', 'https://oploverz.site');
        $this->defaultScraperProvider = (string) SiteSetting::get('default_scraper_provider', 'auto');
        $this->siteTitle = (string) SiteSetting::get('site_title', 'Tofunime - Nonton & Download Anime Sub Indo Kualitas HD');
        $this->siteTagline = (string) SiteSetting::get('site_tagline', 'Streaming Anime Subtitle Indonesia Bersih & Elegan');
    }

    public function saveSettings(): void
    {
        $this->validate([
            'scraperBaseUrl' => ['required', 'url'],
            'scraperOploverzBaseUrl' => ['required', 'url'],
            'defaultScraperProvider' => ['required', 'string', 'in:auto,otakudesu,oploverz'],
            'siteTitle' => ['required', 'string', 'max:255'],
            'siteTagline' => ['required', 'string', 'max:255'],
        ]);

        SiteSetting::set('scraper_otakudesu_base_url', $this->scraperBaseUrl, 'scraper', 'Base URL resmi scraper Otakudesu');
        SiteSetting::set('scraper_oploverz_base_url', $this->scraperOploverzBaseUrl, 'scraper', 'Base URL resmi scraper Oploverz');
        SiteSetting::set('default_scraper_provider', $this->defaultScraperProvider, 'scraper', 'Provider default scraper bot');
        SiteSetting::set('site_title', $this->siteTitle, 'general', 'Judul situs utama');
        SiteSetting::set('site_tagline', $this->siteTagline, 'general', 'Tagline Tofunime');

        session()->flash('message', 'Pengaturan sistem & multi-sumber scraper berhasil diperbarui!');
    }

    public function toggleAdSlot(int $slotId): void
    {
        $slot = AdSlot::find($slotId);
        if ($slot) {
            $slot->update(['is_active' => ! $slot->is_active]);
            session()->flash('message', "Status slot iklan '{$slot->name}' berhasil diubah.");
        }
    }

    public function render()
    {
        $adSlots = AdSlot::all();

        return view('livewire.admin.site-settings-manager', compact('adSlots'));
    }
}
