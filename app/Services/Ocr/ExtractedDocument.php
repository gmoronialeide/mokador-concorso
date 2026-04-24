<?php

namespace App\Services\Ocr;

final readonly class ExtractedDocument
{
    public function __construct(
        public string $type,
        public ?string $merchantName,
        public ?string $merchantAddress,
        public ?string $merchantVat,
        public ?float $merchantConfidence,
        public ?string $date,
        public ?float $total,
        public array $items,
        public array $raw,
    ) {}
}
