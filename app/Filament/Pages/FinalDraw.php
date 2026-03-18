<?php

namespace App\Filament\Pages;

use App\Models\FinalDrawResult;
use App\Models\FinalPrize;
use App\Models\Play;
use App\Services\FinalDrawService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalDraw extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';

    protected static string | \UnitEnum | null $navigationGroup = 'Concorso';

    protected static ?string $navigationLabel = 'Estrazione Finale';

    protected static ?string $title = 'Estrazione Finale';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.final-draw';

    public function getEligibleUsersCount(): int
    {
        return app(FinalDrawService::class)->getEligibleUsers()->count();
    }

    public function getEligiblePlaysCount(): int
    {
        return Play::query()
            ->where('is_banned', false)
            ->whereHas('user', fn ($q) => $q->where('is_banned', false))
            ->count();
    }

    public function getPrizes(): \Illuminate\Database\Eloquent\Collection
    {
        return FinalPrize::with(['winner.user', 'substitutes.user', 'admin'])
            ->orderBy('position')
            ->get();
    }

    public function getDrawnPrizesCount(): int
    {
        return FinalPrize::whereNotNull('drawn_at')->count();
    }

    public function getTotalPrizesCount(): int
    {
        return FinalPrize::count();
    }

    public function hasAllWinners(): bool
    {
        return $this->getTotalPrizesCount() > 0
            && FinalPrize::whereNull('drawn_at')->doesntExist();
    }

    public function hasSubstitutes(): bool
    {
        return FinalDrawResult::where('role', 'substitute')->exists();
    }

    public function isContestEnded(): bool
    {
        $endDate = config('app.concorso_end_date');

        return $endDate && Carbon::parse($endDate)->endOfDay()->isPast();
    }

    public function drawWinnersAction(): Action
    {
        return Action::make('drawWinners')
            ->label('Estrai Vincitori')
            ->icon('heroicon-o-bolt')
            ->color('success')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading('Conferma estrazione vincitori')
            ->modalDescription(fn () => "Verranno estratti i 3 vincitori dei premi finali tra {$this->getEligibleUsersCount()} utenti eleggibili con {$this->getEligiblePlaysCount()} giocate valide totali. L'estrazione è pesata: chi ha giocato di più ha più probabilità.")
            ->modalSubmitActionLabel('Estrai')
            ->visible(fn () => $this->isContestEnded() && ! $this->hasAllWinners() && $this->getTotalPrizesCount() > 0)
            ->action(function () {
                try {
                    $service = app(FinalDrawService::class);
                    $admin = auth('admin')->user();
                    $service->drawWinners($admin);

                    Notification::make()
                        ->title('Vincitori estratti con successo!')
                        ->success()
                        ->send();
                } catch (\RuntimeException $e) {
                    Notification::make()
                        ->title('Errore')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function drawSubstitutesAction(): Action
    {
        return Action::make('drawSubstitutes')
            ->label('Estrai Sostituti')
            ->icon('heroicon-o-users')
            ->color('warning')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading('Conferma estrazione sostituti')
            ->modalDescription(fn () => "Verranno estratti 3 sostituti per ciascun premio finale tra {$this->getEligibleUsersCount()} utenti eleggibili rimasti.")
            ->modalSubmitActionLabel('Estrai')
            ->visible(fn () => $this->hasAllWinners() && ! $this->hasSubstitutes())
            ->action(function () {
                try {
                    $service = app(FinalDrawService::class);
                    $admin = auth('admin')->user();
                    $service->drawSubstitutes($admin);

                    Notification::make()
                        ->title('Sostituti estratti con successo!')
                        ->success()
                        ->send();
                } catch (\RuntimeException $e) {
                    Notification::make()
                        ->title('Errore')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function resetSubstitutesAction(): Action
    {
        return Action::make('resetSubstitutes')
            ->label('Annulla Sostituti')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Annulla estrazione sostituti')
            ->modalDescription('Verranno eliminati tutti i sostituti estratti. I vincitori resteranno invariati.')
            ->visible(fn () => $this->hasSubstitutes())
            ->action(function () {
                app(FinalDrawService::class)->resetSubstitutes();

                Notification::make()
                    ->title('Sostituti annullati')
                    ->success()
                    ->send();
            });
    }

    public function resetAllAction(): Action
    {
        return Action::make('resetAll')
            ->label('Annulla Tutto')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Annulla intera estrazione')
            ->modalDescription('Verranno eliminati TUTTI i risultati (vincitori e sostituti). Questa azione non è reversibile.')
            ->visible(fn () => $this->hasAllWinners())
            ->action(function () {
                app(FinalDrawService::class)->resetAll();

                Notification::make()
                    ->title('Estrazione annullata completamente')
                    ->success()
                    ->send();
            });
    }

    public function exportVerbaleAction(): Action
    {
        return Action::make('exportVerbale')
            ->label('Esporta Verbale')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->visible(fn () => $this->hasAllWinners() && $this->hasSubstitutes())
            ->action(function (): StreamedResponse {
                $prizes = FinalPrize::with(['winner.user', 'substitutes.user', 'admin'])
                    ->orderBy('position')
                    ->get();

                $eligibleCount = $this->getEligibleUsersCount()
                    + FinalDrawResult::count(); // Include drawn users in total
                $playsCount = $this->getEligiblePlaysCount();

                $filename = 'verbale_estrazione_finale_' . now()->format('Y-m-d_His') . '.csv';

                return response()->streamDownload(function () use ($prizes, $eligibleCount, $playsCount) {
                    $handle = fopen('php://output', 'w');

                    // BOM UTF-8 per Excel
                    fwrite($handle, "\xEF\xBB\xBF");

                    // Intestazione documento
                    fputcsv($handle, ['VERBALE ESTRAZIONE FINALE — Mokador ti porta in vacanza'], ';');
                    fputcsv($handle, [''], ';');
                    fputcsv($handle, ['Data generazione', now()->format('d/m/Y H:i:s')], ';');
                    fputcsv($handle, ['Partecipanti eleggibili totali', $eligibleCount], ';');
                    fputcsv($handle, ['Giocate valide totali nel pool', $playsCount], ';');
                    fputcsv($handle, ['Algoritmo', 'Estrazione pesata con random_int() (CSPRNG) — peso = numero giocate valide per utente'], ';');
                    fputcsv($handle, [''], ';');

                    foreach ($prizes as $prize) {
                        fputcsv($handle, [''], ';');
                        fputcsv($handle, ["PREMIO {$prize->position}° — {$prize->name}"], ';');
                        fputcsv($handle, ['Valore', number_format($prize->value, 2, ',', '.')], ';');
                        fputcsv($handle, ['Data estrazione', $prize->drawn_at->format('d/m/Y H:i:s')], ';');
                        fputcsv($handle, ['Estratto da', $prize->admin?->name ?? '—'], ';');
                        fputcsv($handle, [''], ';');

                        // Header risultati
                        fputcsv($handle, ['Ruolo', 'Posizione', 'Nome', 'Cognome', 'Email', 'Telefono', 'Data nascita', 'Indirizzo', 'Città', 'Provincia', 'CAP', 'Giocate valide', 'Data estrazione'], ';');

                        // Vincitore
                        if ($prize->winner) {
                            $u = $prize->winner->user;
                            fputcsv($handle, [
                                'VINCITORE',
                                '',
                                $u->name,
                                $u->surname,
                                $u->email,
                                $u->phone ?? '',
                                $u->birth_date?->format('d/m/Y') ?? '',
                                $u->address ?? '',
                                $u->city ?? '',
                                $u->province ?? '',
                                $u->cap ?? '',
                                $prize->winner->total_plays,
                                $prize->winner->drawn_at->format('d/m/Y H:i:s'),
                            ], ';');
                        }

                        // Sostituti
                        foreach ($prize->substitutes as $sub) {
                            $u = $sub->user;
                            fputcsv($handle, [
                                'SOSTITUTO',
                                $sub->substitute_position . '°',
                                $u->name,
                                $u->surname,
                                $u->email,
                                $u->phone ?? '',
                                $u->birth_date?->format('d/m/Y') ?? '',
                                $u->address ?? '',
                                $u->city ?? '',
                                $u->province ?? '',
                                $u->cap ?? '',
                                $sub->total_plays,
                                $sub->drawn_at->format('d/m/Y H:i:s'),
                            ], ';');
                        }
                    }

                    fclose($handle);
                }, $filename, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ]);
            });
    }

    public function isFullyDrawn(): bool
    {
        return $this->hasAllWinners() && $this->hasSubstitutes();
    }
}
