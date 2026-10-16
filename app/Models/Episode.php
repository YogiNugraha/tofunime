<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'anime_id',
        'episode_number',
        'episode_order',
        'title',
        'slug',
        'source_episode_url',
        'views_count',
        'aired_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'episode_order' => 'decimal:2',
            'views_count' => 'integer',
            'aired_at' => 'datetime',
        ];
    }

    /**
     * Parent anime series.
     *
     * @return BelongsTo<Anime, $this>
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * All video streaming mirrors.
     *
     * @return HasMany<VideoMirror, $this>
     */
    public function mirrors(): HasMany
    {
        return $this->hasMany(VideoMirror::class)->orderBy('priority_order', 'asc');
    }

    /**
     * Only active video streaming mirrors.
     *
     * @return HasMany<VideoMirror, $this>
     */
    public function activeMirrors(): HasMany
    {
        return $this->hasMany(VideoMirror::class)
            ->where('is_active', true)
            ->orderBy('priority_order', 'asc');
    }

    /**
     * Download links for this episode.
     *
     * @return HasMany<DownloadLink, $this>
     */
    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class);
    }

    /**
     * User broken link reports.
     *
     * @return HasMany<BrokenReport, $this>
     */
    public function brokenReports(): HasMany
    {
        return $this->hasMany(BrokenReport::class);
    }

    /**
     * User watch history tracking for this episode.
     *
     * @return HasMany<WatchHistory, $this>
     */
    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }
}
