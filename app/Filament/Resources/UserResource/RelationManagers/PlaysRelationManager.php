<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaysRelationManager extends RelationManager
{
    protected static string $relationship = 'plays';

    protected static ?string $title = 'Giocate';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('played_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('store_code')->label('Punto Vendita'),
                IconColumn::make('is_winner')->label('Vincente')->boolean(),
                TextColumn::make('prize.name')->label('Premio')->placeholder('-'),
                IconColumn::make('is_banned')->label('Valida')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->tooltip(fn (bool $state): string => $state ? 'Bannata' : 'Valida'),
            ])
            ->defaultSort('played_at', 'desc');
    }
}
