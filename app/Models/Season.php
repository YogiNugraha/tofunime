<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SeasonQuarter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'year',
        'quarter',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quarter' => SeasonQuarter::class,
        ];
    }

    /**
     * Animes released in this season.
     *
     * @return HasMany<Anime, $this>
     */
    public function animes(): HasMany
    {
        return $this->hasMany(Anime::class);
    }
}
