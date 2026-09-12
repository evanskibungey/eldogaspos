<?php

namespace App\Services\Sms;

/**
 * Normalised outcome of a send attempt.
 *
 * The gateway's own envelope is not fully documented, so the raw payload is
 * kept alongside the interpreted result: when a live send behaves unexpectedly
 * the log still holds everything needed to correct the parsing.
 */
class SmsResponse
{
    public bool $successful;
    public ?string $uid;
    public ?string $message;
    public ?int $statusCode;
    public array $raw;

    public function __construct(
        bool $successful,
        ?string $uid = null,
        ?string $message = null,
        ?int $statusCode = null,
        array $raw = []
    ) {
        $this->successful = $successful;
        $this->uid = $uid;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->raw = $raw;
    }

    public static function success(?string $uid, array $raw = [], ?int $statusCode = 200): self
    {
        return new self(true, $uid, null, $statusCode, $raw);
    }

    public static function failure(string $message, array $raw = [], ?int $statusCode = null): self
    {
        return new self(false, null, $message, $statusCode, $raw);
    }

    public function failed(): bool
    {
        return !$this->successful;
    }
}
