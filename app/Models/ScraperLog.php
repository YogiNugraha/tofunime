<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScraperLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider_name',
        'target_url',
        'action',
        'status',
        'items_processed',
        'response_time_ms',
        'error_message',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items_processed' => 'integer',
            'response_time_ms' => 'integer',
            'payload' => 'array',
        ];
    }
}
