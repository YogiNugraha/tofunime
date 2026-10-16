<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudioType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
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
        'is_featured',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StudioType::class,
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Animes produced by this studio.
     *
     * @return HasMany<Anime, $this>
     */
    public function animes(): HasMany
    {
        return $this->hasMany(Anime::class);
    }

    /**
     * Scope query to featured studios.
     *
     * @param  Builder<Studio>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
