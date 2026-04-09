<?php

namespace App\Filament\Resources;

use App\Enums\PlayStatus;
use App\Filament\Resources\PlayResource\Pages;
use App\Models\Play;
use App\Models\Prize;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlayResource extends Resource
{
    protected static ?string $model = Play::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play';

    protected static string|\UnitEnum|null $navigationGroup = 'Concorso';

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
                    ->formatStateUsing(fn (Play $record): string => $record->user->surname.' '.$record->user->name)
                    ->searchable(['users.surname', 'users.name']),
                TextColumn::make('user.email')->label('Email')
                    ->copyable()
                    ->copyMessage('Email copiata!')
                    ->searchable(),
                TextColumn::make('store_code')->label('Punto Vendita')->searchable(),
                TextColumn::make('played_at')->label('Data giocata')->dateTime('d/m/Y H:i')->sortable(),
                IconColumn::make('is_winner')->label('Vincente')->boolean(),
                TextColumn::make('prize.name')->label('Premio')->placeholder('-'),
                TextColumn::make('status')->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PlayStatus $state): string => $state->label())
                    ->color(fn (PlayStatus $state): string => $state->color())
                    ->icon(fn (PlayStatus $state): string => $state->icon()),
            ])
            ->defaultSort('played_at', 'desc')
            ->filters([
                TernaryFilter::make('is_winner')->label('Vincente'),
                SelectFilter::make('status')->label('Stato')
                    ->options(collect(PlayStatus::cases())->mapWithKeys(fn (PlayStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('prize_id')->label('Premio')
                    ->options(Prize::pluck('name', 'id')),
            ])
            ->actions([
                Action::make('receipt')
                    ->label('Scontrino')
                    ->icon('heroicon-o-camera')
                    ->color('gray')
                    ->modalHeading('Scontrino')
                    ->modalWidth('md')
                    ->modalContent(fn (Play $record) => view('filament.modals.receipt-preview', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),
                Action::make('notes')
                    ->label('Note')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color(fn (Play $record): string => filled($record->notes) ? 'warning' : 'gray')
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
                ViewAction::make(),
                Action::make('validate')
                    ->label('Valida')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Play $record): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update(['status' => PlayStatus::Validated]);
                    })
                    ->visible(fn (Play $record): bool => $record->isPending() && ! auth('admin')->user()->isNotaio()),
                Action::make('ban')
                    ->label('Banna')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('ban_reason')
                            ->label('Motivazione')
                            ->required(),
                    ])
                    ->action(function (Play $record, array $data): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update([
                            'status' => PlayStatus::Banned,
                            'ban_reason' => $data['ban_reason'],
                            'banned_at' => now(),
                        ]);
                    })
                    ->visible(fn (Play $record): bool => ! $record->isBanned() && ! auth('admin')->user()->isNotaio()),
                Action::make('unban')
                    ->label('Sbanna')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('La giocata verrà sbannata ma il premio NON verrà riassegnato.')
                    ->action(function (Play $record): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update([
                            'status' => PlayStatus::Validated,
                            'ban_reason' => null,
                            'banned_at' => null,
                        ]);
                    })
                    ->visible(fn (Play $record): bool => $record->isBanned() && ! auth('admin')->user()->isNotaio()),
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
                    TextEntry::make('notes')->label('Note')
                        ->visible(fn (Play $record): bool => filled($record->notes))
                        ->columnSpanFull(),
                ])->columns(4),
                Section::make('Scontrino')->schema([
                    ViewEntry::make('receipt_image')
                        ->label('')
                        ->view('filament.infolists.receipt-image'),
                ]),
                Section::make('Utente')->schema([
                    TextEntry::make('user.name')->label('Nome'),
                    TextEntry::make('user.surname')->label('Cognome'),
                    TextEntry::make('user.email')->label('Email')
                        ->columnSpan(2),
                    TextEntry::make('user.phone')->label('Telefono'),
                ])->columns(4),
                Section::make('Premio')->schema([
                    TextEntry::make('prize.code')->label('Codice premio'),
                    TextEntry::make('prize.name')->label('Nome premio'),
                    TextEntry::make('winningSlot.scheduled_date')->label('Data slot')->date('d/m/Y'),
                    TextEntry::make('winningSlot.scheduled_time')->label('Ora slot'),
                ])->columns(4)->visible(fn (Play $record): bool => $record->is_winner),
                Section::make('Ban')->schema([
                    TextEntry::make('status')->label('Stato')
                        ->badge()
                        ->formatStateUsing(fn (PlayStatus $state): string => $state->label())
                        ->color(fn (PlayStatus $state): string => $state->color()),
                    TextEntry::make('ban_reason')->label('Motivazione'),
                    TextEntry::make('banned_at')->label('Data ban')->dateTime('d/m/Y H:i'),
                ])->columns(3)->visible(fn (Play $record): bool => $record->isBanned()),
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
