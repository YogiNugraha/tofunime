<?php

declare(strict_types=1);

namespace App\Enums;

enum DownloadFormat: string
{
    case MP4 = 'MP4';
    case MKV = 'MKV';
    case X265 = 'x265';

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
            self::MP4->value => 'MP4',
            self::MKV->value => 'MKV',
            self::X265->value => 'x265',
        ];
    }
}
