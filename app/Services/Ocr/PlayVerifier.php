<?php

namespace App\Services\Ocr;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Models\Play;
use App\Models\Store;
use Illuminate\Support\Collection;

class PlayVerifier
{
    public function verify(Play $play, ?ExtractedDocument $doc): VerificationResult
    {
        if (empty($play->receipt_image)) {
            return new VerificationResult(
                PlayStatus::Banned,
                VerificationType::Auto,
                ['scontrino mancante'],
            );
        }

        $notes = [];

        if ($play->store_id === null) {
            $notes[] = 'store non assegnato';
        }

        if ($doc === null) {
            return new VerificationResult(
                PlayStatus::Banned,
                VerificationType::Auto,
                ['scontrino non riconosciuto'],
            );
        }

        $this->checkDate($doc, $notes);
        $this->checkTotal($doc, $notes);
        if ($play->store_id !== null) {
            $this->checkMerchant($play, $doc, $notes);
        }
        $this->checkConfidence($doc, $notes);

        if ($notes === []) {
            return new VerificationResult(PlayStatus::Validated, VerificationType::Auto, []);
        }

        return new VerificationResult(PlayStatus::Pending, VerificationType::Auto, $notes);
    }

    private function checkDate(ExtractedDocument $doc, array &$notes): void
    {
        $start = (string) config('app.concorso_start_date', '');
        $end = (string) config('app.concorso_end_date', '');

        $date = $doc->date;
        if ($date === null || $start === '' || $end === '' || $date < $start || $date > $end) {
            $dateStr = $date ?? 'N/D';
            $notes[] = "non torna data scontrino ({$dateStr})";
        }
    }

    private function checkTotal(ExtractedDocument $doc, array &$notes): void
    {
        if ($doc->total === null || $doc->total < 1.00) {
            $totalStr = $doc->total === null ? 'N/D' : number_format($doc->total, 2, ',', '').'€';
            $notes[] = "non torna importo ({$totalStr})";
        }
    }

    private function checkMerchant(Play $play, ExtractedDocument $doc, array &$notes): void
    {
        $store = $play->store;
        if ($store === null) {
            return;
        }

        $docVat = $this->normalizeVat($doc->merchantVat);
        $storeVat = $this->normalizeVat($store->vat_number);

        if ($docVat !== null && $storeVat !== null) {
            if ($docVat === $storeVat) {
                return;
            }
            $notes[] = 'non torna punto vendita';
            $this->appendVatLookupNote($docVat, $notes);

            return;
        }

        $storeName = (string) ($store->sign_name ?: $store->name);
        $nameOk = false;
        if ($doc->merchantName !== null && $storeName !== '') {
            similar_text(
                mb_strtoupper($doc->merchantName),
                mb_strtoupper($storeName),
                $percent,
            );
            $nameOk = $percent >= 80.0;
        }

        $address = (string) $doc->merchantAddress;
        $cityOk = $store->city !== null
            && $store->city !== ''
            && stripos($address, (string) $store->city) !== false;

        if (! ($nameOk || $cityOk)) {
            $notes[] = 'non torna punto vendita';
            if ($docVat !== null) {
                $this->appendVatLookupNote($docVat, $notes);
            }
        }
    }

    private function checkConfidence(ExtractedDocument $doc, array &$notes): void
    {
        if ($doc->merchantConfidence !== null && $doc->merchantConfidence < 0.80) {
            $notes[] = 'verifica manuale merchant (confidence bassa)';
        }
    }

    private function normalizeVat(?string $vat): ?string
    {
        if ($vat === null || $vat === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $vat);

        return $digits === '' ? null : $digits;
    }

    /**
     * @return Collection<int, Store>
     */
    private function lookupStoresByVat(string $normalizedVat): Collection
    {
        return Store::query()
            ->whereNotNull('vat_number')
            ->get(['id', 'name', 'sign_name', 'vat_number', 'is_active'])
            ->filter(fn (Store $s) => $this->normalizeVat($s->vat_number) === $normalizedVat)
            ->values();
    }

    private function formatStoreEntry(Store $store): string
    {
        $name = $store->sign_name ?: $store->name;
        $entry = $name.' (#'.$store->id.')';
        if (! $store->is_active) {
            $entry .= ' [inattivo]';
        }

        return $entry;
    }

    private function appendVatLookupNote(string $normalizedVat, array &$notes): void
    {
        $matches = $this->lookupStoresByVat($normalizedVat);

        if ($matches->isEmpty()) {
            $notes[] = 'P.IVA scontrino non in DB stores';

            return;
        }

        if ($matches->count() === 1) {
            $notes[] = 'P.IVA scontrino corrisponde a store: '.$this->formatStoreEntry($matches->first());

            return;
        }

        $entries = $matches->map(fn (Store $s) => $this->formatStoreEntry($s))->implode(', ');
        $notes[] = 'P.IVA scontrino corrisponde a '.$matches->count().' store: '.$entries;
    }
}
