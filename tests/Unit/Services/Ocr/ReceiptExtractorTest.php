<?php

namespace Tests\Unit\Services\Ocr;

use App\Services\Ocr\ReceiptExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReceiptExtractorTest extends TestCase
{
    private ReceiptExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ReceiptExtractor();
    }

    public function test_returns_null_for_empty_documents(): void
    {
        $raw = $this->loadFixture('receipt_no_documents.json');
        $this->assertNull($this->extractor->fromAzureResponse($raw));
    }

    public function test_extracts_receipt_fields(): void
    {
        $raw = $this->loadFixture('receipt_valid.json');
        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('receipt', $doc->type);
        $this->assertSame('MOKADOR CAFFE', $doc->merchantName);
        $this->assertSame('2026-04-15', $doc->date);
        $this->assertSame(5.40, $doc->total);
        $this->assertGreaterThanOrEqual(0.9, $doc->merchantConfidence);
        $this->assertStringContainsString('48015', (string) $doc->merchantAddress);
        $this->assertStringContainsString('CERVIA', (string) $doc->merchantAddress);
    }

    public function test_extracts_invoice_fields(): void
    {
        $raw = $this->loadFixture('invoice_valid.json');
        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('invoice', $doc->type);
        $this->assertSame('MOKADOR SRL', $doc->merchantName);
        $this->assertSame('01234567890', $doc->merchantVat);
        $this->assertSame('2026-04-15', $doc->date);
        $this->assertSame(12.50, $doc->total);
    }

    public function test_low_confidence_is_preserved(): void
    {
        $raw = $this->loadFixture('receipt_low_confidence.json');
        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertLessThan(0.80, $doc->merchantConfidence);
    }

    public function test_fallback_extracts_vat_from_content_when_merchant_tax_id_missing(): void
    {
        $raw = $this->makeReceiptResponse(
            content: "Pasticceria Romani Soc. Coop.\nVia Monfalcone, 7\nP. Iva : 01679140440\nDOCUMENTO COMMERCIALE",
        );

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('01679140440', $doc->merchantVat);
    }

    public function test_fallback_does_not_run_when_merchant_tax_id_present(): void
    {
        $raw = $this->makeReceiptResponse(
            merchantTaxId: '99999999999',
            content: 'P.IVA 01234567890',
        );

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('99999999999', $doc->merchantVat);
    }

    #[DataProvider('vatLabelVariantsProvider')]
    public function test_fallback_matches_label_variants(string $contentSnippet): void
    {
        $raw = $this->makeReceiptResponse(content: $contentSnippet);

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('01234567890', $doc->merchantVat);
    }

    /** @return array<string, array{0: string}> */
    public static function vatLabelVariantsProvider(): array
    {
        return [
            'P.IVA' => ['P.IVA 01234567890'],
            'PARTITA IVA' => ['PARTITA IVA: 01234567890'],
            'P.I. with IT prefix' => ['P.I. IT01234567890'],
            'C.F./P.IVA' => ['C.F./P.IVA 01234567890'],
            'VAT' => ['VAT 01234567890'],
            'P. Iva with spaces and colon' => ['P. Iva : 01234567890'],
            'PART.IVA' => ['PART.IVA 01234567890'],
            'PART. IVA' => ['PART. IVA 01234567890'],
            'PART IVA' => ['PART IVA 01234567890'],
            'PART.IVA with colon' => ['PART.IVA: 01234567890'],
        ];
    }

    public function test_fallback_returns_null_when_no_label_in_content(): void
    {
        $raw = $this->makeReceiptResponse(
            content: "Via Roma 12\nCAP 01234567890\nTotale 5,40",
        );

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertNull($doc->merchantVat);
    }

    public function test_fallback_returns_null_when_content_missing(): void
    {
        $raw = $this->makeReceiptResponse(content: null);

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertNull($doc->merchantVat);
    }

    public function test_fallback_returns_first_match_on_multiple(): void
    {
        $raw = $this->makeReceiptResponse(
            content: "P.IVA: 01111111111\nP.IVA: 02222222222",
        );

        $doc = $this->extractor->fromAzureResponse($raw);

        $this->assertNotNull($doc);
        $this->assertSame('01111111111', $doc->merchantVat);
    }

    /**
     * @param  array<string, mixed>  $extraFields
     * @return array<string, mixed>
     */
    private function makeReceiptResponse(
        ?string $content = '',
        ?string $merchantTaxId = null,
        array $extraFields = [],
    ): array {
        $fields = $extraFields;
        if ($merchantTaxId !== null) {
            $fields['MerchantTaxId'] = [
                'type' => 'string',
                'valueString' => $merchantTaxId,
                'content' => $merchantTaxId,
                'confidence' => 0.9,
            ];
        }

        $analyze = [
            'documents' => [[
                'docType' => 'receipt.retailMeal',
                'confidence' => 0.9,
                'fields' => $fields,
            ]],
        ];

        if ($content !== null) {
            $analyze['content'] = $content;
        }

        return ['analyzeResult' => $analyze];
    }

    private function loadFixture(string $name): array
    {
        $path = dirname(__DIR__, 3).'/fixtures/azure/'.$name;

        return json_decode((string) file_get_contents($path), true);
    }
}
