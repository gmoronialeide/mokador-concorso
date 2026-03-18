<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinalPrizeResource\Pages;
use App\Models\FinalPrize;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinalPrizeResource extends Resource
{
    protected static ?string $model = FinalPrize::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static string | \UnitEnum | null $navigationGroup = 'Concorso';

    protected static ?string $modelLabel = 'Premio Finale';

    protected static ?string $pluralModelLabel = 'Premi Finali';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')->label('Posizione')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => $state . '°'),
                TextColumn::make('name')->label('Nome'),
                TextColumn::make('value')->label('Valore')
                    ->money('EUR')
                    ->placeholder('Da definire'),
                TextColumn::make('is_drawn_status')
                    ->label('Stato')
                    ->badge()
                    ->state(fn (FinalPrize $record): string => $record->is_drawn ? 'Estratto' : 'Non estratto')
                    ->color(fn (string $state): string => $state === 'Estratto' ? 'success' : 'gray'),
                TextColumn::make('drawn_at')->label('Data estrazione')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('—'),
                TextColumn::make('winner.user.email')->label('Vincitore')
                    ->placeholder('—'),
            ])
            ->defaultSort('position', 'asc')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinalPrizes::route('/'),
        ];
    }
}
