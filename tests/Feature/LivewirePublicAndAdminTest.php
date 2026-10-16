<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\AnimeManager;
use App\Livewire\Admin\BrokenReportQueue;
use App\Livewire\Admin\DashboardOverview;
use App\Livewire\Admin\EpisodeMirrorEditor;
use App\Livewire\Admin\HybridGrabberStudio;
use App\Livewire\Admin\SiteSettingsManager;
use App\Livewire\Auth\Login;
use App\Livewire\Public\AnimeCatalog;
use App\Livewire\Public\AnimeDetail;
use App\Livewire\Public\HomeIndex;
use App\Livewire\Public\ScheduleBoard;
use App\Livewire\Public\WatchPlayer;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\User;
use App\Models\VideoMirror;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewirePublicAndAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render_successfully(): void
    {
        $anime = Anime::firstOrFail();
        $episode = $anime->episodes()->firstOrFail();

        // 1. Home
        $this->get('/')
            ->assertStatus(200)
            ->assertSeeLivewire(HomeIndex::class);

        // 2. Catalog (/katalog & /anime redirect)
        $this->get('/katalog')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeCatalog::class);

        $this->get('/anime')->assertRedirect('/katalog');

        // 3. Detail
        $this->get('/anime/'.$anime->slug)
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeDetail::class)
            ->assertSee('application/ld+json', false);

        // 4. Watch Player
        $this->get('/nonton/'.$episode->slug)
            ->assertStatus(200)
            ->assertSeeLivewire(WatchPlayer::class)
            ->assertSee('Pilih Resolusi');

        // 5. Schedule Board (/jadwal-rilis & /jadwal redirect)
        $this->get('/jadwal-rilis')
            ->assertStatus(200)
            ->assertSeeLivewire(ScheduleBoard::class);

        $this->get('/jadwal')->assertRedirect('/jadwal-rilis');

        // 6. Ongoing shortcut
        $this->get('/ongoing')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeCatalog::class);

        // 7. Taxonomy shortcuts
        $this->get('/genre/action')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeCatalog::class);

        $this->get('/tag/fantasy')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeCatalog::class);

        $this->get('/studio/ufotable')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeCatalog::class);

        $this->get('/genre')->assertRedirect('/katalog');
        $this->get('/tag')->assertRedirect('/katalog');
        $this->get('/studio')->assertRedirect('/katalog');
        $this->get('/author')->assertRedirect('/katalog');

        // 8. Sitemaps
        $this->get('/sitemap.xml')->assertStatus(200)->assertHeader('Content-Type', 'application/xml');
        $this->get('/sitemap-static.xml')->assertStatus(200)->assertHeader('Content-Type', 'application/xml');
    }

    public function test_auth_login_page_renders_and_authenticates_admin(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSeeLivewire(Login::class);

        $this->get('/admin/login')->assertRedirect('/login');

        // Unauthenticated access to /admin redirects to /login
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');

        // Attempt login with invalid credentials
        Livewire::test(Login::class)
            ->set('email', 'admin@tofunime.test')
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors(['email']);

        // Attempt login with valid credentials
        Livewire::test(Login::class)
            ->set('email', 'admin@tofunime.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_watch_player_server_switching_and_broken_reporting(): void
    {
        $anime = Anime::firstOrFail();
        $episode = $anime->episodes()->firstOrFail();

        $mirror1 = VideoMirror::create([
            'episode_id' => $episode->id,
            'server_name' => 'DesuDrive',
            'quality' => '720p',
            'embed_url' => 'https://desudrive.com/embed/ep1',
            'is_default' => true,
            'priority_order' => 1,
        ]);

        $mirror2 = VideoMirror::create([
            'episode_id' => $episode->id,
            'server_name' => 'Vidhide',
            'quality' => '720p',
            'embed_url' => 'https://vidhide.com/embed/ep1',
            'is_default' => false,
            'priority_order' => 2,
        ]);

        Livewire::test(WatchPlayer::class, ['episode' => $episode])
            ->assertSet('activeMirrorId', $mirror1->id)
            ->call('setMirror', $mirror2->id)
            ->assertSet('activeMirrorId', $mirror2->id)
            ->assertSet('currentEmbedUrl', 'https://vidhide.com/embed/ep1')
            ->call('reportBrokenAndFallback')
            ->assertSet('reportedBroken', true)
            ->assertSet('activeMirrorId', $mirror1->id);

        $this->assertDatabaseHas('broken_reports', [
            'episode_id' => $episode->id,
            'video_mirror_id' => $mirror2->id,
        ]);
    }

    public function test_admin_cms_pages_render_and_hybrid_grabber_interacts(): void
    {
        $admin = User::firstOrFail();
        $this->actingAs($admin);

        // 1. Dashboard
        $this->get('/admin')
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSeeLivewire(DashboardOverview::class);

        // 2. Hybrid Grabber Studio
        $this->get('/admin/scraper')
            ->assertStatus(200)
            ->assertSeeLivewire(HybridGrabberStudio::class);

        // 3. Anime Manager
        $this->get('/admin/animes')
            ->assertStatus(200)
            ->assertSeeLivewire(AnimeManager::class);

        // 4. Reports Queue
        $this->get('/admin/reports')
            ->assertStatus(200)
            ->assertSeeLivewire(BrokenReportQueue::class);

        // 5. Settings Manager
        $this->get('/admin/settings')
            ->assertStatus(200)
            ->assertSeeLivewire(SiteSettingsManager::class);

        // 6. Episode Mirror Editor
        $episode = Episode::firstOrFail();
        $this->get('/admin/episodes/'.$episode->id)
            ->assertStatus(200)
            ->assertSeeLivewire(EpisodeMirrorEditor::class);
    }
}
