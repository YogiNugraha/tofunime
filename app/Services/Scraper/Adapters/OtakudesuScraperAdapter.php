<?php

declare(strict_types=1);

namespace App\Services\Scraper\Adapters;

use App\Models\SiteSetting;
use App\Services\Scraper\Contracts\AnimeScraperInterface;
use App\Services\Scraper\Decoders\OtakudesuAjaxDecoder;
use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class OtakudesuScraperAdapter implements AnimeScraperInterface
{
    public function __construct(
        protected OtakudesuAjaxDecoder $decoder
    ) {}

    public function getProviderName(): string
    {
        return 'otakudesu';
    }

    /**
     * Get the dynamic base URL configured in site_settings.
     */
    public function getBaseUrl(): string
    {
        try {
            return rtrim((string) SiteSetting::get('scraper_otakudesu_base_url', 'https://otakudesu.blog'), '/');
        } catch (\Throwable) {
            return 'https://otakudesu.blog';
        }
    }

    /**
     * Scrape detailed anime information, Indonesian synopsis, and list of episodes.
     *
     * @return array<string, mixed>
     */
    public function scrapeAnimeDetails(string $url): array
    {
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html, $url);

        // 1. Poster Fallback
        $posterUrl = null;
        $posterNode = $crawler->filter('.fotoanime img');
        if ($posterNode->count() > 0) {
            $posterUrl = $posterNode->attr('src');
        }

        // 2. Metadata Box (.infozin .infozingle p)
        $metadata = [];
        $crawler->filter('.infozin .infozingle p')->each(function (Crawler $node) use (&$metadata): void {
            $text = trim($node->text());
            if (str_contains($text, ':')) {
                [$key, $val] = explode(':', $text, 2);
                $cleanKey = strtolower(trim(strip_tags($key)));
                $metadata[$cleanKey] = trim($val);
            }
        });

        // Fallback title from h1 or metadata
        $title = $metadata['judul'] ?? null;
        if (empty($title) && $crawler->filter('h1.entry-title, .infozin h1, h1')->count() > 0) {
            $title = trim($crawler->filter('h1.entry-title, .infozin h1, h1')->first()->text());
        }

        // 3. Indonesian Synopsis (.sinopc p)
        $synopsisParagraphs = [];
        $crawler->filter('.sinopc p')->each(function (Crawler $p) use (&$synopsisParagraphs): void {
            $t = trim($p->text());
            if (! empty($t)) {
                $synopsisParagraphs[] = $t;
            }
        });
        $synopsis = implode("\n\n", $synopsisParagraphs);

        // 4. Daftar Episode & Batch Links (.episodelist ul li)
        $episodes = [];
        $batchLinks = [];

        $crawler->filter('.episodelist ul li')->each(function (Crawler $li) use (&$episodes, &$batchLinks): void {
            $linkNode = $li->filter('span a');
            if ($linkNode->count() === 0) {
                return;
            }

            $href = $linkNode->attr('href');
            $text = trim($linkNode->text());

            if (empty($href)) {
                return;
            }

            // Check if batch link (/batch/, /lengkap/, or Episode range like "Episode 1 – 28")
            if (str_contains($href, '/batch/') || str_contains($href, '/lengkap/') || preg_match('/Episode\s+\d+\s*[-–]\s*\d+/i', $text)) {
                $batchLinks[] = [
                    'url' => $href,
                    'title' => $text,
                ];

                return;
            }

            // Extract episode number
            $epNum = null;
            if (preg_match('/Episode\s+([0-9]+(\.[0-9]+)?)/i', $text, $matches)) {
                $epNum = $matches[1];
            } elseif (preg_match('/([0-9]+(\.[0-9]+)?)/i', $text, $matches)) {
                $epNum = $matches[1];
            }

            if ($epNum !== null) {
                $episodes[] = [
                    'episode_number' => $epNum,
                    'episode_order' => (float) $epNum,
                    'title' => $text,
                    'source_url' => $href,
                ];
            }
        });

        // Sort episodes ascending by order
        usort($episodes, fn (array $a, array $b): int => $a['episode_order'] <=> $b['episode_order']);

        return [
            'title' => $title ?? 'Anime Otakudesu',
            'japanese_title' => $metadata['japanese'] ?? $metadata['jepang'] ?? null,
            'rating_score' => isset($metadata['skor']) && is_numeric($metadata['skor']) ? (float) $metadata['skor'] : 0.0,
            'producer' => $metadata['produser'] ?? null,
            'type' => $metadata['tipe'] ?? 'TV',
            'status' => $metadata['status'] ?? 'Ongoing',
            'total_episodes' => isset($metadata['total episode']) && is_numeric($metadata['total episode']) ? (int) $metadata['total episode'] : null,
            'duration' => $metadata['durasi'] ?? null,
            'aired_string' => $metadata['tanggal rilis'] ?? null,
            'studio' => $metadata['studio'] ?? null,
            'genres' => isset($metadata['genre']) ? array_map('trim', explode(',', $metadata['genre'])) : [],
            'poster_url' => $posterUrl,
            'synopsis' => $synopsis,
            'episodes' => $episodes,
            'batch_links' => $batchLinks,
            'raw_metadata' => $metadata,
        ];
    }

    /**
     * Scrape single episode streaming mirrors and download links.
     *
     * @return array<string, mixed>
     */
    public function scrapeEpisodeDetails(string $episodeUrl): array
    {
        $html = $this->fetchHtml($episodeUrl);
        $crawler = new Crawler($html, $episodeUrl);

        // 1. Default Iframe (#pembed iframe)
        $defaultIframe = null;
        $defaultIframeNode = $crawler->filter('#pembed iframe, .responsive-embed-stream iframe, .player-embed iframe');
        if ($defaultIframeNode->count() > 0) {
            $defaultIframe = $defaultIframeNode->attr('src');
            if ($defaultIframe && str_starts_with($defaultIframe, '//')) {
                $defaultIframe = 'https:'.$defaultIframe;
            }
        }

        // 2. Multi-Server & Multi-Resolusi (.mirrorstream ul)
        $mirrors = [];
        $priorityOrder = 1;

        if ($defaultIframe !== null && ! str_contains($defaultIframe, 'maintenance')) {
            $mirrors[] = [
                'server_name' => 'Default Stream',
                'quality' => '720p',
                'embed_url' => $defaultIframe,
                'is_default' => true,
                'priority_order' => $priorityOrder++,
            ];
        }

        // Pre-fetch nonce once for this episode page if available
        $ajaxActions = $this->decoder->extractActionsFromHtml($html);
        $ajaxUrl = $this->getBaseUrl().'/wp-admin/admin-ajax.php';
        $nonce = $this->decoder->fetchNonce($ajaxUrl, $ajaxActions['nonce_action'] ?? null);

        // Loop resolutions: ul[class*="m360p"], ul[class*="m480p"], ul[class*="m720p"], ul[class*="m1080p"]
        $crawler->filter('.mirrorstream ul')->each(function (Crawler $ul) use (&$mirrors, &$priorityOrder, $nonce, $html): void {
            $class = $ul->attr('class') ?? '';
            $quality = '720p';
            if (str_contains($class, '360')) {
                $quality = '360p';
            } elseif (str_contains($class, '480')) {
                $quality = '480p';
            } elseif (str_contains($class, '720')) {
                $quality = '720p';
            } elseif (str_contains($class, '1080')) {
                $quality = '1080p';
            }

            $ul->filter('li a')->each(function (Crawler $a) use (&$mirrors, &$priorityOrder, $quality, $nonce, $html): void {
                $serverName = trim($a->text());
                $dataContent = $a->attr('data-content');

                if (empty($dataContent)) {
                    return;
                }

                $embedUrl = $this->decoder->decodeMirror($dataContent, $nonce, $html);

                if ($embedUrl !== null) {
                    $mirrors[] = [
                        'server_name' => $serverName,
                        'quality' => $quality,
                        'embed_url' => $embedUrl,
                        'is_default' => empty($mirrors),
                        'priority_order' => $priorityOrder++,
                    ];
                }
            });
        });

        // Ensure at least one working mirror is marked default
        $hasDefault = false;
        foreach ($mirrors as $m) {
            if (! empty($m['is_default'])) {
                $hasDefault = true;
                break;
            }
        }

        if (! $hasDefault && ! empty($mirrors)) {
            $defaultIdx = 0;
            foreach ($mirrors as $idx => $m) {
                if ($m['quality'] === '720p') {
                    $defaultIdx = $idx;
                    break;
                }
            }
            $mirrors[$defaultIdx]['is_default'] = true;
        }

        // 3. Download Links (.download ul li)
        $downloadLinks = [];
        $crawler->filter('.download ul li')->each(function (Crawler $li) use (&$downloadLinks): void {
            $strongNode = $li->filter('strong');
            $qualityText = $strongNode->count() > 0 ? trim($strongNode->text()) : '720p';

            // Detect format & quality
            $quality = '720p';
            if (preg_match('/(360p|480p|720p|1080p)/i', $qualityText, $qMatch)) {
                $quality = strtolower($qMatch[1]);
            }

            $format = 'MP4';
            if (stripos($qualityText, 'mkv') !== false) {
                $format = 'MKV';
            } elseif (stripos($qualityText, 'x265') !== false || stripos($qualityText, 'hevc') !== false) {
                $format = 'x265';
            }

            // File size (<i> tag)
            $size = null;
            $sizeNode = $li->filter('i');
            if ($sizeNode->count() > 0) {
                $size = trim($sizeNode->text());
            }

            // Host links (<a href="...">HostName</a>)
            $li->filter('a')->each(function (Crawler $a) use (&$downloadLinks, $quality, $format, $size): void {
                $hostName = trim($a->text());
                $url = $a->attr('href');

                if (! empty($url) && ! empty($hostName)) {
                    $downloadLinks[] = [
                        'quality' => $quality,
                        'format' => $format,
                        'host_name' => $hostName,
                        'url' => $url,
                        'file_size' => $size,
                    ];
                }
            });
        });

        return [
            'episode_url' => $episodeUrl,
            'default_iframe' => $defaultIframe,
            'mirrors' => $mirrors,
            'download_links' => $downloadLinks,
        ];
    }

    /**
     * Scrape ongoing anime list for batch tracking.
     *
     * @return list<array{title: string, url: string, current_episode: ?string, release_day: ?string, poster_url: ?string}>
     */
    public function scrapeOngoing(int $page = 1): array
    {
        $ongoingUrl = "{$this->getBaseUrl()}/ongoing-anime/page/{$page}/";
        $html = $this->fetchHtml($ongoingUrl);
        $crawler = new Crawler($html, $ongoingUrl);

        $results = [];

        $crawler->filter('.venz ul li')->each(function (Crawler $li) use (&$results): void {
            $linkNode = $li->filter('.thumb a, a');
            if ($linkNode->count() === 0) {
                return;
            }

            $url = $linkNode->attr('href');
            $title = $li->filter('.jdlflm, h2')->count() > 0 ? trim($li->filter('.jdlflm, h2')->text()) : '';
            $epText = $li->filter('.epz')->count() > 0 ? trim($li->filter('.epz')->text()) : null;
            $dayText = $li->filter('.epztipe')->count() > 0 ? trim($li->filter('.epztipe')->text()) : null;
            $poster = $li->filter('img')->count() > 0 ? $li->filter('img')->attr('src') : null;

            if (! empty($url) && ! empty($title)) {
                $results[] = [
                    'title' => $title,
                    'url' => $url,
                    'current_episode' => $epText,
                    'release_day' => $dayText,
                    'poster_url' => $poster,
                ];
            }
        });

        return $results;
    }

    /**
     * Fetch HTML using Laravel HTTP client with browser user agent.
     */
    protected function fetchHtml(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        ])
            ->retry(3, 1000)
            ->timeout(20)
            ->get($url);

        if (! $response->successful()) {
            throw new Exception("OtakudesuScraperAdapter error [{$response->status()}] when fetching {$url}");
        }

        return $response->body();
    }
}
