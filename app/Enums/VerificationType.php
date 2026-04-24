<?php

namespace App\Enums;

enum VerificationType: string
{
    case Auto = 'auto';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatica',
            self::Manual => 'Manuale',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Auto => 'gray',
            self::Manual => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Auto => 'heroicon-o-cpu-chip',
            self::Manual => 'heroicon-o-user',
        };
    }
}
