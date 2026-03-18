<div style="display: flex; justify-content: center; align-items: center; overflow: hidden; max-height: 70vh;">
    @if ($record->receipt_image)
        <img
            src="{{ route('admin.receipt', ['path' => str_replace('receipts/', '', $record->receipt_image)]) }}"
            alt="Scontrino giocata #{{ $record->id }}"
            style="max-height: 70vh; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block;"
        >
    @else
        <p style="color: #6b7280; font-size: 0.875rem;">Immagine non disponibile.</p>
    @endif
</div>
