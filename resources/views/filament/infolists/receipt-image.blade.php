@php
    $record = $getRecord();
    $url = $record->receipt_image
        ? route('admin.receipt', ['path' => str_replace('receipts/', '', $record->receipt_image)])
        : null;
@endphp

@include('filament.components.receipt-lightbox', ['url' => $url, 'alt' => 'Scontrino #' . $record->id, 'size' => 'md'])
