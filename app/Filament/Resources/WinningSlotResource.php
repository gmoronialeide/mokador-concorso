<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WinningSlotResource\Pages;
use App\Models\Prize;
use App\Models\WinningSlot;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WinningSlotResource extends Resource
{
    protected static ?string $model = WinningSlot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|\UnitEnum|null $navigationGroup = 'Concorso';

    protected static ?string $modelLabel = 'Slot Vincente';

    protected static ?string $pluralModelLabel = 'Slot Vincenti';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return ! auth('admin')->user()->isNotaio();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('prize.code')->label('Premio')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'gray',
                        'B' => 'info',
                        'C' => 'success',
                        'D' => 'warning',
                        'E' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('prize.name')->label('Nome premio'),
                TextColumn::make('scheduled_date')->label('Data programmata')
                    ->date('d/m/Y')->sortable(),
                TextColumn::make('scheduled_time')->label('Ora programmata'),
                TextColumn::make('is_assigned')->label('Stato')
                    ->badge()
                    ->formatStateUsing(function (bool $state, WinningSlot $record): string {
                        if ($state) {
                            return 'Assegnato';
                        }
                        if ($record->scheduled_date < Carbon::today()) {
                            return 'Non assegnato';
                        }

                        return 'Disponibile';
                    })
                    ->color(function (bool $state, WinningSlot $record): string {
                        if ($state) {
                            return 'success';
                        }
                        if ($record->scheduled_date < Carbon::today()) {
                            return 'danger';
                        }

                        return 'gray';
                    }),
                TextColumn::make('play.user.email')->label('Vincitore')->placeholder('-'),
                TextColumn::make('assigned_at')->label('Assegnato il')
                    ->dateTime('d/m/Y H:i')->placeholder('-'),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->filters([
                TernaryFilter::make('is_assigned')->label('Assegnato'),
                SelectFilter::make('prize_id')->label('Premio')
                    ->options(Prize::pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWinningSlots::route('/'),
        ];
    }
}
