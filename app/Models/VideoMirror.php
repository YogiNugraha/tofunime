<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoQuality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoMirror extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'episode_id',
        'server_name',
        'quality',
        'embed_url',
        'is_default',
        'is_active',
        'priority_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quality' => VideoQuality::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'priority_order' => 'integer',
        ];
    }

    /**
     * Parent episode.
     *
     * @return BelongsTo<Episode, $this>
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * User broken reports for this mirror.
     *
     * @return HasMany<BrokenReport, $this>
     */
    public function brokenReports(): HasMany
    {
        return $this->hasMany(BrokenReport::class);
    }

    /**
     * Scope query to active mirrors.
     *
     * @param  Builder<VideoMirror>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get user-friendly Tofunime-branded server name.
     */
    public function getDisplayNameAttribute(): string
    {
        $raw = strtolower((string) $this->server_name);
        $url = strtolower((string) $this->embed_url);

        if (str_contains($raw, 'blogger') || str_contains($raw, 'google') || str_contains($url, 'blogger.com')) {
            return 'Tofunime Direct (Google HD)';
        }

        if (str_contains($raw, 'mega') || str_contains($url, 'mega.nz')) {
            return 'Tofunime Cloud (Mega)';
        }

        if (str_contains($raw, 'desu') || str_contains($url, 'desudesu')) {
            return 'Tofunime Alpha (HD)';
        }

        if (str_contains($raw, 'odstream') || str_contains($url, 'arcg')) {
            return 'Tofunime Beta (HD)';
        }

        if (str_contains($raw, 'filelion') || str_contains($raw, 'vidhide') || str_contains($url, 'vidhide')) {
            return 'Tofunime Gamma (Fast)';
        }

        if (str_contains($raw, 'streamwish') || str_contains($url, 'streamwish')) {
            return 'Tofunime Delta';
        }

        if (str_contains($raw, 'mp4upload') || str_contains($url, 'mp4upload')) {
            return 'Tofunime Stream';
        }

        return 'Tofunime Server '.($this->priority_order ?: $this->id);
    }
}
