<?php

namespace App\Filament\Resources\PlayResource\Pages;

use App\Enums\PlayStatus;
use App\Filament\Resources\PlayResource;
use App\Models\Play;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPlay extends ViewRecord
{
    protected static string $resource = PlayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unvalidate')
                ->label('Annulla validazione')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Annullare la validazione?')
                ->modalDescription('La giocata tornerà in stato "In verifica".')
                ->action(function (Play $record): void {
                    abort_if(auth('admin')->user()->isNotaio(), 403);
                    abort_unless($record->status === PlayStatus::Validated, 422);
                    $record->update(['status' => PlayStatus::Pending]);
                })
                ->visible(fn (Play $record): bool => $record->status === PlayStatus::Validated && ! auth('admin')->user()->isNotaio()),
        ];
    }
}
