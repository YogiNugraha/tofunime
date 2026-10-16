<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\ReleaseDay;
use App\Enums\TaxonomyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anime extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mal_id',
        'studio_id',
        'season_id',
        'title',
        'slug',
        'english_title',
        'japanese_title',
        'synonyms',
        'synopsis',
        'background_info',
        'source_material',
        'poster_url',
        'banner_url',
        'trailer_youtube_id',
        'type',
        'status',
        'total_episodes',
        'duration',
        'release_year',
        'aired_string',
        'release_day',
        'rating_score',
        'scored_by',
        'rank',
        'age_rating',
        'is_featured',
        'is_recommended',
        'views_count',
        'latest_episode_at',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
            'type' => AnimeType::class,
            'status' => AnimeStatus::class,
            'release_day' => ReleaseDay::class,
            'is_featured' => 'boolean',
            'is_recommended' => 'boolean',
            'views_count' => 'integer',
            'latest_episode_at' => 'datetime',
            'rating_score' => 'decimal:2',
            'scored_by' => 'integer',
            'rank' => 'integer',
            'total_episodes' => 'integer',
            'release_year' => 'integer',
        ];
    }

    /**
     * Primary animation studio.
     *
     * @return BelongsTo<Studio, $this>
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    /**
     * Release season (e.g. Fall 2026).
     *
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Authors, original creators, and directors.
     *
     * @return BelongsToMany<Person, $this>
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'anime_person')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * All associated taxonomies (genres, themes, demographics).
     *
     * @return BelongsToMany<Taxonomy, $this>
     */
    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, 'anime_taxonomy');
    }

    /**
     * Genres subset of taxonomies.
     *
     * @return BelongsToMany<Taxonomy, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, 'anime_taxonomy')
            ->where('taxonomies.type', TaxonomyType::Genre);
    }

    /**
     * Themes / tags subset of taxonomies (e.g. Isekai, Reincarnation).
     *
     * @return BelongsToMany<Taxonomy, $this>
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, 'anime_taxonomy')
            ->where('taxonomies.type', TaxonomyType::Theme);
    }

    /**
     * Demographic tags subset (e.g. Shounen, Seinen).
     *
     * @return BelongsToMany<Taxonomy, $this>
     */
    public function demographics(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, 'anime_taxonomy')
            ->where('taxonomies.type', TaxonomyType::Demographic);
    }

    /**
     * Anime episodes ordered descending by episode_order.
     *
     * @return HasMany<Episode, $this>
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_order', 'desc');
    }

    /**
     * Direct and batch download links.
     *
     * @return HasMany<DownloadLink, $this>
     */
    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class);
    }

    /**
     * External scraping source configuration.
     *
     * @return HasMany<ScraperSource, $this>
     */
    public function scraperSources(): HasMany
    {
        return $this->hasMany(ScraperSource::class);
    }

    /**
     * User bookmarks for this anime.
     *
     * @return HasMany<UserBookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(UserBookmark::class);
    }

    /**
     * User watch history records for this anime.
     *
     * @return HasMany<WatchHistory, $this>
     */
    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    /**
     * Scope query to ongoing animes.
     *
     * @param  Builder<Anime>  $query
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->where('status', AnimeStatus::Ongoing);
    }

    /**
     * Scope query to completed animes.
     *
     * @param  Builder<Anime>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', AnimeStatus::Completed);
    }

    /**
     * Scope query to featured showcase items.
     *
     * @param  Builder<Anime>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Scope query to recommended animes.
     *
     * @param  Builder<Anime>  $query
     */
    public function scopeRecommended(Builder $query): void
    {
        $query->where('is_recommended', true);
    }

    /**
     * Scope query ordered by most recently updated episode.
     *
     * @param  Builder<Anime>  $query
     */
    public function scopeLatestUpdated(Builder $query): void
    {
        $query->orderByDesc('latest_episode_at');
    }
}
