<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use App\Services\Scraper\Decoders\OtakudesuAjaxDecoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtakudesuScraperAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrape_anime_details_parses_otakudesu_dom(): void
    {
        $mockHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div class="fotoanime">
        <img src="https://otakudesu.cloud/wp-content/uploads/2026/01/tensei-deshita.jpg" />
    </div>
    <div class="infozin">
        <div class="infozingle">
            <p><span><b>Judul</b>: Tensei shitara Ken Deshita Season 2</span></p>
            <p><span><b>Japanese</b>: 転生したら剣でした 2</span></p>
            <p><span><b>Skor</b>: 8.10</span></p>
            <p><span><b>Produser</b>: NBCUniversal</span></p>
            <p><span><b>Tipe</b>: TV</span></p>
            <p><span><b>Status</b>: Ongoing</span></p>
            <p><span><b>Total Episode</b>: 12</span></p>
            <p><span><b>Durasi</b>: 24 Menit</span></p>
            <p><span><b>Tanggal Rilis</b>: Oct 2026</span></p>
            <p><span><b>Studio</b>: C2C</span></p>
            <p><span><b>Genre</b>: Action, Fantasy, Isekai</span></p>
        </div>
    </div>
    <div class="sinopc">
        <p>Fran dan Guru melanjutkan petualangan mereka di benua baru.</p>
        <p>Pertarungan melawan monster elit semakin seru.</p>
    </div>
    <div class="episodelist">
        <ul>
            <li>
                <span>
                    <a href="https://otakudesu.blog/episode/tensei-deshita-s2-episode-1-sub-indo/">Tensei shitara Ken Deshita S2 Episode 1 Sub Indo</a>
                </span>
            </li>
            <li>
                <span>
                    <a href="https://otakudesu.blog/episode/tensei-deshita-s2-episode-2-sub-indo/">Tensei shitara Ken Deshita S2 Episode 2 Sub Indo</a>
                </span>
            </li>
            <li>
                <span>
                    <a href="https://otakudesu.blog/batch/tensei-deshita-s2-batch-sub-indo/">Tensei shitara Ken Deshita S2 Batch Sub Indo</a>
                </span>
            </li>
        </ul>
    </div>
</body>
</html>
HTML;

        Http::fake([
            'otakudesu.blog/anime/*' => Http::response($mockHtml, 200),
        ]);

        $decoder = new OtakudesuAjaxDecoder;
        $adapter = new OtakudesuScraperAdapter($decoder);

        $result = $adapter->scrapeAnimeDetails('https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/');

        $this->assertEquals('Tensei shitara Ken Deshita Season 2', $result['title']);
        $this->assertEquals(8.1, $result['rating_score']);
        $this->assertEquals('https://otakudesu.cloud/wp-content/uploads/2026/01/tensei-deshita.jpg', $result['poster_url']);
        $this->assertStringContainsString('Fran dan Guru melanjutkan petualangan', $result['synopsis']);

        // Assert episodes
        $this->assertCount(2, $result['episodes']);
        $this->assertEquals('1', $result['episodes'][0]['episode_number']);
        $this->assertEquals('2', $result['episodes'][1]['episode_number']);

        // Assert batch link separated
        $this->assertCount(1, $result['batch_links']);
    }

    public function test_scrape_episode_details_parses_default_iframe_and_download_links(): void
    {
        $mockEpisodeHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div id="pembed">
        <iframe src="https://desudrive.com/embed/abc1234"></iframe>
    </div>
    <div class="download">
        <ul>
            <li>
                <strong>Mp4 360p</strong>
                <i>35 MB</i>
                <a href="https://mega.nz/file/1">Mega</a>
                <a href="https://krakens.cc/file/1">Kraken</a>
            </li>
            <li>
                <strong>Mp4 720p</strong>
                <i>110 MB</i>
                <a href="https://mega.nz/file/2">Mega</a>
                <a href="https://krakens.cc/file/2">Kraken</a>
            </li>
        </ul>
    </div>
</body>
</html>
HTML;

        Http::fake([
            'otakudesu.blog/episode/*' => Http::response($mockEpisodeHtml, 200),
        ]);

        $decoder = new OtakudesuAjaxDecoder;
        $adapter = new OtakudesuScraperAdapter($decoder);

        $result = $adapter->scrapeEpisodeDetails('https://otakudesu.blog/episode/tensei-deshita-s2-episode-1-sub-indo/');

        $this->assertEquals('https://desudrive.com/embed/abc1234', $result['default_iframe']);
        $this->assertCount(1, $result['mirrors']);
        $this->assertEquals('Default Stream', $result['mirrors'][0]['server_name']);
        $this->assertTrue($result['mirrors'][0]['is_default']);

        // Assert download links
        $this->assertCount(4, $result['download_links']);
        $this->assertEquals('360p', $result['download_links'][0]['quality']);
        $this->assertEquals('35 MB', $result['download_links'][0]['file_size']);
        $this->assertEquals('Mega', $result['download_links'][0]['host_name']);
    }

    public function test_ajax_decoder_extracts_iframe_src_from_base64_response(): void
    {
        $decoder = new OtakudesuAjaxDecoder;
        $encodedIframe = base64_encode('<iframe src="https://vidhide.com/embed/sample999" width="100%" height="100%"></iframe>');

        Http::fake([
            '*/admin-ajax.php' => Http::sequence()
                ->push(['data' => 'mock_nonce_12345'], 200)
                ->push($encodedIframe, 200),
        ]);

        $dataContent = base64_encode(json_encode([
            'id' => 1234,
            'i' => 0,
            'q' => '720p',
        ]));

        $url = $decoder->decodeMirror($dataContent);

        $this->assertEquals('https://vidhide.com/embed/sample999', $url);
    }
}
