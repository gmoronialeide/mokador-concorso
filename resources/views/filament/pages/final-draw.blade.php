<x-filament-panels::page>
    {{-- Avviso concorso ancora in corso --}}
    @if (! $this->isContestEnded())
        <div class="rounded-lg bg-warning-50 p-4 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:ring-warning-400/20">
            <div class="flex items-center gap-x-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-500" />
                <p class="text-sm font-medium text-warning-700 dark:text-warning-400">
                    Il concorso è ancora in corso. L'estrazione finale sarà disponibile dopo la chiusura.
                </p>
            </div>
        </div>
    @endif

    {{-- Statistiche --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Utenti eleggibili</p>
                <p class="text-3xl font-bold text-primary-600">{{ $this->getEligibleUsersCount() }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Giocate valide nel pool</p>
                <p class="text-3xl font-bold text-primary-600">{{ $this->getEligiblePlaysCount() }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Premi estratti</p>
                <p class="text-3xl font-bold text-primary-600">{{ $this->getDrawnPrizesCount() }} / {{ $this->getTotalPrizesCount() }}</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Azioni estrazione --}}
    @if ($this->isContestEnded())
        <div class="flex flex-wrap items-center gap-3">
            {{ $this->drawWinnersAction }}
            {{ $this->drawSubstitutesAction }}
            {{ $this->exportVerbaleAction }}
            {{ $this->resetSubstitutesAction }}
            {{ $this->resetAllAction }}
        </div>
    @endif

    {{-- Risultati premi --}}
    @foreach ($this->getPrizes() as $prize)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700">
                        {{ $prize->position }}°
                    </span>
                    <span>{{ $prize->name }}</span>
                    @if ($prize->value > 0)
                        <span class="text-sm text-gray-500">— € {{ number_format($prize->value, 2, ',', '.') }}</span>
                    @endif
                </div>
            </x-slot>

            @if ($prize->is_drawn)
                {{-- Vincitore --}}
                @if ($prize->winner)
                    <div class="mb-4">
                        <h4 class="mb-2 text-sm font-semibold uppercase tracking-wider text-success-600">
                            Vincitore
                        </h4>
                        <div class="rounded-lg border border-success-200 bg-success-50 p-4">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-5">
                                <div>
                                    <span class="text-xs text-gray-500">Nome</span>
                                    <p class="font-medium">{{ $prize->winner->user->name }} {{ $prize->winner->user->surname }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Email</span>
                                    <p class="font-medium">{{ $prize->winner->user->email }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Telefono</span>
                                    <p class="font-medium">{{ $prize->winner->user->phone ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Giocate valide</span>
                                    <p class="font-medium">{{ $prize->winner->total_plays }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Estratto il</span>
                                    <p class="font-medium">{{ $prize->winner->drawn_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Sostituti --}}
                @if ($prize->substitutes->isNotEmpty())
                    <div>
                        <h4 class="mb-2 text-sm font-semibold uppercase tracking-wider text-warning-600">
                            Sostituti
                        </h4>
                        <div class="space-y-2">
                            @foreach ($prize->substitutes as $substitute)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-6">
                                        <div>
                                            <span class="text-xs text-gray-500">Posizione</span>
                                            <p class="font-medium">{{ $substitute->substitute_position }}° sostituto</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Nome</span>
                                            <p class="font-medium">{{ $substitute->user->name }} {{ $substitute->user->surname }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Email</span>
                                            <p class="font-medium">{{ $substitute->user->email }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Telefono</span>
                                            <p class="font-medium">{{ $substitute->user->phone ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Giocate valide</span>
                                            <p class="font-medium">{{ $substitute->total_plays }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">Estratto il</span>
                                            <p class="font-medium">{{ $substitute->drawn_at->format('d/m/Y H:i:s') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Info estrazione --}}
                <div class="mt-3 text-xs text-gray-400">
                    Estrazione effettuata da {{ $prize->admin?->name ?? '—' }} il {{ $prize->drawn_at->format('d/m/Y H:i:s') }}
                </div>
            @else
                <div class="py-6 text-center text-gray-400">
                    <x-filament::icon icon="heroicon-o-clock" class="mx-auto mb-2 h-8 w-8" />
                    <p>In attesa di estrazione</p>
                </div>
            @endif
        </x-filament::section>
    @endforeach

    @if ($this->getTotalPrizesCount() === 0)
        <x-filament::section>
            <div class="py-6 text-center text-gray-400">
                <x-filament::icon icon="heroicon-o-exclamation-circle" class="mx-auto mb-2 h-8 w-8" />
                <p>Nessun premio finale configurato. Verifica la migrazione seed_production_data.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
