<?php

namespace App\Filament\Resources\PlayResource\Pages;

use App\Filament\Resources\PlayResource;
use App\Models\Play;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPlays extends ListRecords
{
    protected static string $resource = PlayResource::class;

    public ?string $listSnapshotAt = null;

    public function mount(): void
    {
        parent::mount();

        $this->listSnapshotAt = now()->format('Y-m-d H:i:s');
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery() ?? static::getResource()::getEloquentQuery();

        return $query->where('plays.created_at', '<=', $this->listSnapshotAt);
    }

    public function getSubheading(): ?string
    {
        $newCount = Play::query()
            ->where('created_at', '>', $this->listSnapshotAt)
            ->count();

        if ($newCount === 0) {
            return null;
        }

        return $newCount === 1
            ? '1 nuova giocata dal caricamento. Ricarica per visualizzarla.'
            : "{$newCount} nuove giocate dal caricamento. Ricarica per visualizzarle.";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reload')
                ->label('Ricarica elenco')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn ($livewire) => $livewire->js('window.location.reload()')),
        ];
    }
}
