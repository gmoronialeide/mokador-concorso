@php
    use App\Services\Ocr\ReceiptExtractor;

    $store = $record->store;
    $canManage = auth('admin')->check() && ! auth('admin')->user()->isNotaio();
    $headerLabel = $store ? $store->display_name : $record->store_code;
    $userLabel = $record->user ? trim($record->user->name.' '.$record->user->surname) : null;

    $ocr = $record->ocr_raw
        ? app(ReceiptExtractor::class)->fromAzureResponse($record->ocr_raw)
        : null;

    $ocrConfidencePct = $ocr?->merchantConfidence !== null
        ? (int) round($ocr->merchantConfidence * 100)
        : null;
    $ocrConfidenceColor = match (true) {
        $ocrConfidencePct === null => '#9ca3af',
        $ocrConfidencePct >= 80 => '#16a34a',
        default => '#ca8a04',
    };
    $ocrTotalLabel = $ocr?->total !== null
        ? '€ '.number_format($ocr->total, 2, ',', '.')
        : '—';
    $ocrTypeLabel = $ocr?->type === 'invoice' ? 'Fattura' : 'Scontrino';
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
                        x-on:click="$wire.replaceMountedAction('validate', [], { table: true, recordKey: '{{ $record->id }}' })"
                    >
                        Valida
                    </x-filament::button>
                @endif

                @if (! $record->isBanned())
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-no-symbol"
                        size="sm"
                        x-on:click="$wire.replaceMountedAction('ban', [], { table: true, recordKey: '{{ $record->id }}' })"
                    >
                        Banna
                    </x-filament::button>
                @endif

                @if ($record->isBanned())
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        size="sm"
                        x-on:click="$wire.replaceMountedAction('unban', [], { table: true, recordKey: '{{ $record->id }}' })"
                    >
                        Sbanna
                    </x-filament::button>
                @endif
            </div>
        @endif
    </div>

    @if ($record->notes)
        <div style="padding: 0.75rem 1rem; background: #fef3c7; border: 1px solid #fde68a; border-radius: 0.5rem; font-size: 0.875rem; color: #78350f;">
            <strong>Note:</strong> {{ $record->notes }}
        </div>
    @endif

    @if ($record->ocr_raw === null || $ocr === null)
        <div style="padding: 0.75rem 1rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.875rem; color: #6b7280;">
            Lettura automatica non disponibile
        </div>
    @else
        <div style="padding: 0.75rem 1rem; background: #f9fafb; border-radius: 0.5rem; font-size: 0.875rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
                <div style="min-width: 90px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Tipo</div>
                    <div><strong>{{ $ocrTypeLabel }}</strong></div>
                </div>
                <div style="min-width: 160px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Esercente</div>
                    <div>{{ $ocr->merchantName ?? '—' }}</div>
                </div>
                <div style="min-width: 110px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Data</div>
                    <div>{{ $ocr->date ?? '—' }}</div>
                </div>
                <div style="min-width: 100px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Totale</div>
                    <div>{{ $ocrTotalLabel }}</div>
                </div>
                <div style="min-width: 130px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">P. IVA</div>
                    <div>{{ $ocr->merchantVat ?? '—' }}</div>
                </div>
                <div style="min-width: 110px;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Confidence</div>
                    <div>
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 9999px; background: {{ $ocrConfidenceColor }}; color: white; font-weight: 600;">
                            {{ $ocrConfidencePct !== null ? $ocrConfidencePct.'%' : '—' }}
                        </span>
                    </div>
                </div>
            </div>
            <details style="margin-top: 0.75rem;">
                <summary style="cursor: pointer; color: #4b5563; font-size: 0.8rem;">Altri dettagli</summary>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem;">
                    <div style="min-width: 200px;">
                        <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Indirizzo esercente</div>
                        <div>{{ $ocr->merchantAddress ?? '—' }}</div>
                    </div>
                    <div style="min-width: 100px;">
                        <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Articoli</div>
                        <div>{{ count($ocr->items) }} articoli</div>
                    </div>
                </div>
            </details>
        </div>
    @endif

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
