<?php

declare(strict_types=1);

namespace App\Enums;

enum AnimeStatus: string
{
    case Ongoing = 'Ongoing';
    case Completed = 'Completed';
    case Upcoming = 'Upcoming';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Ongoing',
            self::Completed => 'Tamat',
            self::Upcoming => 'Mendatang',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Ongoing->value => 'Ongoing',
            self::Completed->value => 'Tamat',
            self::Upcoming->value => 'Mendatang',
        ];
    }
}
