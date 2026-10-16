<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VideoQuality;
use App\Livewire\Admin\SiteSettingsManager;
use App\Livewire\Public\WatchPlayer;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\SiteSetting;
use App\Models\VideoMirror;
use App\Services\Scraper\Adapters\OploverzScraperAdapter;
use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use App\Services\Scraper\ScraperManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MultiProviderAndHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_scraper_manager_resolves_and_detects_providers_correctly(): void
    {
        $manager = app(ScraperManager::class);

        // Detect by URL
        $this->assertEquals('otakudesu', $manager->detectProvider('https://otakudesu.blog/anime/sousou-no-frieren-sub-indo/'));
        $this->assertEquals('oploverz', $manager->detectProvider('https://oploverz.site/series/frieren-beyond-journeys-end-s1'));

        // Resolve instances
        $otakuDriver = $manager->resolve('https://otakudesu.blog/anime/test');
        $this->assertInstanceOf(OtakudesuScraperAdapter::class, $otakuDriver);

        $oploverzDriver = $manager->resolve('https://oploverz.site/series/test');
        $this->assertInstanceOf(OploverzScraperAdapter::class, $oploverzDriver);
    }

    public function test_site_settings_manager_persists_multi_scraper_configuration(): void
    {
        Livewire::test(SiteSettingsManager::class)
            ->set('scraperBaseUrl', 'https://otakudesu.cloud')
            ->set('scraperOploverzBaseUrl', 'https://oploverz.site')
            ->set('defaultScraperProvider', 'oploverz')
            ->set('siteTitle', 'Tofunime Testing')
            ->set('siteTagline', 'Anime Subtitle Indonesia')
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertSee('Pengaturan sistem & multi-sumber scraper berhasil diperbarui!');

        $this->assertEquals('https://otakudesu.cloud', SiteSetting::get('scraper_otakudesu_base_url'));
        $this->assertEquals('https://oploverz.site', SiteSetting::get('scraper_oploverz_base_url'));
        $this->assertEquals('oploverz', SiteSetting::get('default_scraper_provider'));
    }

    public function test_watch_player_automatically_skips_broken_server_and_picks_online_server(): void
    {
        $anime = Anime::create([
            'title' => 'Test Anime',
            'slug' => 'test-anime',
        ]);

        $episode = Episode::create([
            'anime_id' => $anime->id,
            'episode_number' => '1',
            'episode_order' => 1.0,
            'title' => 'Test Episode 1',
            'slug' => 'test-anime-episode-1-sub-indo',
            'aired_at' => now(),
        ]);

        // Mirror 1: Broken / Maintenance server (priority 1, default = true)
        $brokenMirror = VideoMirror::create([
            'episode_id' => $episode->id,
            'server_name' => 'Server Rusak Maintenance',
            'quality' => VideoQuality::Q720p,
            'embed_url' => 'https://broken-server.test/maintenance/video.php',
            'is_default' => true,
            'is_active' => true,
            'priority_order' => 1,
        ]);

        // Mirror 2: Dead server that returns 404 (priority 2, default = false)
        $deadMirror = VideoMirror::create([
            'episode_id' => $episode->id,
            'server_name' => 'Server Dead 404',
            'quality' => VideoQuality::Q720p,
            'embed_url' => 'https://dead-server.test/embed/404',
            'is_default' => false,
            'is_active' => true,
            'priority_order' => 2,
        ]);

        // Mirror 3: Healthy operational server (priority 3, default = false)
        $healthyMirror = VideoMirror::create([
            'episode_id' => $episode->id,
            'server_name' => 'Server Aktif Healthy',
            'quality' => VideoQuality::Q720p,
            'embed_url' => 'https://healthy-server.test/embed/ok',
            'is_default' => false,
            'is_active' => true,
            'priority_order' => 3,
        ]);

        // Mock HTTP calls for the health checks
        Http::fake([
            'https://broken-server.test/*' => Http::response('Server is undergoing maintenance', 503),
            'https://dead-server.test/*' => Http::response('File was deleted or not found', 404),
            'https://healthy-server.test/*' => Http::response('<!DOCTYPE html><html><body><video src="..."></video></body></html>', 200),
        ]);

        // Test Livewire WatchPlayer: It should NOT pick brokenMirror or deadMirror; it MUST pick healthyMirror!
        Livewire::test(WatchPlayer::class, ['episode' => $episode])
            ->assertSet('activeMirrorId', $healthyMirror->id)
            ->assertSet('currentEmbedUrl', 'https://healthy-server.test/embed/ok')
            ->assertSee('Server Siap Putar:')
            ->assertSee($healthyMirror->display_name);
    }
}
