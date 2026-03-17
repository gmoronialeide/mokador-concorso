<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WinningSlot extends Model
{
    protected $fillable = [
        'prize_id',
        'scheduled_date',
        'scheduled_time',
        'is_assigned',
        'play_id',
        'assigned_at',
    ];

    protected $attributes = [
        'is_assigned' => false,
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'is_assigned' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function play(): BelongsTo
    {
        return $this->belongsTo(Play::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_assigned', false);
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function scopePastTime(Builder $query, string $time): Builder
    {
        return $query->where('scheduled_time', '<=', $time);
    }
}
