<?php

declare(strict_types=1);

namespace App\Services\Scraper;

use App\Models\SiteSetting;
use App\Services\Scraper\Adapters\OploverzScraperAdapter;
use App\Services\Scraper\Adapters\OtakudesuScraperAdapter;
use App\Services\Scraper\Contracts\AnimeScraperInterface;
use InvalidArgumentException;

class ScraperManager
{
    /**
     * @var array<string, AnimeScraperInterface>
     */
    protected array $drivers = [];

    public function __construct(
        OtakudesuScraperAdapter $otakudesu,
        OploverzScraperAdapter $oploverz
    ) {
        $this->drivers['otakudesu'] = $otakudesu;
        $this->drivers['oploverz'] = $oploverz;
    }

    /**
     * Resolve scraper adapter from URL or provider name.
     */
    public function resolve(?string $url = null, ?string $provider = null): AnimeScraperInterface
    {
        if ($provider && isset($this->drivers[$provider])) {
            return $this->drivers[$provider];
        }

        if ($url) {
            $detected = $this->detectProvider($url);
            if (isset($this->drivers[$detected])) {
                return $this->drivers[$detected];
            }
        }

        $defaultProvider = (string) SiteSetting::get('default_scraper_provider', 'otakudesu');
        if (isset($this->drivers[$defaultProvider])) {
            return $this->drivers[$defaultProvider];
        }

        return $this->drivers['otakudesu'];
    }

    /**
     * Detect provider from a given URL string.
     */
    public function detectProvider(string $url): string
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'oploverz')) {
            return 'oploverz';
        }

        if (str_contains($lower, 'otakudesu')) {
            return 'otakudesu';
        }

        return (string) SiteSetting::get('default_scraper_provider', 'otakudesu');
    }

    /**
     * Get a specific driver by name.
     */
    public function get(string $provider): AnimeScraperInterface
    {
        if (! isset($this->drivers[$provider])) {
            throw new InvalidArgumentException("Scraper provider [{$provider}] tidak terdaftar.");
        }

        return $this->drivers[$provider];
    }

    /**
     * Get all registered providers with metadata for UI.
     *
     * @return array<string, array{key: string, name: string, base_url: string, sample_url: string, status: string}>
     */
    public function getAvailableProviders(): array
    {
        return [
            'otakudesu' => [
                'key' => 'otakudesu',
                'name' => 'Otakudesu',
                'base_url' => (string) SiteSetting::get('scraper_otakudesu_base_url', 'https://otakudesu.blog'),
                'sample_url' => 'https://otakudesu.blog/anime/sousou-no-frieren-sub-indo/',
                'status' => 'Aktif (Default HTML Decoder)',
            ],
            'oploverz' => [
                'key' => 'oploverz',
                'name' => 'Oploverz',
                'base_url' => (string) SiteSetting::get('scraper_oploverz_base_url', 'https://oploverz.site'),
                'sample_url' => 'https://oploverz.site/series/frieren-beyond-journeys-end-s1',
                'status' => 'Aktif (SvelteKit Devalue JSON)',
            ],
        ];
    }
}
