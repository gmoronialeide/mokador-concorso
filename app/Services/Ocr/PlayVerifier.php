<?php

namespace App\Services\Ocr;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Models\Play;

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
            $notes[] = 'OCR non riuscito';

            return new VerificationResult(PlayStatus::Pending, VerificationType::Auto, $notes);
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

        if (! empty($doc->merchantVat) && ! empty($store->vat_number)) {
            if (preg_replace('/\D/', '', $doc->merchantVat) === preg_replace('/\D/', '', $store->vat_number)) {
                return;
            }
            $notes[] = 'non torna punto vendita';

            return;
        }

        $address = (string) $doc->merchantAddress;
        $capMatch = preg_match('/\b\d{5}\b/', $address, $m);
        $capOk = $capMatch && $m[0] === (string) $store->cap;

        $cityOk = $store->city !== null
            && $store->city !== ''
            && stripos($address, (string) $store->city) !== false;

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

        if (! ($capOk && $cityOk && $nameOk)) {
            $notes[] = 'non torna punto vendita';
        }
    }

    private function checkConfidence(ExtractedDocument $doc, array &$notes): void
    {
        if ($doc->merchantConfidence !== null && $doc->merchantConfidence < 0.80) {
            $notes[] = 'verifica manuale merchant (confidence bassa)';
        }
    }
}
