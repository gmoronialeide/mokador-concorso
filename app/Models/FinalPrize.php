<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinalPrize extends Model
{
    protected $fillable = [
        'name',
        'description',
        'value',
        'position',
        'drawn_at',
        'drawn_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'position' => 'integer',
            'drawn_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'drawn_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(FinalDrawResult::class);
    }

    public function winner(): HasOne
    {
        return $this->hasOne(FinalDrawResult::class)->where('role', 'winner');
    }

    public function substitutes(): HasMany
    {
        return $this->hasMany(FinalDrawResult::class)
            ->where('role', 'substitute')
            ->orderBy('substitute_position');
    }

    public function getIsDrawnAttribute(): bool
    {
        return $this->drawn_at !== null;
    }

    public function getHasSubstitutesAttribute(): bool
    {
        return $this->substitutes()->exists();
    }
}
