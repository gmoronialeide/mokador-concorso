@php
    $store = $record->store;
    $isNotaio = auth('admin')->user()?->isNotaio() ?? false;
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.75rem 1rem; background: #f9fafb; border-radius: 0.5rem;">
        <div style="display: flex; flex-direction: column;">
            @if ($store)
                <strong style="font-size: 0.95rem;">{{ $store->display_name }}</strong>
                <span style="font-size: 0.8rem; color: #6b7280;">{{ $store->city }}, {{ $store->province }}</span>
            @else
                <strong style="font-size: 0.95rem;">{{ $record->store_code }}</strong>
                <span style="font-size: 0.8rem; color: #6b7280;">— P.V. non assegnato</span>
            @endif
        </div>
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
