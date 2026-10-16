<?php

declare(strict_types=1);

use App\Http\Controllers\StreamPlayerController;
use App\Livewire\Admin\AnimeEditor;
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
use App\Services\Seo\SitemapShardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Tofunime Routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomeIndex::class)->name('home');
Route::get('/katalog', AnimeCatalog::class)->name('anime.catalog');
Route::get('/anime', fn () => redirect()->route('anime.catalog'));
Route::get('/anime/{anime:slug}', AnimeDetail::class)->name('anime.detail');
Route::get('/nonton/{episode:slug}', WatchPlayer::class)->name('anime.watch');
Route::get('/stream/player/{mirror}', [StreamPlayerController::class, 'player'])->name('stream.player');
Route::get('/jadwal-rilis', ScheduleBoard::class)->name('anime.schedule');
Route::get('/jadwal', fn () => redirect()->route('anime.schedule'));

// Ongoing shortcut
Route::get('/ongoing', AnimeCatalog::class)->name('anime.ongoing');

// Taxonomies shortcuts
Route::get('/genre/{genre}', AnimeCatalog::class)->name('anime.genre');
Route::get('/tag/{tag}', AnimeCatalog::class)->name('anime.tag');
Route::get('/studio/{studio}', AnimeCatalog::class)->name('anime.studio');
Route::get('/author/{author}', AnimeCatalog::class)->name('anime.author');

// Redirect base taxonomy routes to catalog
Route::get('/genre', fn () => redirect()->route('anime.catalog'));
Route::get('/tag', fn () => redirect()->route('anime.catalog'));
Route::get('/studio', fn () => redirect()->route('anime.catalog'));
Route::get('/author', fn () => redirect()->route('anime.catalog'));

/*
|--------------------------------------------------------------------------
| XML Sitemaps
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', fn (SitemapShardingService $sitemap) => $sitemap->renderIndex());
Route::get('/sitemap-static.xml', fn (SitemapShardingService $sitemap) => $sitemap->renderStatic());
Route::get('/sitemap-taxonomies.xml', fn (SitemapShardingService $sitemap) => $sitemap->renderTaxonomies());
Route::get('/sitemap-animes-{part}.xml', fn (int $part, SitemapShardingService $sitemap) => $sitemap->renderAnimes($part));
Route::get('/sitemap-episodes-{part}.xml', fn (int $part, SitemapShardingService $sitemap) => $sitemap->renderEpisodes($part));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', Login::class)->name('login');
Route::get('/admin/login', fn () => redirect()->route('login'));
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Admin CMS Routes (/admin/*)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function (): void {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/scraper', HybridGrabberStudio::class)->name('scraper');
    Route::get('/animes', AnimeManager::class)->name('animes');
    Route::get('/animes/create', fn () => redirect()->route('admin.scraper'))->name('animes.create');
    Route::get('/animes/{anime}/edit', AnimeEditor::class)->name('animes.edit');
    Route::get('/episodes/{episode}', EpisodeMirrorEditor::class)->name('episodes.edit');
    Route::get('/reports', BrokenReportQueue::class)->name('reports');
    Route::get('/settings', SiteSettingsManager::class)->name('settings');
});
