<?php

declare(strict_types=1);

namespace App\Enums;

enum StudioType: string
{
    case Studio = 'studio';
    case Producer = 'producer';
    case Licensor = 'licensor';

    public function label(): string
    {
        return match ($this) {
            self::Studio => 'Studio Animasi',
            self::Producer => 'Produser',
            self::Licensor => 'Pemberi Lisensi',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Studio->value => 'Studio',
            self::Producer->value => 'Producer',
            self::Licensor->value => 'Licensor',
        ];
    }
}
