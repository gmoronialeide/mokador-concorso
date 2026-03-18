<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayResource\Pages;
use App\Models\Play;
use App\Models\Prize;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PlayResource extends Resource
{
    protected static ?string $model = Play::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-play';

    protected static string | \UnitEnum | null $navigationGroup = 'Concorso';

    protected static ?string $modelLabel = 'Giocata';

    protected static ?string $pluralModelLabel = 'Giocate';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.surname')->label('Utente')
                    ->formatStateUsing(fn (Play $record): string => $record->user->surname . ' ' . $record->user->name)
                    ->searchable(['users.surname', 'users.name']),
                TextColumn::make('store_code')->label('Punto Vendita')->searchable(),
                TextColumn::make('played_at')->label('Data giocata')->dateTime('d/m/Y H:i')->sortable(),
                IconColumn::make('is_winner')->label('Vincente')->boolean(),
                TextColumn::make('prize.name')->label('Premio')->placeholder('-'),
                IconColumn::make('is_banned')->label('Bannata')->boolean()
                    ->trueColor('danger')->falseColor('success'),
            ])
            ->defaultSort('played_at', 'desc')
            ->filters([
                TernaryFilter::make('is_winner')->label('Vincente'),
                TernaryFilter::make('is_banned')->label('Bannata'),
                SelectFilter::make('prize_id')->label('Premio')
                    ->options(Prize::pluck('name', 'id')),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('ban')
                    ->label('Banna')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('ban_reason')
                            ->label('Motivazione')
                            ->required(),
                    ])
                    ->action(function (Play $record, array $data): void {
                        // Il premio e lo slot NON vengono liberati:
                        // - Il PV resta "ha già vinto" per la settimana
                        // - Il premio resta assegnato (non torna in gioco)
                        $record->update([
                            'is_banned' => true,
                            'ban_reason' => $data['ban_reason'],
                            'banned_at' => now(),
                        ]);
                    })
                    ->visible(fn (Play $record): bool => ! $record->is_banned),
                Action::make('unban')
                    ->label('Sbanna')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('La giocata verrà sbannata ma il premio NON verrà riassegnato.')
                    ->action(function (Play $record): void {
                        $record->update([
                            'is_banned' => false,
                            'ban_reason' => null,
                            'banned_at' => null,
                        ]);
                    })
                    ->visible(fn (Play $record): bool => $record->is_banned),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Giocata')->schema([
                    TextEntry::make('id')->label('ID'),
                    TextEntry::make('played_at')->label('Data giocata')->dateTime('d/m/Y H:i'),
                    TextEntry::make('store_code')->label('Punto Vendita'),
                    TextEntry::make('is_winner')->label('Vincente')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Vincente' : 'Non vincente')
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                ])->columns(4),
                Section::make('Scontrino')->schema([
                    ImageEntry::make('receipt_image')->label('')
                        ->disk('receipts')
                        ->height(400),
                ]),
                Section::make('Utente')->schema([
                    TextEntry::make('user.name')->label('Nome'),
                    TextEntry::make('user.surname')->label('Cognome'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('user.phone')->label('Telefono'),
                ])->columns(4),
                Section::make('Premio')->schema([
                    TextEntry::make('prize.code')->label('Codice premio'),
                    TextEntry::make('prize.name')->label('Nome premio'),
                    TextEntry::make('winningSlot.scheduled_date')->label('Data slot')->date('d/m/Y'),
                    TextEntry::make('winningSlot.scheduled_time')->label('Ora slot'),
                ])->columns(4)->visible(fn (Play $record): bool => $record->is_winner),
                Section::make('Ban')->schema([
                    TextEntry::make('is_banned')->label('Bannata')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Bannata' : 'Attiva')
                        ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                    TextEntry::make('ban_reason')->label('Motivazione'),
                    TextEntry::make('banned_at')->label('Data ban')->dateTime('d/m/Y H:i'),
                ])->columns(3)->visible(fn (Play $record): bool => $record->is_banned),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlays::route('/'),
            'view' => Pages\ViewPlay::route('/{record}'),
        ];
    }
}
