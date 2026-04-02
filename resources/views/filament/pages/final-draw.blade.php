<x-filament-panels::page>
    {{-- Avviso concorso ancora in corso --}}
    @if (! $this->isContestEnded())
        <div style="background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 12px;">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 20px; height: 20px; color: #f59e0b;" />
            <p style="margin: 0; font-size: 14px; font-weight: 500; color: #92400e;">
                Il concorso è ancora in corso. L'estrazione finale sarà disponibile dopo la chiusura.
            </p>
        </div>
    @endif

    {{-- Statistiche --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 51, 40, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-user-group" style="width: 24px; height: 24px; color: #4F3328;" />
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 500; color: #6b7280;">Utenti nel pool</p>
                    <p style="margin: 4px 0 0 0; font-size: 28px; font-weight: 700; color: #4F3328; line-height: 1;">{{ $this->getValidUsersCount() }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-play-circle" style="width: 24px; height: 24px; color: #3b82f6;" />
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 500; color: #6b7280;">Giocate valide nel pool</p>
                    <p style="margin: 4px 0 0 0; font-size: 28px; font-weight: 700; color: #3b82f6; line-height: 1;">{{ $this->getEligiblePlaysCount() }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(34, 197, 94, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-trophy" style="width: 24px; height: 24px; color: #16a34a;" />
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 500; color: #6b7280;">Premi estratti</p>
                    <p style="margin: 4px 0 0 0; font-size: 28px; font-weight: 700; color: #16a34a; line-height: 1;">{{ $this->getDrawnPrizesCount() }} / {{ $this->getTotalPrizesCount() }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Azioni estrazione --}}
    @if ($this->isContestEnded())
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">
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
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #4F3328; color: white; font-size: 14px; font-weight: 700; flex-shrink: 0;">
                        {{ $prize->position }}°
                    </span>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 18px; font-weight: 600;">{{ $prize->name }}</span>
                        @if ($prize->value > 0)
                            <span style="display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; background: rgba(79, 51, 40, 0.08); font-size: 13px; font-weight: 600; color: #4F3328;">
                                € {{ number_format($prize->value, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </x-slot>

            @if ($prize->is_drawn)
                {{-- Vincitore --}}
                @if ($prize->winner)
                    <div style="margin-bottom: 20px;">
                        <h4 style="display: flex; align-items: center; gap: 6px; margin: 0 0 10px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #16a34a;">
                            <x-filament::icon icon="heroicon-o-trophy" style="width: 16px; height: 16px;" />
                            Vincitore
                        </h4>
                        <div style="border-left: 4px solid #16a34a; background: #fff; padding: 16px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; border-left: 4px solid #16a34a;">
                            <p style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">
                                <a href="{{ $this->getUserViewUrl($prize->winner->user->id) }}" target="_blank" style="color: #111827; text-decoration: none; border-bottom: 1px dashed #9ca3af;">
                                    {{ $prize->winner->user->name }} {{ $prize->winner->user->surname }}
                                </a>
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                                <div>
                                    <span style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">Email</span>
                                    <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 500; color: #374151; white-space: nowrap; overflow-x: auto;">{{ $prize->winner->user->email }}</p>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">Telefono</span>
                                    <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 500; color: #374151;">{{ $prize->winner->user->phone ?? '—' }}</p>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">Giocate valide</span>
                                    <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 500; color: #374151;">{{ $prize->winner->total_plays }}</p>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">Estratto il</span>
                                    <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 500; color: #374151;">{{ $prize->winner->drawn_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Sostituti --}}
                @if ($prize->substitutes->isNotEmpty())
                    <div>
                        <h4 style="display: flex; align-items: center; gap: 6px; margin: 0 0 10px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #d97706;">
                            <x-filament::icon icon="heroicon-o-users" style="width: 16px; height: 16px;" />
                            Sostituti
                        </h4>
                        <div style="overflow: hidden; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">#</th>
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Nome</th>
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Email</th>
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Telefono</th>
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Giocate</th>
                                        <th style="padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Estratto il</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($prize->substitutes as $substitute)
                                        <tr style="border-bottom: 1px solid #f3f4f6; {{ $loop->even ? 'background: #fafafa;' : '' }}">
                                            <td style="padding: 10px 12px;">
                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: rgba(217, 119, 6, 0.1); font-size: 12px; font-weight: 700; color: #d97706;">
                                                    {{ $substitute->substitute_position }}
                                                </span>
                                            </td>
                                            <td style="padding: 10px 12px; font-weight: 500;">
                                                <a href="{{ $this->getUserViewUrl($substitute->user->id) }}" target="_blank" style="color: #111827; text-decoration: none; border-bottom: 1px dashed #9ca3af;">
                                                    {{ $substitute->user->name }} {{ $substitute->user->surname }}
                                                </a>
                                            </td>
                                            <td style="padding: 10px 12px; color: #4b5563; white-space: nowrap; overflow-x: auto;">{{ $substitute->user->email }}</td>
                                            <td style="padding: 10px 12px; color: #4b5563;">{{ $substitute->user->phone ?? '—' }}</td>
                                            <td style="padding: 10px 12px; color: #4b5563;">{{ $substitute->total_plays }}</td>
                                            <td style="padding: 10px 12px; color: #4b5563;">{{ $substitute->drawn_at->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Info estrazione --}}
                <div style="margin-top: 16px; display: flex; align-items: center; gap: 8px; font-size: 12px; color: #9ca3af;">
                    <x-filament::icon icon="heroicon-o-user-circle" style="width: 16px; height: 16px;" />
                    <span>Estrazione effettuata da <strong style="font-weight: 500;">{{ $prize->admin?->name ?? '—' }}</strong></span>
                    <span style="color: #d1d5db;">|</span>
                    <x-filament::icon icon="heroicon-o-clock" style="width: 16px; height: 16px;" />
                    <span>{{ $prize->drawn_at->format('d/m/Y H:i:s') }}</span>
                </div>
            @else
                <div style="border: 2px dashed #e5e7eb; border-radius: 8px; padding: 32px; text-align: center;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 40px; height: 40px; color: #d1d5db; margin: 0 auto 12px auto;" />
                    <p style="margin: 0; font-size: 14px; font-weight: 500; color: #9ca3af;">In attesa di estrazione</p>
                </div>
            @endif
        </x-filament::section>
    @endforeach

    @if ($this->getTotalPrizesCount() === 0)
        <x-filament::section>
            <div style="border: 2px dashed #e5e7eb; border-radius: 8px; padding: 32px; text-align: center;">
                <x-filament::icon icon="heroicon-o-exclamation-circle" style="width: 40px; height: 40px; color: #d1d5db; margin: 0 auto 12px auto;" />
                <p style="margin: 0; font-size: 14px; font-weight: 500; color: #9ca3af;">Nessun premio finale configurato. Verifica la migrazione seed_production_data.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
