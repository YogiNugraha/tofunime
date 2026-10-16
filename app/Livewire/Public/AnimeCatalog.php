<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Models\Anime;
use App\Models\Taxonomy;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Katalog Anime Lengkap - Tofunime')]
class AnimeCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $genre = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $sort = 'latest';

    #[Url]
    public string $studio = '';

    #[Url]
    public string $author = '';

    public function mount(
        ?string $genre = null,
        ?string $status = null,
        ?string $type = null,
        ?string $studio = null,
        ?string $author = null,
        ?string $tag = null
    ): void {
        if (request()->routeIs('anime.ongoing')) {
            $this->status = 'Ongoing';
        }

        $genreVal = $genre ?? $tag;
        if ($genreVal !== null && empty($this->genre)) {
            $this->genre = $genreVal;
        }

        if ($status !== null && empty($this->status)) {
            $this->status = $status;
        }

        if ($type !== null && empty($this->type)) {
            $this->type = $type;
        }

        if ($studio !== null && empty($this->studio)) {
            $this->studio = $studio;
        }

        if ($author !== null && empty($this->author)) {
            $this->author = $author;
        }
    }

    public function updatingGenre(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['genre', 'status', 'type', 'sort', 'studio', 'author']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Anime::with(['studio', 'genres']);

        if (! empty($this->genre)) {
            $query->whereHas('taxonomies', fn ($q) => $q->where('slug', $this->genre));
        }

        if (! empty($this->studio)) {
            $query->whereHas('studio', fn ($q) => $q->where('slug', $this->studio));
        }

        if (! empty($this->author)) {
            $query->whereHas('people', fn ($q) => $q->where('slug', $this->author));
        }

        if (! empty($this->status)) {
            $statusEnum = AnimeStatus::tryFrom($this->status);
            if ($statusEnum) {
                $query->where('status', $statusEnum);
            }
        }

        if (! empty($this->type)) {
            $typeEnum = AnimeType::tryFrom($this->type);
            if ($typeEnum) {
                $query->where('type', $typeEnum);
            }
        }

        match ($this->sort) {
            'rating' => $query->orderByDesc('rating_score'),
            'popular' => $query->orderByDesc('views_count'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('latest_episode_at')->orderByDesc('id'),
        };

        $animes = $query->paginate(24);
        $taxonomies = Taxonomy::orderBy('name')->get();

        return view('livewire.public.anime-catalog', compact('animes', 'taxonomies'));
    }
}
