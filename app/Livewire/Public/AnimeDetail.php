<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Models\Anime;
use App\Services\Seo\TofunimeSeoGenerator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AnimeDetail extends Component
{
    public Anime $anime;

    public function mount(Anime $anime): void
    {
        $this->anime = $anime->load([
            'studio',
            'season',
            'genres',
            'themes',
            'demographics',
            'people',
            'episodes',
            'downloadLinks' => fn ($q) => $q->where('is_batch', true),
        ]);
    }

    public function render(TofunimeSeoGenerator $seoGenerator)
    {
        // Similar anime recommendation query based on shared genres
        $genreIds = $this->anime->genres->pluck('id')->toArray();

        $recommendations = Anime::with('studio')
            ->where('id', '!=', $this->anime->id)
            ->whereHas('taxonomies', fn ($q) => $q->whereIn('taxonomies.id', $genreIds))
            ->orderByDesc('rating_score')
            ->take(6)
            ->get();

        $firstEpisode = $this->anime->episodes->sortBy('episode_order')->first();
        $latestEpisode = $this->anime->episodes->sortByDesc('episode_order')->first();
        $seoJsonLd = $seoGenerator->renderJsonLd($seoGenerator->generateAnimeSchema($this->anime));

        return view('livewire.public.anime-detail', compact(
            'recommendations',
            'firstEpisode',
            'latestEpisode',
            'seoJsonLd'
        ))->title("Nonton {$this->anime->title} Sub Indo Streaming & Download - Tofunime");
    }
}
