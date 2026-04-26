<?php

namespace App\Domain\ApiResources\Exceptions;

use RuntimeException;

final class ApiResourceEligibilityException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        private readonly int $httpStatus,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public static function forbidden(string $errorCode, string $message): self
    {
        return new self($errorCode, 403, $message);
    }

    public static function validation(string $errorCode, string $message): self
    {
        return new self($errorCode, 422, $message);
    }
}
