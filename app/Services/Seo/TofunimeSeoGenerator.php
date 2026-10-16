<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Anime;
use App\Models\Episode;

class TofunimeSeoGenerator
{
    /**
     * Generate Schema.org JSON-LD for Anime Details (TVSeries).
     *
     * @return array<string, mixed>
     */
    public function generateAnimeSchema(Anime $anime): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TVSeries',
            'name' => $anime->title,
            'url' => url("/anime/{$anime->slug}"),
            'description' => $anime->synopsis ?? "Nonton anime {$anime->title} subtitle Indonesia online gratis.",
            'image' => $anime->poster_url,
            'numberOfEpisodes' => $anime->total_episodes ?? $anime->episodes()->count(),
        ];

        if (! empty($anime->english_title)) {
            $schema['alternateName'] = $anime->english_title;
        }

        if ($anime->studio) {
            $schema['productionCompany'] = [
                '@type' => 'Organization',
                'name' => $anime->studio->name,
                'url' => url("/studio/{$anime->studio->slug}"),
            ];
        }

        $genres = $anime->genres->pluck('name')->toArray();
        if (! empty($genres)) {
            $schema['genre'] = $genres;
        }

        $authors = [];
        foreach ($anime->people as $person) {
            $authors[] = [
                '@type' => 'Person',
                'name' => $person->name,
                'url' => url("/author/{$person->slug}"),
                'jobTitle' => $person->pivot->role ?? 'Original Creator',
            ];
        }
        if (! empty($authors)) {
            $schema['author'] = $authors;
        }

        if ((float) $anime->rating_score > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $anime->rating_score, 2),
                'bestRating' => '10.0',
                'worstRating' => '1.0',
                'ratingCount' => max(1, $anime->scored_by),
            ];
        }

        return $schema;
    }

    /**
     * Generate Schema.org JSON-LD for Episode Watch Page (TVEpisode & VideoObject).
     *
     * @return array<string, mixed>
     */
    public function generateWatchSchema(Episode $episode): array
    {
        $anime = $episode->anime;
        $defaultMirror = $episode->activeMirrors->firstWhere('is_default', true) ?? $episode->activeMirrors->first();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => ['TVEpisode', 'VideoObject'],
            'name' => "Nonton {$anime->title} Episode {$episode->episode_number} Sub Indo",
            'episodeNumber' => (string) $episode->episode_number,
            'partOfSeries' => [
                '@type' => 'TVSeries',
                'name' => $anime->title,
                'url' => url("/anime/{$anime->slug}"),
            ],
            'url' => url("/nonton/{$episode->slug}"),
            'thumbnailUrl' => $anime->poster_url,
            'uploadDate' => ($episode->aired_at ?? $episode->created_at)->toIso8601String(),
            'description' => "Streaming dan download {$anime->title} Episode {$episode->episode_number} Sub Indo kualitas 360p, 480p, 720p, 1080p.",
        ];

        if ($defaultMirror && ! empty($defaultMirror->embed_url)) {
            $schema['embedUrl'] = $defaultMirror->embed_url;
        }

        return $schema;
    }

    /**
     * Convert array schema to JSON-LD script tag.
     *
     * @param  array<string, mixed>  $schema
     */
    public function renderJsonLd(array $schema): string
    {
        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }
}
