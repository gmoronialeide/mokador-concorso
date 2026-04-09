<?php

namespace App\Enums;

enum AdminRole: string
{
    case Admin = 'admin';
    case Notaio = 'notaio';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Amministratore',
            self::Notaio => 'Notaio',
        };
    }
}
