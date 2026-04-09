<?php

namespace App\Enums;

enum PlayStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In verifica',
            self::Validated => 'Validata',
            self::Banned => 'Bannata',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Validated => 'success',
            self::Banned => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Validated => 'heroicon-o-check-circle',
            self::Banned => 'heroicon-o-x-circle',
        };
    }
}
