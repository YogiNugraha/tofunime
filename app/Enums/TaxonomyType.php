<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxonomyType: string
{
    case Genre = 'genre';
    case Theme = 'theme';
    case Demographic = 'demographic';

    public function label(): string
    {
        return match ($this) {
            self::Genre => 'Genre',
            self::Theme => 'Tema / Tag',
            self::Demographic => 'Demografi',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Genre->value => 'Genre',
            self::Theme->value => 'Tema / Tag',
            self::Demographic->value => 'Demografi',
        ];
    }
}
