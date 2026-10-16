<?php

declare(strict_types=1);

namespace App\Enums;

enum AnimeType: string
{
    case TV = 'TV';
    case Movie = 'Movie';
    case OVA = 'OVA';
    case ONA = 'ONA';
    case Special = 'Special';

    public function label(): string
    {
        return match ($this) {
            self::TV => 'TV Series',
            self::Movie => 'Movie',
            self::OVA => 'OVA',
            self::ONA => 'ONA',
            self::Special => 'Special',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::TV->value => 'TV Series',
            self::Movie->value => 'Movie',
            self::OVA->value => 'OVA',
            self::ONA->value => 'ONA',
            self::Special->value => 'Special',
        ];
    }
}
