<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\TaxonomyType;
use App\Models\Anime;
use App\Models\Taxonomy;
use App\Services\Metadata\AnimeMetadataService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Manajemen Katalog Anime - Tofunime CMS')]
class AnimeManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $genreFilter = '';

    #[Url]
    public string $sortBy = 'latest';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingGenreFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'genreFilter', 'sortBy']);
        $this->resetPage();
    }

    public function toggleFeatured(int $animeId): void
    {
        $anime = Anime::find($animeId);
        if ($anime) {
            $anime->update(['is_featured' => ! $anime->is_featured]);
            app(TofunimeCacheService::class)->flushHomeAndScheduleCache();
            session()->flash('message', "Status featured anime '{$anime->title}' berhasil diperbarui.");
        }
    }

    public function quickSyncMal(int $animeId, AnimeMetadataService $metadataService): void
    {
        $anime = Anime::find($animeId);
        if (! $anime) {
            return;
        }

        try {
            $query = ! empty($anime->mal_id) ? (string) $anime->mal_id : $anime->title;
            $metadata = $metadataService->fetchMetadata($query);
            $metadataService->syncToAnime($anime, $metadata);

            session()->flash('message', "Sukses! Metadata anime '{$anime->title}' berhasil disinkronisasi dari MyAnimeList/AniList.");
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menarik metadata: '.$e->getMessage());
        }
    }

    public function deleteAnime(int $animeId): void
    {
        $anime = Anime::find($animeId);
        if ($anime) {
            $anime->delete();
            session()->flash('message', 'Anime berhasil dihapus.');
        }
    }

    public function render()
    {
        $query = Anime::with(['studio', 'episodes', 'genres'])->withCount('episodes');

        if (! empty($this->search)) {
            $q = trim($this->search);
            $query->where(function ($sub) use ($q): void {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('english_title', 'like', "%{$q}%")
                    ->orWhere('japanese_title', 'like', "%{$q}%");
            });
        }

        if (! empty($this->statusFilter)) {
            $statusEnum = AnimeStatus::tryFrom($this->statusFilter);
            if ($statusEnum) {
                $query->where('status', $statusEnum);
            }
        }

        if (! empty($this->typeFilter)) {
            $typeEnum = AnimeType::tryFrom($this->typeFilter);
            if ($typeEnum) {
                $query->where('type', $typeEnum);
            }
        }

        if (! empty($this->genreFilter)) {
            $query->whereHas('taxonomies', function ($sub): void {
                $sub->where('taxonomies.name', $this->genreFilter);
            });
        }

        match ($this->sortBy) {
            'rating' => $query->orderByDesc('rating_score'),
            'popular' => $query->orderByDesc('views_count'),
            'title_asc' => $query->orderBy('title'),
            'episodes_desc' => $query->orderByDesc('episodes_count'),
            default => $query->latest('id'),
        };

        $animes = $query->paginate(15);
        $totalAllAnimes = Anime::count();
        $availableGenres = Taxonomy::where('type', TaxonomyType::Genre)->orderBy('name')->pluck('name');
        $availableTypes = AnimeType::cases();
        $availableStatuses = AnimeStatus::cases();

        $hasActiveFilters = ! empty($this->search)
            || ! empty($this->statusFilter)
            || ! empty($this->typeFilter)
            || ! empty($this->genreFilter)
            || $this->sortBy !== 'latest';

        return view('livewire.admin.anime-manager', compact(
            'animes',
            'totalAllAnimes',
            'availableGenres',
            'availableTypes',
            'availableStatuses',
            'hasActiveFilters'
        ));
    }
}
