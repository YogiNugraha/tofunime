<?php

declare(strict_types=1);

namespace App\Services\Scraper\Contracts;

interface AnimeScraperInterface
{
    /**
     * Get the unique provider identifier (e.g. 'otakudesu').
     */
    public function getProviderName(): string;

    /**
     * Scrape detailed anime information, Indonesian synopsis, and list of episodes.
     *
     * @return array<string, mixed>
     */
    public function scrapeAnimeDetails(string $url): array;

    /**
     * Scrape single episode streaming mirrors and download links.
     *
     * @return array<string, mixed>
     */
    public function scrapeEpisodeDetails(string $episodeUrl): array;

    /**
     * Scrape ongoing anime list for batch tracking.
     *
     * @return list<array{title: string, url: string, current_episode: ?string, release_day: ?string, poster_url: ?string}>
     */
    public function scrapeOngoing(int $page = 1): array;
}
