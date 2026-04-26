<?php

namespace App\Domain\Issuer\Exceptions;

use RuntimeException;

final class SigningKeyStateException extends RuntimeException
{
    public static function missingActiveKey(): self
    {
        return new self('signing_key_missing');
    }

    public static function invalidState(): self
    {
        return new self('invalid_signing_key_state');
    }

    public static function generationFailed(): self
    {
        return new self('signing_key_generation_failed');
    }

    public static function unsupportedAlgorithm(string $algorithm): self
    {
        return new self("unsupported_signing_algorithm:{$algorithm}");
    }

    public static function privateKeyDecryptFailed(): self
    {
        return new self('private_key_decrypt_failed');
    }
}
