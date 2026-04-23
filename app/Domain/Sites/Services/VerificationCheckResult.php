<?php

namespace App\Domain\Sites\Services;

final readonly class VerificationCheckResult
{
    public function __construct(
        public bool $passed,
        public ?string $errorCode = null,
    ) {
    }

    public static function passed(): self
    {
        return new self(true);
    }

    public static function failed(string $errorCode): self
    {
        return new self(false, $errorCode);
    }
}
