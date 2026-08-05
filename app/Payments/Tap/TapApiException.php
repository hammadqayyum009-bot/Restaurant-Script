<?php

namespace App\Payments\Tap;

use RuntimeException;

/**
 * Covers both a reachable-but-error response and total unreachability
 * (connection failure, timeout) — callers distinguish the two, if they need
 * to, via isConnectionFailure() rather than a separate exception class.
 */
class TapApiException extends RuntimeException
{
    public function __construct(string $message, protected bool $connectionFailure = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function isConnectionFailure(): bool
    {
        return $this->connectionFailure;
    }
}
