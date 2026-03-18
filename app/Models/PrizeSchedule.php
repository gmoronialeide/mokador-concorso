<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeSchedule extends Model
{
    protected $table = 'prize_schedule';

    protected $fillable = [
        'prize_id',
        'day_of_week',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /** Nome giorno italiano. */
    public function getDayNameAttribute(): string
    {
        $days = [1 => 'Lunedì', 2 => 'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì', 5 => 'Venerdì', 6 => 'Sabato', 7 => 'Domenica'];

        return $days[$this->day_of_week] ?? '';
    }

    /** Nome giorno italiano abbreviato. */
    public function getDayShortAttribute(): string
    {
        $days = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];

        return $days[$this->day_of_week] ?? '';
    }
}
