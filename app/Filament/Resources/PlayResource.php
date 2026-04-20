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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Js;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'store', 'prize', 'winningSlot']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.surname')->label('Utente')
                    ->formatStateUsing(fn (Play $record): string => $record->user->surname.' '.$record->user->name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $terms = array_filter(preg_split('/\s+/', trim($search)) ?: []);

                        if ($terms === []) {
                            return $query;
                        }

                        return $query->whereHas('user', function (Builder $q) use ($terms): void {
                            foreach ($terms as $term) {
                                $q->where(function (Builder $sub) use ($term): void {
                                    $sub->where('surname', 'like', "%{$term}%")
                                        ->orWhere('name', 'like', "%{$term}%");
                                });
                            }
                        });
                    }),
                TextColumn::make('store_code')
                    ->label('Punto Vendita')
                    ->tooltip(function (Play $record): ?string {
                        if ($record->store === null) {
                            return null;
                        }

                        $store = $record->store;

                        return sprintf('%s (%s, %s)',
                            $store->display_name,
                            $store->city,
                            $store->province,
                        );
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $terms = array_filter(preg_split('/\s+/', trim($search)) ?: []);

                        if ($terms === []) {
                            return $query;
                        }

                        return $query->where(function (Builder $outer) use ($terms): void {
                            foreach ($terms as $term) {
                                $outer->where(function (Builder $sub) use ($term): void {
                                    $sub->where('store_code', 'like', "%{$term}%")
                                        ->orWhereHas('store', function (Builder $q) use ($term): void {
                                            $q->where('name', 'like', "%{$term}%")
                                                ->orWhere('sign_name', 'like', "%{$term}%")
                                                ->orWhere('city', 'like', "%{$term}%")
                                                ->orWhere('province', 'like', "%{$term}%");
                                        });
                                });
                            }
                        });
                    }),
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
                Action::make('copy_email')
                    ->label('')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->tooltip(fn (Play $record): string => $record->user->email)
                    ->alpineClickHandler(function (Play $record): string {
                        $emailJs = Js::from($record->user->email);
                        $messageJs = Js::from('Email copiata!');

                        return <<<JS
                            const text = {$emailJs};
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(text);
                            } else {
                                const ta = document.createElement('textarea');
                                ta.value = text;
                                ta.style.position = 'fixed';
                                ta.style.opacity = '0';
                                document.body.appendChild(ta);
                                ta.select();
                                document.execCommand('copy');
                                document.body.removeChild(ta);
                            }
                            \$tooltip({$messageJs}, {
                                theme: \$store.theme,
                                timeout: 2000,
                            })
                            JS;
                    }),
                Action::make('receipt')
                    ->label('')
                    ->icon('heroicon-o-camera')
                    ->color('gray')
                    ->tooltip('Scontrino')
                    ->modalHeading('Scontrino')
                    ->modalWidth('md')
                    ->modalContent(fn (Play $record) => view('filament.modals.receipt-preview', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),
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
                ViewAction::make()->label('')->tooltip('Dettaglio'),
                Action::make('validate')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip('Valida')
                    ->requiresConfirmation()
                    ->modalHeading('Validare questa giocata?')
                    ->modalDescription('Confermi che lo scontrino è valido e la giocata può essere approvata?')
                    ->action(function (Play $record): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update(['status' => PlayStatus::Validated]);
                    })
                    ->visible(fn (Play $record): bool => $record->isPending() && ! auth('admin')->user()->isNotaio()),
                Action::make('ban')
                    ->label('')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->tooltip('Banna')
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
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip('Sbanna')
                    ->requiresConfirmation()
                    ->modalHeading('Validare questa giocata?')
                    ->modalDescription('Confermi che lo scontrino è valido e la giocata può essere approvata? Il premio NON verrà riassegnato.')
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
                    TextEntry::make('is_winner')->label('Vincente')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Vincente' : 'Non vincente')
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    TextEntry::make('notes')->label('Note')
                        ->visible(fn (Play $record): bool => filled($record->notes))
                        ->columnSpanFull(),
                ])->columns(3),
                Section::make('Punto Vendita')->schema([
                    TextEntry::make('store_code')->label('Codice'),
                    TextEntry::make('store.display_name')->label('Nome')->placeholder('—'),
                    TextEntry::make('store.address')->label('Indirizzo')->placeholder('—'),
                    TextEntry::make('store.cap')->label('CAP')->placeholder('—'),
                    TextEntry::make('store.city')->label('Città')->placeholder('—'),
                    TextEntry::make('store.province')->label('Provincia')->placeholder('—'),
                ])->columns(3),
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
