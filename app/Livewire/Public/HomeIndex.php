<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Enums\AnimeType;
use App\Models\Anime;
use App\Models\Episode;
use App\Services\Cache\TofunimeCacheService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tofunime - Nonton & Download Anime Sub Indo Kualitas HD')]
class HomeIndex extends Component
{
    public string $typeFilter = 'all';

    public function setFilter(string $type): void
    {
        $this->typeFilter = $type;
    }

    public function render(TofunimeCacheService $cache)
    {
        $featuredAnime = $cache->getFeaturedHero();
        $iconicStudios = $cache->getIconicStudios();

        $recentQuery = Episode::with('anime')->latest('aired_at');

        if ($this->typeFilter !== 'all') {
            $mappedType = AnimeType::tryFrom($this->typeFilter);
            if ($mappedType) {
                $recentQuery->whereHas('anime', fn ($q) => $q->where('type', $mappedType));
            }
        }

        $recentUpdates = $recentQuery->take(8)->get();

        $compactAnimesRow1 = Anime::with('studio')
            ->orderByDesc('rating_score')
            ->take(6)
            ->get();

        $compactAnimesRow2 = Anime::with('studio')
            ->orderByDesc('views_count')
            ->skip(1)
            ->take(6)
            ->get();

        return view('livewire.public.home-index', compact(
            'featuredAnime',
            'recentUpdates',
            'iconicStudios',
            'compactAnimesRow1',
            'compactAnimesRow2'
        ));
    }
}
