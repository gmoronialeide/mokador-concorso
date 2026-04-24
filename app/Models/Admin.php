<?php

namespace App\Models;

use App\Enums\AdminRole;
use Database\Factories\AdminFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => AdminRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === AdminRole::Admin;
    }

    public function isNotaio(): bool
    {
        return $this->role === AdminRole::Notaio;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
