<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prize extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'value',
        'total_quantity',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'total_quantity' => 'integer',
        ];
    }

    public function winningSlots(): HasMany
    {
        return $this->hasMany(WinningSlot::class);
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PrizeSchedule::class)->orderBy('day_of_week');
    }

    public function getAssignedCountAttribute(): int
    {
        return $this->winningSlots()->where('is_assigned', true)->count();
    }

    public function getRemainingCountAttribute(): int
    {
        return $this->total_quantity - $this->assigned_count;
    }

    public function getScheduleDays(): array
    {
        return $this->winningSlots()
            ->select('scheduled_date')
            ->distinct()
            ->orderBy('scheduled_date')
            ->pluck('scheduled_date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->toArray();
    }
}
