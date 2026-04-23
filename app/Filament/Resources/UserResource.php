<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\PlaysRelationManager;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Anagrafiche';

    protected static ?string $modelLabel = 'Utente';

    protected static ?string $pluralModelLabel = 'Utenti';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('surname')->label('Cognome')->sortable()->searchable(),
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('Telefono')->searchable(),
                TextColumn::make('city')->label('Città'),
                TextColumn::make('created_at')->label('Registrato il')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('plays_count')->label('Giocate')->counts('plays'),
                IconColumn::make('email_verified_at')->label('Email verificata')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasVerifiedEmail())
                    ->sortable(),
                IconColumn::make('marketing_consent')->label('Marketing')->boolean(),
                IconColumn::make('is_banned')->label('Valido')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->tooltip(fn (bool $state): string => $state ? 'Bannato' : 'Valido'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('email_verified')
                    ->label('Email verificata')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                    ),
                TernaryFilter::make('is_banned')->label('Bannato'),
                SelectFilter::make('province')->label('Provincia')
                    ->options(fn () => User::distinct()->pluck('province', 'province')->filter()->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('verify_email')
                    ->label('Verifica e attiva')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifica email e attiva utente')
                    ->modalDescription('Marca l\'email come verificata e, se l\'utente risulta bannato, ne rimuove il ban così da consentirgli l\'accesso.')
                    ->action(function (User $record): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        self::activateUser($record);
                        Notification::make()
                            ->title('Utente attivato')
                            ->body("Email verificata per {$record->email}.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail() && ! auth('admin')->user()->isNotaio()),
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
                    ->action(function (User $record, array $data): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update([
                            'is_banned' => true,
                            'ban_reason' => $data['ban_reason'],
                        ]);
                    })
                    ->visible(fn (User $record): bool => ! $record->is_banned && ! auth('admin')->user()->isNotaio()),
                Action::make('unban')
                    ->label('Sbanna')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $record->update([
                            'is_banned' => false,
                            'ban_reason' => null,
                        ]);
                    })
                    ->visible(fn (User $record): bool => $record->is_banned && ! auth('admin')->user()->isNotaio()),
            ])
            ->bulkActions([
                BulkAction::make('verify_email_selected')
                    ->label('Verifica e attiva selezionati')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $activated = 0;
                        $records->each(function (User $user) use (&$activated): void {
                            if (self::activateUser($user)) {
                                $activated++;
                            }
                        });
                        Notification::make()
                            ->title('Utenti attivati')
                            ->body("{$activated} utenti verificati e attivati.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => ! auth('admin')->user()->isNotaio()),
                BulkAction::make('ban_selected')
                    ->label('Banna selezionati')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('ban_reason')
                            ->label('Motivazione')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        abort_if(auth('admin')->user()->isNotaio(), 403);
                        $records->each(fn (User $user) => $user->update([
                            'is_banned' => true,
                            'ban_reason' => $data['ban_reason'],
                        ]));
                    })
                    ->visible(fn (): bool => ! auth('admin')->user()->isNotaio()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Dati personali')->schema([
                    TextEntry::make('name')->label('Nome'),
                    TextEntry::make('surname')->label('Cognome'),
                    TextEntry::make('birth_date')->label('Data di nascita')->date('d/m/Y'),
                    TextEntry::make('email')
                        ->columnSpan(2),
                    TextEntry::make('phone')->label('Telefono'),
                ])->columns(3),
                Section::make('Indirizzo')->schema([
                    TextEntry::make('address')->label('Indirizzo')
                        ->columnSpan(2),
                    TextEntry::make('city')->label('Città'),
                    TextEntry::make('province')->label('Provincia'),
                    TextEntry::make('cap')->label('CAP'),
                ])->columns(4),
                Section::make('Stato')->schema([
                    TextEntry::make('is_banned')->label('Bannato')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Bannato' : 'Attivo')
                        ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                    TextEntry::make('ban_reason')->label('Motivazione ban')->visible(fn (User $record): bool => $record->is_banned),
                    TextEntry::make('email_verified_at')->label('Email verificata')->dateTime('d/m/Y H:i'),
                    TextEntry::make('created_at')->label('Registrato il')->dateTime('d/m/Y H:i'),
                    TextEntry::make('marketing_consent')->label('Consenso marketing')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Sì' : 'No'),
                    TextEntry::make('privacy_consent')->label('Consenso privacy')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Sì' : 'No'),
                ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PlaysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    /**
     * Verify email and remove ban (if present) so the user can access the contest.
     * Returns true if any state changed.
     */
    public static function activateUser(User $user): bool
    {
        $changed = false;

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $changed = true;
        }

        if ($user->is_banned) {
            $user->update([
                'is_banned' => false,
                'ban_reason' => null,
            ]);
            $changed = true;
        }

        return $changed;
    }
}
