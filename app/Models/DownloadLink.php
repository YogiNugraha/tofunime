<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DownloadFormat;
use App\Enums\VideoQuality;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLink extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'anime_id',
        'episode_id',
        'is_batch',
        'format',
        'quality',
        'host_name',
        'url',
        'file_size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_batch' => 'boolean',
            'format' => DownloadFormat::class,
            'quality' => VideoQuality::class,
        ];
    }

    /**
     * Associated anime.
     *
     * @return BelongsTo<Anime, $this>
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Associated episode (null if batch download).
     *
     * @return BelongsTo<Episode, $this>
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
