<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'surname',
        'birth_date',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'cap',
        'password',
        'privacy_consent',
        'marketing_consent',
        'is_banned',
        'ban_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'privacy_consent' => 'boolean',
            'marketing_consent' => 'boolean',
            'is_banned' => 'boolean',
        ];
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class);
    }

    public function scopeBanned(Builder $query): Builder
    {
        return $query->where('is_banned', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_banned', false);
    }

    public function getPlaysCountAttribute(): int
    {
        return $this->plays()->count();
    }

    public function getWinsCountAttribute(): int
    {
        return $this->plays()->where('is_winner', true)->count();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function hasPlayedToday(): bool
    {
        return $this->plays()
            ->whereDate('played_at', Carbon::today())
            ->exists();
    }
}
