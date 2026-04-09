<?php

namespace App\Filament\Widgets;

use App\Models\WinningSlot;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class UnassignedPrizesWidget extends BaseWidget
{
    protected static ?string $heading = 'Premi non assegnati per giorno';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WinningSlot::query()
                    ->where('is_assigned', false)
                    ->whereDate('scheduled_date', '<', Carbon::today())
                    ->orderBy('scheduled_date', 'desc')
            )
            ->columns([
                TextColumn::make('scheduled_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('scheduled_time')
                    ->label('Ora prevista'),
                TextColumn::make('prize.code')
                    ->label('Codice premio')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'gray',
                        'B' => 'info',
                        'C' => 'success',
                        'D' => 'warning',
                        'E' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('prize.name')
                    ->label('Premio'),
                TextColumn::make('prize.value')
                    ->label('Valore')
                    ->money('EUR'),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->emptyStateHeading('Nessun premio non assegnato')
            ->emptyStateDescription('Tutti i premi previsti fino ad oggi sono stati assegnati.')
            ->paginated([10, 25, 50]);
    }
}
