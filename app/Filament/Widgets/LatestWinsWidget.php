<?php

namespace App\Filament\Widgets;

use App\Enums\PlayStatus;
use App\Models\Play;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestWinsWidget extends BaseWidget
{
    protected static ?string $heading = 'Ultime 10 vincite';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Play::query()
                    ->where('is_winner', true)
                    ->whereNot('status', PlayStatus::Banned)
                    ->with(['user', 'prize'])
                    ->latest('played_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('played_at')->label('Data')->dateTime('d/m/Y H:i'),
                TextColumn::make('user.surname')->label('Utente')
                    ->formatStateUsing(fn (Play $record): string => $record->user->surname . ' ' . $record->user->name),
                TextColumn::make('user.email')->label('Email'),
                TextColumn::make('store_code')->label('Punto Vendita'),
                TextColumn::make('prize.code')->label('Premio')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'gray', 'B' => 'info', 'C' => 'success', 'D' => 'warning', 'E' => 'danger', default => 'gray',
                    }),
                TextColumn::make('prize.name')->label('Nome premio'),
            ])
            ->paginated(false);
    }
}
