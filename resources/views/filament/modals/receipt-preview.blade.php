@php
    $store = $record->store;
    $canManage = auth('admin')->check() && ! auth('admin')->user()->isNotaio();
    $headerLabel = $store ? $store->display_name : $record->store_code;
    $userLabel = $record->user ? trim($record->user->name.' '.$record->user->surname) : null;
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.75rem 1rem; background: #f9fafb; border-radius: 0.5rem;">
        <div style="display: flex; flex-direction: column;">
            <strong style="font-size: 0.95rem;">{{ $headerLabel }} - ID {{ $record->id }}@if ($userLabel) - {{ $userLabel }}@endif</strong>
            @if ($store)
                <span style="font-size: 0.8rem; color: #6b7280;">{{ $store->city }}, {{ $store->province }}</span>
            @else
                <span style="font-size: 0.8rem; color: #6b7280;">— P.V. non assegnato</span>
            @endif
        </div>

        @if ($canManage)
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @if ($record->isPending())
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        size="sm"
                        x-on:click="$wire.mountTableAction('validate', '{{ $record->id }}')"
                    >
                        Valida
                    </x-filament::button>
                @endif

                @if (! $record->isBanned())
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-no-symbol"
                        size="sm"
                        x-on:click="$wire.mountTableAction('ban', '{{ $record->id }}')"
                    >
                        Banna
                    </x-filament::button>
                @endif

                @if ($record->isBanned())
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        size="sm"
                        x-on:click="$wire.mountTableAction('unban', '{{ $record->id }}')"
                    >
                        Sbanna
                    </x-filament::button>
                @endif
            </div>
        @endif
    </div>

    <div style="display: flex; justify-content: center; align-items: center; overflow: hidden; max-height: 75vh;">
        @if ($record->receipt_image)
            <img
                src="{{ route('admin.receipt', ['path' => str_replace('receipts/', '', $record->receipt_image)]) }}"
                alt="Scontrino giocata #{{ $record->id }}"
                style="max-height: 75vh; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block;"
            >
        @else
            <p style="color: #6b7280; font-size: 0.875rem;">Immagine non disponibile.</p>
        @endif
    </div>
</div>
