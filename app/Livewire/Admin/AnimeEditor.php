<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\ReleaseDay;
use App\Models\Anime;
use App\Services\Metadata\AnimeMetadataService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Editor Anime - Tofunime CMS')]
class AnimeEditor extends Component
{
    public ?Anime $anime = null;

    public bool $isEditing = false;

    // Auto-fill query/URL input
    public string $malQueryOrUrl = '';

    public string $autoFillMessage = '';

    public string $autoFillType = 'success';

    // Save status notification
    public string $saveMessage = '';

    public string $saveType = 'success';

    // Form fields
    public string $title = '';

    public string $slug = '';

    public ?string $japanese_title = '';

    public ?string $english_title = '';

    public ?string $synonyms_text = '';

    public ?string $synopsis = '';

    public ?string $background_info = '';

    public string $type = 'TV';

    public string $status = 'Ongoing';

    public ?string $release_day = '';

    public ?int $total_episodes = null;

    public ?string $duration = '24 min';

    public ?int $release_year = null;

    public ?string $aired_string = '';

    public ?string $rating_score = '0.00';

    public ?int $scored_by = null;

    public ?int $rank = null;

    public ?string $age_rating = '';

    public ?string $source_material = 'Original';

    public ?int $mal_id = null;

    public ?string $poster_url = '';

    public ?string $banner_url = '';

    public ?string $trailer_youtube_id = '';

    public ?string $studio_name = '';

    public ?string $season_name = '';

    public ?string $season_quarter = '';

    /**
     * @var list<string>
     */
    public array $selectedGenres = [];

    /**
     * @var list<string>
     */
    public array $selectedThemes = [];

    public string $newGenre = '';

    public string $newTheme = '';

    /**
     * @var list<array{name: string, role: string, image_url: ?string}>
     */
    public array $staffList = [];

    public string $newStaffName = '';

    public string $newStaffRole = 'Original Story';

    public bool $is_featured = false;

    public bool $is_recommended = false;

    public function mount(?Anime $anime = null): void
    {
        if ($anime && $anime->exists) {
            $this->anime = $anime->load(['studio', 'season', 'taxonomies', 'people', 'scraperSources', 'episodes.mirrors']);
            $this->isEditing = true;
            $this->loadFromModel();
        } else {
            $this->isEditing = false;
        }
    }

    /**
     * Populate form fields from existing Anime model.
     */
    protected function loadFromModel(): void
    {
        if (! $this->anime) {
            return;
        }

        $this->title = (string) $this->anime->title;
        $this->slug = (string) $this->anime->slug;
        $this->japanese_title = $this->anime->japanese_title;
        $this->english_title = $this->anime->english_title;
        $this->synonyms_text = is_array($this->anime->synonyms) ? implode(', ', $this->anime->synonyms) : '';
        $this->synopsis = $this->anime->synopsis;
        $this->background_info = $this->anime->background_info;

        $this->type = $this->anime->type?->value ?? 'TV';
        $this->status = $this->anime->status?->value ?? 'Ongoing';
        $this->release_day = $this->anime->release_day?->value ?? '';
        $this->total_episodes = $this->anime->total_episodes;
        $this->duration = $this->anime->duration;
        $this->release_year = $this->anime->release_year;
        $this->aired_string = $this->anime->aired_string;

        $this->rating_score = (string) $this->anime->rating_score;
        $this->scored_by = $this->anime->scored_by;
        $this->rank = $this->anime->rank;
        $this->age_rating = $this->anime->age_rating;
        $this->source_material = $this->anime->source_material ?? 'Original';

        $this->mal_id = $this->anime->mal_id;
        $this->poster_url = $this->anime->poster_url;
        $this->banner_url = $this->anime->banner_url;
        $this->trailer_youtube_id = $this->anime->trailer_youtube_id;

        $this->studio_name = $this->anime->studio?->name ?? '';
        $this->season_name = $this->anime->season?->name ?? '';

        $this->selectedGenres = $this->anime->genres->pluck('name')->toArray();
        $this->selectedThemes = $this->anime->themes->pluck('name')->toArray();

        $this->staffList = $this->anime->people->map(fn ($p): array => [
            'name' => (string) $p->name,
            'role' => (string) ($p->pivot->role ?? 'Original Creator'),
            'image_url' => $p->image_url,
        ])->toArray();

        $this->is_featured = (bool) $this->anime->is_featured;
        $this->is_recommended = (bool) $this->anime->is_recommended;
    }

    /**
     * Auto-generate slug when title is modified.
     */
    public function updatedTitle(string $value): void
    {
        if (! $this->isEditing || empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    /**
     * 1-Click Auto-Fill data from MyAnimeList/AniList using AnimeMetadataService.
     */
    public function fetchFromMal(AnimeMetadataService $metadataService): void
    {
        $this->autoFillMessage = '';

        if (empty(trim($this->malQueryOrUrl))) {
            $this->autoFillType = 'error';
            $this->autoFillMessage = 'Silakan masukkan URL MyAnimeList, ID MyAnimeList, atau judul anime.';

            return;
        }

        try {
            $data = $metadataService->fetchMetadata($this->malQueryOrUrl);

            // Autofill properties
            $this->title = $data['title'];

            // Preserve established slug if editing an existing anime, otherwise generate unique slug
            if (! $this->isEditing || empty($this->slug)) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $counter = 2;
                while (Anime::where('slug', $slug)->where('id', '!=', $this->anime?->id ?? 0)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $this->slug = $slug;
            }

            $this->english_title = $data['english_title'] ?? $this->english_title;
            $this->japanese_title = $data['japanese_title'] ?? $this->japanese_title;
            $this->synonyms_text = ! empty($data['synonyms']) ? implode(', ', $data['synonyms']) : $this->synonyms_text;

            // Retain Indonesian synopsis if already written, otherwise populate with metadata synopsis
            if (empty(trim((string) $this->synopsis)) && ! empty($data['synopsis'])) {
                $this->synopsis = $data['synopsis'];
            }

            $this->poster_url = $data['poster_url'] ?? $this->poster_url;
            $this->banner_url = $data['banner_url'] ?? $this->banner_url;
            $this->trailer_youtube_id = $data['trailer_youtube_id'] ?? $this->trailer_youtube_id;

            $this->type = $data['type'] ?? $this->type;
            $this->status = $data['status'] ?? $this->status;
            $this->total_episodes = $data['total_episodes'] ?? $this->total_episodes;
            $this->duration = $data['duration'] ?? $this->duration;
            $this->release_year = $data['release_year'] ?? $this->release_year;
            $this->aired_string = $data['aired_string'] ?? $this->aired_string;

            $this->rating_score = ! empty($data['rating_score']) ? number_format((float) $data['rating_score'], 2, '.', '') : $this->rating_score;
            $this->scored_by = $data['scored_by'] ?? $this->scored_by;
            $this->source_material = $data['source_material'] ?? $this->source_material;
            $this->mal_id = $data['mal_id'] ?? $this->mal_id;

            $this->studio_name = $data['studio_name'] ?? $this->studio_name;
            $this->season_name = $data['season_name'] ?? $this->season_name;
            $this->season_quarter = $data['season_quarter'] ?? $this->season_quarter;

            // Merge taxonomies
            if (! empty($data['genres'])) {
                $this->selectedGenres = array_values(array_unique(array_merge($this->selectedGenres, $data['genres'])));
            }
            if (! empty($data['themes'])) {
                $this->selectedThemes = array_values(array_unique(array_merge($this->selectedThemes, $data['themes'])));
            }

            // Merge staff
            if (! empty($data['staff'])) {
                $existingStaffKeys = array_map(fn ($s) => $s['name'].'|'.$s['role'], $this->staffList);
                foreach ($data['staff'] as $staffItem) {
                    $key = $staffItem['name'].'|'.$staffItem['role'];
                    if (! in_array($key, $existingStaffKeys, true)) {
                        $this->staffList[] = $staffItem;
                        $existingStaffKeys[] = $key;
                    }
                }
            }

            $this->autoFillType = 'success';
            $this->autoFillMessage = "Berhasil menarik data untuk '{$data['title']}'! Semua field formulir telah terisi otomatis.";
        } catch (Exception $e) {
            $this->autoFillType = 'error';
            $this->autoFillMessage = 'Gagal mengambil data: '.$e->getMessage();
        }
    }

    public function addGenre(): void
    {
        $val = trim($this->newGenre);
        if (! empty($val) && ! in_array($val, $this->selectedGenres, true)) {
            $this->selectedGenres[] = $val;
            $this->newGenre = '';
        }
    }

    public function removeGenre(string $genre): void
    {
        $this->selectedGenres = array_values(array_filter($this->selectedGenres, fn ($g) => $g !== $genre));
    }

    public function addTheme(): void
    {
        $val = trim($this->newTheme);
        if (! empty($val) && ! in_array($val, $this->selectedThemes, true)) {
            $this->selectedThemes[] = $val;
            $this->newTheme = '';
        }
    }

    public function removeTheme(string $theme): void
    {
        $this->selectedThemes = array_values(array_filter($this->selectedThemes, fn ($t) => $t !== $theme));
    }

    public function addStaff(): void
    {
        $name = trim($this->newStaffName);
        if (! empty($name)) {
            $this->staffList[] = [
                'name' => $name,
                'role' => $this->newStaffRole ?: 'Original Story',
                'image_url' => null,
            ];
            $this->newStaffName = '';
        }
    }

    public function removeStaff(int $index): void
    {
        unset($this->staffList[$index]);
        $this->staffList = array_values($this->staffList);
    }

    /**
     * Save Anime and sync all relations.
     */
    public function save(?AnimeMetadataService $metadataService = null): void
    {
        $metadataService ??= app(AnimeMetadataService::class);
        $this->saveMessage = '';

        // Normalize empty string values for numeric/nullable fields
        if ($this->rating_score === '' || $this->rating_score === null) {
            $this->rating_score = null;
        }
        if ($this->total_episodes === '' || $this->total_episodes === null) {
            $this->total_episodes = null;
        }
        if ($this->release_year === '' || $this->release_year === null) {
            $this->release_year = null;
        }
        if ($this->mal_id === '' || $this->mal_id === null) {
            $this->mal_id = null;
        }

        // Auto-resolve slug collision if taken by another anime
        if (Anime::where('slug', $this->slug)->where('id', '!=', $this->anime?->id ?? 0)->exists()) {
            if ($this->isEditing && $this->anime && $this->anime->slug && ! Anime::where('slug', $this->anime->slug)->where('id', '!=', $this->anime->id)->exists()) {
                $this->slug = $this->anime->slug;
            } else {
                $baseSlug = $this->slug;
                $counter = 2;
                while (Anime::where('slug', "{$baseSlug}-{$counter}")->where('id', '!=', $this->anime?->id ?? 0)->exists()) {
                    $counter++;
                }
                $this->slug = "{$baseSlug}-{$counter}";
            }
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('animes', 'slug')->ignore($this->anime?->id)],
            'type' => ['required', 'string'],
            'status' => ['required', 'string'],
            'rating_score' => ['nullable', 'numeric', 'between:0,10'],
        ], [
            'title.required' => 'Judul utama anime wajib diisi.',
            'slug.required' => 'Slug URL wajib diisi.',
            'slug.unique' => 'Slug URL ini sudah digunakan oleh anime lain.',
            'type.required' => 'Tipe anime wajib dipilih.',
            'status.required' => 'Status anime wajib dipilih.',
            'rating_score.numeric' => 'Skor rating harus berupa angka.',
            'rating_score.between' => 'Skor rating harus berada di antara 0 dan 10.',
        ]);

        $synonymsArray = array_values(array_filter(array_map('trim', explode(',', (string) $this->synonyms_text))));

        $payload = [
            'mal_id' => $this->mal_id ? (int) $this->mal_id : null,
            'title' => $this->title,
            'slug' => $this->slug,
            'english_title' => $this->english_title ?: null,
            'japanese_title' => $this->japanese_title ?: null,
            'synonyms' => $synonymsArray,
            'synopsis' => $this->synopsis ?: null,
            'background_info' => $this->background_info ?: null,
            'source_material' => $this->source_material ?: 'Original',
            'poster_url' => $this->poster_url ?: null,
            'banner_url' => $this->banner_url ?: null,
            'trailer_youtube_id' => $this->trailer_youtube_id ?: null,
            'type' => AnimeType::tryFrom($this->type) ?? AnimeType::TV,
            'status' => AnimeStatus::tryFrom($this->status) ?? AnimeStatus::Ongoing,
            'release_day' => ! empty($this->release_day) ? ReleaseDay::tryFrom($this->release_day) : null,
            'total_episodes' => $this->total_episodes ? (int) $this->total_episodes : null,
            'duration' => $this->duration ?: null,
            'release_year' => $this->release_year ? (int) $this->release_year : null,
            'aired_string' => $this->aired_string ?: null,
            'rating_score' => ! empty($this->rating_score) ? (float) $this->rating_score : 0.00,
            'scored_by' => ! empty($this->scored_by) ? (int) $this->scored_by : 0,
            'rank' => $this->rank,
            'age_rating' => $this->age_rating ?: null,
            'is_featured' => (bool) $this->is_featured,
            'is_recommended' => (bool) $this->is_recommended,
            'studio_name' => $this->studio_name,
            'season_name' => $this->season_name,
            'season_quarter' => $this->season_quarter,
            'genres' => $this->selectedGenres,
            'themes' => $this->selectedThemes,
            'staff' => $this->staffList,
        ];

        try {
            if ($this->isEditing && $this->anime) {
                $metadataService->syncToAnime($this->anime, $payload);
                $this->anime->refresh()->load(['studio', 'season', 'taxonomies', 'people']);
                $msg = "Anime '{$this->anime->title}' berhasil diperbarui!";
                $this->saveType = 'success';
                $this->saveMessage = $msg;
                session()->flash('message', $msg);
            } else {
                $anime = Anime::create([
                    'title' => $this->title,
                    'slug' => $this->slug,
                    'type' => AnimeType::tryFrom($this->type) ?? AnimeType::TV,
                    'status' => AnimeStatus::tryFrom($this->status) ?? AnimeStatus::Ongoing,
                ]);
                $metadataService->syncToAnime($anime, $payload);
                session()->flash('message', "Anime '{$anime->title}' berhasil dibuat!");

                $this->redirect(route('admin.animes.edit', $anime), navigate: true);

                return;
            }

            $this->loadFromModel();
        } catch (Exception $e) {
            $this->saveType = 'error';
            $this->saveMessage = 'Gagal menyimpan anime: '.$e->getMessage();
        }
    }

    public function render()
    {
        $availableTypes = AnimeType::cases();
        $availableStatuses = AnimeStatus::cases();
        $availableDays = ReleaseDay::cases();

        return view('livewire.admin.anime-editor', compact('availableTypes', 'availableStatuses', 'availableDays'));
    }
}
