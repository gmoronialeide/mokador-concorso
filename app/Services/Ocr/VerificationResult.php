<?php

namespace App\Services\Ocr;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;

final readonly class VerificationResult
{
    public function __construct(
        public PlayStatus $status,
        public VerificationType $type,
        public array $notes,
    ) {}

    public function noteString(): string
    {
        return implode("\n", array_map(
            fn (string $n) => 'controllo automatico: '.$n,
            $this->notes,
        ));
    }
}
