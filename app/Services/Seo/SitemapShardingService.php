<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\Person;
use App\Models\Studio;
use App\Models\Taxonomy;
use Illuminate\Http\Response;

class SitemapShardingService
{
    protected int $perPage = 1000;

    /**
     * Generate the Root Sitemap Index linking to sharded sub-sitemaps.
     */
    public function renderIndex(): Response
    {
        $animeCount = Anime::count();
        $episodeCount = Episode::count();

        $animeParts = max(1, (int) ceil($animeCount / $this->perPage));
        $episodeParts = max(1, (int) ceil($episodeCount / $this->perPage));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Static Pages
        $xml .= '<sitemap><loc>'.url('/sitemap-static.xml').'</loc><lastmod>'.now()->toAtomString().'</lastmod></sitemap>';

        // Taxonomies
        $xml .= '<sitemap><loc>'.url('/sitemap-taxonomies.xml').'</loc><lastmod>'.now()->toAtomString().'</lastmod></sitemap>';

        // Animes Parts
        for ($i = 1; $i <= $animeParts; $i++) {
            $xml .= '<sitemap><loc>'.url("/sitemap-animes-{$i}.xml").'</loc><lastmod>'.now()->toAtomString().'</lastmod></sitemap>';
        }

        // Episodes Parts
        for ($i = 1; $i <= $episodeParts; $i++) {
            $xml .= '<sitemap><loc>'.url("/sitemap-episodes-{$i}.xml").'</loc><lastmod>'.now()->toAtomString().'</lastmod></sitemap>';
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate Static URLs Sitemap.
     */
    public function renderStatic(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'hourly'],
            ['loc' => url('/anime'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => url('/ongoing'), 'priority' => '0.9', 'changefreq' => 'hourly'],
            ['loc' => url('/jadwal'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => url('/genre'), 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

        return $this->buildUrlset($urls);
    }

    /**
     * Generate Taxonomies Sitemap (Genres, Themes, Studios, People).
     */
    public function renderTaxonomies(): Response
    {
        $urls = [];

        foreach (Taxonomy::all() as $tax) {
            $prefix = $tax->type->value === 'theme' ? 'tag' : 'genre';
            $urls[] = [
                'loc' => url("/{$prefix}/{$tax->slug}"),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        foreach (Studio::all() as $studio) {
            $urls[] = [
                'loc' => url("/studio/{$studio->slug}"),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        foreach (Person::all() as $person) {
            $urls[] = [
                'loc' => url("/author/{$person->slug}"),
                'priority' => '0.5',
                'changefreq' => 'weekly',
            ];
        }

        return $this->buildUrlset($urls);
    }

    /**
     * Generate Animes Catalog Sitemap chunk.
     */
    public function renderAnimes(int $part = 1): Response
    {
        $offset = ($part - 1) * $this->perPage;
        $animes = Anime::latest('updated_at')->skip($offset)->take($this->perPage)->get();

        $urls = [];
        foreach ($animes as $anime) {
            $urls[] = [
                'loc' => url("/anime/{$anime->slug}"),
                'lastmod' => $anime->updated_at->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'daily',
            ];
        }

        return $this->buildUrlset($urls);
    }

    /**
     * Generate Episodes Watch Sitemap chunk.
     */
    public function renderEpisodes(int $part = 1): Response
    {
        $offset = ($part - 1) * $this->perPage;
        $episodes = Episode::latest('updated_at')->skip($offset)->take($this->perPage)->get();

        $urls = [];
        foreach ($episodes as $episode) {
            $urls[] = [
                'loc' => url("/nonton/{$episode->slug}"),
                'lastmod' => ($episode->aired_at ?? $episode->updated_at)->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        return $this->buildUrlset($urls);
    }

    /**
     * Helper to wrap url entries into standard sitemap XML.
     *
     * @param  list<array{loc: string, lastmod?: string, priority?: string, changefreq?: string}>  $urls
     */
    protected function buildUrlset(array $urls): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $u) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>';
            if (isset($u['lastmod'])) {
                $xml .= '<lastmod>'.$u['lastmod'].'</lastmod>';
            }
            if (isset($u['changefreq'])) {
                $xml .= '<changefreq>'.$u['changefreq'].'</changefreq>';
            }
            if (isset($u['priority'])) {
                $xml .= '<priority>'.$u['priority'].'</priority>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
