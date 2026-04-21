<?php

namespace App\Models;

use App\Enums\PlayStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Play extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'store_code',
        'receipt_image',
        'played_at',
        'is_winner',
        'prize_id',
        'winning_slot_id',
        'status',
        'ban_reason',
        'banned_at',
        'notes',
    ];

    protected $attributes = [
        'is_winner' => false,
        'status' => PlayStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'is_winner' => 'boolean',
            'status' => PlayStatus::class,
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
        return $this->belongsTo(Store::class);
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
        return $query->where('status', PlayStatus::Banned);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PlayStatus::Pending);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', PlayStatus::Validated);
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('played_at', $date);
    }

    public function isPending(): bool
    {
        return $this->status === PlayStatus::Pending;
    }

    public function isValidated(): bool
    {
        return $this->status === PlayStatus::Validated;
    }

    public function isBanned(): bool
    {
        return $this->status === PlayStatus::Banned;
    }
}
