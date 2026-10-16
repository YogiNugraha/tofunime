<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Anime;
use App\Models\ScraperSource;
use App\Services\Jikan\JikanApiService;
use App\Services\Scraper\Adapters\OploverzScraperAdapter;
use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use App\Services\Scraper\Decoders\OtakudesuAjaxDecoder;
use App\Services\Scraper\HybridAnimeImporter;
use App\Services\Scraper\ScraperManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HybridAnimeImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hybrid_importer_integrates_otakudesu_and_jikan(): void
    {
        $mockAnimeHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div class="fotoanime">
        <img src="https://otakudesu.cloud/wp-content/uploads/2026/01/sample.jpg" />
    </div>
    <div class="infozin">
        <div class="infozingle">
            <p><span><b>Judul</b>: Mushoku Tensei Season 2 Sub Indo</span></p>
            <p><span><b>Status</b>: Ongoing</span></p>
            <p><span><b>Durasi</b>: 24 Menit</span></p>
            <p><span><b>Genre</b>: Fantasy, Adventure</span></p>
        </div>
    </div>
    <div class="sinopc">
        <p>Petualangan Rudeus di Akademi Sihir Ranoa dalam bahasa Indonesia.</p>
    </div>
    <div class="episodelist">
        <ul>
            <li><span><a href="https://otakudesu.blog/episode/mushoku-s2-episode-1-sub-indo/">Episode 1</a></span></li>
            <li><span><a href="https://otakudesu.blog/episode/mushoku-s2-episode-2-sub-indo/">Episode 2</a></span></li>
        </ul>
    </div>
</body>
</html>
HTML;

        Http::fake([
            'otakudesu.blog/anime/*' => Http::response($mockAnimeHtml, 200),
            'otakudesu.blog/episode/*' => Http::response('<div id="pembed"><iframe src="https://desudrive.com/embed/ep1"></iframe></div>', 200),
            'api.jikan.moe/v4/anime?*' => Http::response([
                'data' => [
                    [
                        'mal_id' => 51179,
                        'title' => 'Mushoku Tensei II: Isekai Ittara Honki Dasu',
                    ],
                ],
            ], 200),
            'api.jikan.moe/v4/anime/51179/full' => Http::response([
                'data' => [
                    'mal_id' => 51179,
                    'title' => 'Mushoku Tensei II: Isekai Ittara Honki Dasu',
                    'title_english' => 'Mushoku Tensei: Jobless Reincarnation Season 2',
                    'type' => 'TV',
                    'status' => 'Currently Airing',
                    'score' => 8.72,
                    'scored_by' => 300000,
                    'studios' => [
                        ['mal_id' => 1993, 'name' => 'Studio Bind'],
                    ],
                    'genres' => [
                        ['mal_id' => 10, 'name' => 'Fantasy'],
                    ],
                    'themes' => [
                        ['mal_id' => 62, 'name' => 'Isekai'],
                    ],
                ],
            ], 200),
            'api.jikan.moe/v4/anime/51179/staff' => Http::response([
                'data' => [
                    [
                        'person' => ['mal_id' => 999, 'name' => 'Rifujin na Magonote', 'images' => ['jpg' => ['image_url' => null]]],
                        'positions' => ['Original Creator'],
                    ],
                ],
            ], 200),
        ]);

        $decoder = new OtakudesuAjaxDecoder;
        $otakudesu = new OtakudesuScraperAdapter($decoder);
        $oploverz = new OploverzScraperAdapter;
        $scraperManager = new ScraperManager($otakudesu, $oploverz);
        $jikan = new JikanApiService;
        $importer = new HybridAnimeImporter($jikan, $scraperManager);

        $anime = $importer->import('https://otakudesu.blog/anime/mushoku-s2-sub-indo/', null, true);

        $this->assertInstanceOf(Anime::class, $anime);
        $this->assertEquals(51179, $anime->mal_id);
        $this->assertEquals('Mushoku Tensei: Jobless Reincarnation Season 2', $anime->english_title);
        $this->assertEquals('Studio Bind', $anime->studio?->name);
        $this->assertEquals('Petualangan Rudeus di Akademi Sihir Ranoa dalam bahasa Indonesia.', $anime->synopsis);

        // Check episodes created
        $this->assertCount(2, $anime->episodes);

        // Check ScraperSource linked
        $source = ScraperSource::where('anime_id', $anime->id)->first();
        $this->assertNotNull($source);
        $this->assertEquals('https://otakudesu.blog/anime/mushoku-s2-sub-indo/', $source->source_anime_url);

        // Check staff linked
        $this->assertTrue($anime->people()->where('name', 'Rifujin na Magonote')->exists());
    }

    public function test_artisan_grab_command_executes_successfully(): void
    {
        $mockAnimeHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div class="fotoanime"><img src="https://otakudesu.cloud/sample.jpg" /></div>
    <div class="infozin"><div class="infozingle"><p><span><b>Judul</b>: Test Anime</span></p></div></div>
    <div class="episodelist"><ul><li><span><a href="https://otakudesu.blog/episode/test-ep-1/">Ep 1</a></span></li></ul></div>
</body>
</html>
HTML;

        Http::fake([
            'otakudesu.blog/anime/*' => Http::response($mockAnimeHtml, 200),
            'api.jikan.moe/*' => Http::response(['data' => []], 200),
        ]);

        $this->artisan('tofunime:grab', [
            'url' => 'https://otakudesu.blog/anime/test-anime-sub-indo/',
        ])
            ->expectsOutputToContain('TOFUNIME HYBRID GRABBER')
            ->expectsOutputToContain('Anime berhasil diimpor ke dalam katalog Tofunime!')
            ->assertExitCode(0);
    }
}
