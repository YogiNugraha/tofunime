<?php

declare(strict_types=1);

namespace App\Enums;

enum ReleaseDay: string
{
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';
    case Random = 'Random';

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Senin',
            self::Tuesday => 'Selasa',
            self::Wednesday => 'Rabu',
            self::Thursday => 'Kamis',
            self::Friday => 'Jumat',
            self::Saturday => 'Sabtu',
            self::Sunday => 'Minggu',
            self::Random => 'Acak',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Monday->value => 'Senin',
            self::Tuesday->value => 'Selasa',
            self::Wednesday->value => 'Rabu',
            self::Thursday->value => 'Kamis',
            self::Friday->value => 'Jumat',
            self::Saturday->value => 'Sabtu',
            self::Sunday->value => 'Minggu',
            self::Random->value => 'Acak',
        ];
    }
}
