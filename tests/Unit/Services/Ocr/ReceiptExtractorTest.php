<?php

namespace Tests\Unit\Services\Ocr;

use App\Services\Ocr\ReceiptExtractor;
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

    private function loadFixture(string $name): array
    {
        $path = dirname(__DIR__, 3).'/fixtures/azure/'.$name;

        return json_decode((string) file_get_contents($path), true);
    }
}
