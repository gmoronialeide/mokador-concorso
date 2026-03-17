<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'province',
        'cap',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvince(Builder $query, string $province): Builder
    {
        return $query->where('province', $province);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }
}
