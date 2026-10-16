<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'people';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mal_id',
        'name',
        'slug',
        'image_url',
    ];

    /**
     * Animes linked to this person (Author, Director, Creator, etc).
     *
     * @return BelongsToMany<Anime, $this>
     */
    public function animes(): BelongsToMany
    {
        return $this->belongsToMany(Anime::class, 'anime_person')
            ->withPivot('role')
            ->withTimestamps();
    }
}
