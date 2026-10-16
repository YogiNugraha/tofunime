<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxonomyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Taxonomy extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mal_id',
        'name',
        'slug',
        'type',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaxonomyType::class,
        ];
    }

    /**
     * Animes associated with this taxonomy.
     *
     * @return BelongsToMany<Anime, $this>
     */
    public function animes(): BelongsToMany
    {
        return $this->belongsToMany(Anime::class, 'anime_taxonomy');
    }

    /**
     * Scope query to genres.
     *
     * @param  Builder<Taxonomy>  $query
     */
    public function scopeGenre(Builder $query): void
    {
        $query->where('type', TaxonomyType::Genre);
    }

    /**
     * Scope query to themes.
     *
     * @param  Builder<Taxonomy>  $query
     */
    public function scopeTheme(Builder $query): void
    {
        $query->where('type', TaxonomyType::Theme);
    }

    /**
     * Scope query to demographics.
     *
     * @param  Builder<Taxonomy>  $query
     */
    public function scopeDemographic(Builder $query): void
    {
        $query->where('type', TaxonomyType::Demographic);
    }
}
