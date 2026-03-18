<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalDrawResult extends Model
{
    protected $fillable = [
        'final_prize_id',
        'user_id',
        'role',
        'substitute_position',
        'total_plays',
        'drawn_at',
    ];

    protected function casts(): array
    {
        return [
            'substitute_position' => 'integer',
            'total_plays' => 'integer',
            'drawn_at' => 'datetime',
        ];
    }

    public function finalPrize(): BelongsTo
    {
        return $this->belongsTo(FinalPrize::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
