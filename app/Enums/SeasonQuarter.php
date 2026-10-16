<?php

declare(strict_types=1);

namespace App\Enums;

enum SeasonQuarter: string
{
    case Winter = 'Winter';
    case Spring = 'Spring';
    case Summer = 'Summer';
    case Fall = 'Fall';

    public function label(): string
    {
        return match ($this) {
            self::Winter => 'Musim Dingin (Winter)',
            self::Spring => 'Musim Semi (Spring)',
            self::Summer => 'Musim Panas (Summer)',
            self::Fall => 'Musim Gugur (Fall)',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Winter->value => 'Winter',
            self::Spring->value => 'Spring',
            self::Summer->value => 'Summer',
            self::Fall->value => 'Fall',
        ];
    }
}
