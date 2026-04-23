<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify_email')
                ->label('Verifica e attiva')
                ->icon('heroicon-o-envelope-open')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verifica email e attiva utente')
                ->modalDescription('Marca l\'email come verificata e, se l\'utente risulta bannato, ne rimuove il ban così da consentirgli l\'accesso.')
                ->action(function (): void {
                    abort_if(auth('admin')->user()->isNotaio(), 403);
                    /** @var User $user */
                    $user = $this->record;
                    UserResource::activateUser($user);
                    Notification::make()
                        ->title('Utente attivato')
                        ->body("Email verificata per {$user->email}.")
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => ! $this->record->hasVerifiedEmail() && ! auth('admin')->user()->isNotaio()),
        ];
    }
}
