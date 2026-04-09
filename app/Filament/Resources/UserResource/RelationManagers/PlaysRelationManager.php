<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\PlayStatus;
use App\Models\Play;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
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
                TextColumn::make('status')->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PlayStatus $state): string => $state->label())
                    ->color(fn (PlayStatus $state): string => $state->color())
                    ->icon(fn (PlayStatus $state): string => $state->icon()),
            ])
            ->defaultSort('played_at', 'desc')
            ->actions([
                Action::make('notes')
                    ->label('')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color(fn (Play $record): string => filled($record->notes) ? 'warning' : 'gray')
                    ->tooltip('Note')
                    ->modalHeading('Note')
                    ->modalWidth('md')
                    ->form([
                        Textarea::make('notes')
                            ->label('Note')
                            ->rows(4)
                            ->default(fn (Play $record): ?string => $record->notes),
                    ])
                    ->action(function (Play $record, array $data): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update(['notes' => $data['notes']]);
                    })
                    ->visible(fn (): bool => ! auth('admin')->user()->isNotaio())
                    ->modalSubmitActionLabel('Salva')
                    ->modalCancelActionLabel('Chiudi'),
            ]);
    }
}
