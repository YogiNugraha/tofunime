<?php

declare(strict_types=1);

namespace App\Services\Scraper\Adapters;

use App\Models\SiteSetting;
use App\Services\Scraper\Contracts\AnimeScraperInterface;
use Exception;
use Illuminate\Support\Facades\Http;

class OploverzScraperAdapter implements AnimeScraperInterface
{
    /**
     * Get the provider identifier.
     */
    public function getProviderName(): string
    {
        return 'oploverz';
    }

    /**
     * Get base URL for Oploverz scraper.
     */
    public function getBaseUrl(): string
    {
        try {
            return rtrim((string) SiteSetting::get('scraper_oploverz_base_url', 'https://oploverz.site'), '/');
        } catch (\Throwable) {
            return 'https://oploverz.site';
        }
    }

    /**
     * Scrape detailed anime information and episode list from Oploverz series URL.
     *
     * @return array<string, mixed>
     */
    public function scrapeAnimeDetails(string $url): array
    {
        // Extract series slug from URL (e.g. series/frieren-beyond-journeys-end-s1)
        if (! preg_match('/series\/([^\/\?#]+)/i', $url, $m)) {
            throw new Exception("Format URL Oploverz tidak valid. Pastikan URL mengandung '/series/{slug}'. URL: {$url}");
        }

        $seriesSlug = $m[1];
        $jsonUrl = "{$this->getBaseUrl()}/series/{$seriesSlug}/__data.json";

        $json = $this->fetchSvelteKitData($jsonUrl);
        $data = $json['nodes'][2]['data'] ?? null;

        if (! is_array($data) || ! isset($data[0]['series'])) {
            throw new Exception("Gagal membaca struktur data SvelteKit Oploverz dari URL: {$jsonUrl}");
        }

        $series = $this->devalueResolve($data[0]['series'], $data);
        $rawEpisodes = isset($data[0]['episodes']) ? $this->devalueResolve($data[0]['episodes'], $data) : [];

        $title = $series['title'] ?? 'Anime Oploverz';
        $japaneseTitle = $series['japaneseTitle'] ?? null;
        $synopsis = $series['description'] ?? null;
        $posterUrl = $series['poster'] ?? null;
        $ratingScore = isset($series['score']) && is_numeric($series['score']) ? (float) $series['score'] : 0.0;
        $status = $series['status'] ?? 'Completed';
        $duration = $series['duration'] ?? null;

        $genres = [];
        if (! empty($series['genres']) && is_array($series['genres'])) {
            foreach ($series['genres'] as $g) {
                if (isset($g['name'])) {
                    $genres[] = trim($g['name']);
                }
            }
        }

        $episodes = [];
        $batchLinks = [];

        if (! empty($rawEpisodes) && is_array($rawEpisodes)) {
            foreach ($rawEpisodes as $ep) {
                $epNum = (string) ($ep['episodeNumber'] ?? '');
                if ($epNum === '') {
                    continue;
                }

                $cleanNum = ltrim($epNum, '0') ?: '0';
                $epTitle = ! empty($ep['title']) ? $ep['title'] : "{$title} Episode {$cleanNum} Subtitle Indonesia";
                $sourceUrl = "{$this->getBaseUrl()}/series/{$seriesSlug}/episode/{$epNum}";

                $episodes[] = [
                    'episode_number' => $cleanNum,
                    'episode_order' => (float) $cleanNum,
                    'title' => $epTitle,
                    'source_url' => $sourceUrl,
                ];
            }
        }

        // Sort episodes ascending by order
        usort($episodes, fn (array $a, array $b): int => $a['episode_order'] <=> $b['episode_order']);

        return [
            'title' => $title,
            'japanese_title' => $japaneseTitle,
            'rating_score' => $ratingScore,
            'producer' => null,
            'type' => 'TV',
            'status' => $status,
            'total_episodes' => count($episodes),
            'duration' => $duration,
            'aired_string' => $series['releaseDate'] ?? null,
            'studio' => $series['studio']['name'] ?? null,
            'genres' => $genres,
            'poster_url' => $posterUrl,
            'synopsis' => $synopsis,
            'episodes' => $episodes,
            'batch_links' => $batchLinks,
            'raw_metadata' => $series,
        ];
    }

    /**
     * Scrape single episode streaming mirrors and download links.
     *
     * @return array<string, mixed>
     */
    public function scrapeEpisodeDetails(string $episodeUrl): array
    {
        // Extract series slug and episode number
        if (! preg_match('/series\/([^\/\?#]+)\/episode\/([^\/\?#]+)/i', $episodeUrl, $m)) {
            // Fallback: check if series URL provided
            if (preg_match('/series\/([^\/\?#]+)/i', $episodeUrl, $mSeries)) {
                $m[1] = $mSeries[1];
                $m[2] = '1';
            } else {
                throw new Exception("URL episode Oploverz tidak valid: {$episodeUrl}");
            }
        }

        $seriesSlug = $m[1];
        $episodeNum = $m[2];
        $jsonUrl = "{$this->getBaseUrl()}/series/{$seriesSlug}/episode/{$episodeNum}/__data.json";

        $json = $this->fetchSvelteKitData($jsonUrl);
        $data = $json['nodes'][2]['data'] ?? null;

        $mirrors = [];
        $downloadLinks = [];
        $priorityOrder = 1;

        if (is_array($data)) {
            $targetEp = null;

            if (isset($data[0]['episode'])) {
                $targetEp = $this->devalueResolve($data[0]['episode'], $data);
            } elseif (isset($data[0]['episodes'])) {
                $rawEpisodes = $this->devalueResolve($data[0]['episodes'], $data);
                foreach ($rawEpisodes as $ep) {
                    if ((string) ($ep['episodeNumber'] ?? '') === (string) $episodeNum || (string) (int) ($ep['episodeNumber'] ?? 0) === (string) (int) $episodeNum) {
                        $targetEp = $ep;
                        break;
                    }
                }
            }

            if ($targetEp) {
                // 1. Streaming Mirrors
                if (! empty($targetEp['streamUrl']) && is_array($targetEp['streamUrl'])) {
                    foreach ($targetEp['streamUrl'] as $stream) {
                        $source = ! empty($stream['source']) ? ucfirst((string) $stream['source']) : 'Blogger HD';
                        $streamUrl = $stream['url'] ?? null;

                        if (! empty($streamUrl)) {
                            $mirrors[] = [
                                'server_name' => "Oploverz {$source}",
                                'quality' => '720p',
                                'embed_url' => $streamUrl,
                                'is_default' => empty($mirrors),
                                'priority_order' => $priorityOrder++,
                            ];
                        }
                    }
                }

                // 2. Download Links
                if (! empty($targetEp['downloadUrl']) && is_array($targetEp['downloadUrl'])) {
                    foreach ($targetEp['downloadUrl'] as $pkg) {
                        $resolutions = $pkg['resolutions'] ?? [];
                        foreach ($resolutions as $res) {
                            $quality = $res['quality'] ?? '720p';
                            $links = $res['download_links'] ?? [];

                            foreach ($links as $link) {
                                if (! empty($link['url'])) {
                                    $downloadLinks[] = [
                                        'quality' => $quality,
                                        'format' => 'MP4',
                                        'host_name' => ucfirst((string) ($link['host'] ?? 'Filedon')),
                                        'url' => $link['url'],
                                        'file_size' => null,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'episode_url' => $episodeUrl,
            'default_iframe' => $mirrors[0]['embed_url'] ?? null,
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
        return [];
    }

    /**
     * Fetch SvelteKit JSON data with IPv4 force to avoid Windows IPv6 connection timeouts.
     *
     * @return array<string, mixed>
     */
    protected function fetchSvelteKitData(string $jsonUrl): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept' => 'application/json',
            'Referer' => $this->getBaseUrl(),
        ])
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->timeout(20)
            ->retry(2, 500)
            ->get($jsonUrl);

        if (! $response->successful()) {
            throw new Exception("Gagal mengakses endpoint Oploverz: {$jsonUrl} (Status: {$response->status()})");
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new Exception("Response Oploverz bukan format JSON yang valid: {$jsonUrl}");
        }

        return $json;
    }

    /**
     * Cycle-safe recursive resolver for SvelteKit devalue serialized array structures.
     *
     * @param  mixed  $val
     * @param  array<int, mixed>  $data
     * @param  array<int, bool>  $visited
     * @return mixed
     */
    protected function devalueResolve($val, array $data, array &$visited = [], int $depth = 0)
    {
        if ($depth > 15) {
            return null;
        }

        if (is_int($val)) {
            if (! isset($data[$val])) {
                return null;
            }

            if (isset($visited[$val])) {
                return null;
            }

            $visited[$val] = true;
            $res = $this->devalueResolve($data[$val], $data, $visited, $depth + 1);
            unset($visited[$val]);

            return $res;
        }

        if (is_array($val)) {
            $res = [];
            foreach ($val as $k => $v) {
                $res[$k] = $this->devalueResolve($v, $data, $visited, $depth + 1);
            }

            return $res;
        }

        return $val;
    }
}
