<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Play extends Model
{
    protected $fillable = [
        'user_id',
        'store_code',
        'receipt_image',
        'played_at',
        'is_winner',
        'prize_id',
        'winning_slot_id',
        'is_banned',
        'ban_reason',
        'banned_at',
    ];

    protected $attributes = [
        'is_winner' => false,
        'is_banned' => false,
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'is_winner' => 'boolean',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_code', 'code');
    }

    public function winningSlot(): BelongsTo
    {
        return $this->belongsTo(WinningSlot::class);
    }

    public function scopeWinners(Builder $query): Builder
    {
        return $query->where('is_winner', true);
    }

    public function scopeLosers(Builder $query): Builder
    {
        return $query->where('is_winner', false);
    }

    public function scopeBanned(Builder $query): Builder
    {
        return $query->where('is_banned', true);
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('played_at', $date);
    }
}
