@if ($getRecord()->receipt_image)
    <div style="max-width: 100%; overflow: hidden;">
        <img
            src="{{ route('admin.receipt', ['path' => str_replace('receipts/', '', $getRecord()->receipt_image)]) }}"
            alt="Scontrino"
            style="max-height: 500px; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; border-radius: 8px;"
        >
    </div>
@else
    <p style="color: #6b7280; font-size: 0.875rem;">Immagine non disponibile.</p>
@endif
