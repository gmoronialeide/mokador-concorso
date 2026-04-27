<?php

namespace App\Services\Ocr;

class ReceiptExtractor
{
    public function fromAzureResponse(array $response): ?ExtractedDocument
    {
        $documents = $response['analyzeResult']['documents'] ?? [];
        if ($documents === []) {
            return null;
        }

        $document = $documents[0];
        $fields = $document['fields'] ?? [];
        $isInvoice = str_starts_with((string) ($document['docType'] ?? ''), 'invoice');

        $merchantNameField = $fields[$isInvoice ? 'VendorName' : 'MerchantName'] ?? null;
        $merchantAddressField = $fields[$isInvoice ? 'VendorAddress' : 'MerchantAddress'] ?? null;
        $merchantVatField = $fields[$isInvoice ? 'VendorTaxId' : 'MerchantTaxId'] ?? null;
        $dateField = $fields[$isInvoice ? 'InvoiceDate' : 'TransactionDate'] ?? null;
        $totalField = $fields[$isInvoice ? 'InvoiceTotal' : 'Total'] ?? null;

        $merchantVat = $this->stringOf($merchantVatField);
        if ($merchantVat === null) {
            $content = (string) ($response['analyzeResult']['content'] ?? '');
            $merchantVat = $this->extractVatFromContent($content);
        }

        return new ExtractedDocument(
            type: $isInvoice ? 'invoice' : 'receipt',
            merchantName: $this->stringOf($merchantNameField),
            merchantAddress: $this->addressOf($merchantAddressField),
            merchantVat: $merchantVat,
            merchantConfidence: isset($merchantNameField['confidence'])
                ? (float) $merchantNameField['confidence']
                : null,
            date: $this->stringOf($dateField),
            total: $totalField !== null ? $this->totalOf($totalField) : null,
            items: $fields['Items']['valueArray'] ?? [],
            raw: $response,
        );
    }

    private function stringOf(?array $field): ?string
    {
        if ($field === null) {
            return null;
        }

        return $field['valueString']
            ?? $field['valueDate']
            ?? $field['content']
            ?? null;
    }

    private function addressOf(?array $field): ?string
    {
        if ($field === null) {
            return null;
        }

        if (isset($field['valueAddress'])) {
            $a = $field['valueAddress'];

            return trim(implode(' ', array_filter([
                $a['streetAddress'] ?? null,
                $a['postalCode'] ?? null,
                $a['city'] ?? null,
                $a['state'] ?? null,
            ])));
        }

        return $field['content'] ?? null;
    }

    private function totalOf(array $field): ?float
    {
        if (isset($field['valueCurrency']['amount'])) {
            return (float) $field['valueCurrency']['amount'];
        }
        if (isset($field['valueNumber'])) {
            return (float) $field['valueNumber'];
        }

        return null;
    }

    private function extractVatFromContent(string $content): ?string
    {
        $pattern = '/(?:P\.?\s*IVA|PART\.?\s*IVA|PARTITA\s+IVA|P\.?\s*I\.?\b|VAT(?:\s+number)?)\b[\s.:\/-]*(?:IT)?[\s.:\/-]*(\d{11})\b/i';
        if (preg_match($pattern, $content, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
