<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoQuality: string
{
    case Q360p = '360p';
    case Q480p = '480p';
    case Q720p = '720p';
    case Q1080p = '1080p';
    case Q4K = '4K';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Q360p->value => '360p',
            self::Q480p->value => '480p',
            self::Q720p->value => '720p',
            self::Q1080p->value => '1080p',
            self::Q4K->value => '4K',
        ];
    }
}
