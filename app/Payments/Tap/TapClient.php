<?php

namespace App\Payments\Tap;

use App\Models\PaymentTransaction;
use App\Payments\LogsGatewayRequestsSafely;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Same shape and retry/timeout discipline as MoyasarClient — Bearer auth
 * instead of Basic, different endpoint paths, and amounts are sent as
 * pre-formatted decimal strings (Tap's wire format), never integers and
 * never PHP floats. redact()/log() come from LogsGatewayRequestsSafely,
 * shared with MoyasarClient so the two can never drift on what gets masked.
 */
class TapClient
{
    use LogsGatewayRequestsSafely;

    public const BASE_URL = 'https://api.tap.company/v2';

    public const CONNECT_TIMEOUT_SECONDS = 5;

    public const REQUEST_TIMEOUT_SECONDS = 15;

    public function __construct(protected string $secretKey, protected string $driver = 'tap') {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCharge(array $payload, ?PaymentTransaction $transaction = null): array
    {
        return $this->request('POST', '/charges', $payload, retryable: false, transaction: $transaction);
    }

    /** @return array<string, mixed> */
    public function fetchCharge(string $chargeId, ?PaymentTransaction $transaction = null): array
    {
        return $this->request('GET', '/charges/'.$chargeId, [], retryable: true, transaction: $transaction);
    }

    /**
     * $amountDecimal must already be the pre-formatted string
     * Money::toDecimal() produces (or null for a full refund) — never a
     * float, never re-derived here.
     *
     * @return array<string, mixed>
     */
    public function refund(string $chargeId, ?string $amountDecimal, ?string $currency, string $reason, ?PaymentTransaction $transaction = null): array
    {
        $payload = ['charge_id' => $chargeId, 'reason' => $reason];

        if ($amountDecimal !== null) {
            $payload['amount'] = $amountDecimal;
            $payload['currency'] = $currency;
        }

        return $this->request('POST', '/refunds', $payload, retryable: false, transaction: $transaction);
    }

    /**
     * A harmless, read-only authenticated call used by the admin "test
     * connection" button — never creates or touches a real charge.
     */
    public function testConnection(): bool
    {
        try {
            $this->request('GET', '/charges?limit=1', [], retryable: true);

            return true;
        } catch (TapApiException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(string $method, string $endpoint, array $payload, bool $retryable, ?PaymentTransaction $transaction = null): array
    {
        $attempts = $retryable ? 2 : 1;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $start = microtime(true);

            try {
                $response = Http::withToken($this->secretKey)
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->{strtolower($method)}(self::BASE_URL.$endpoint, $payload);

                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $body = $response->json() ?? [];

                $this->log($transaction, $method, $endpoint, $response->status(), $durationMs, $payload, $body);

                if ($response->failed()) {
                    throw new TapApiException(
                        "Tap API returned HTTP {$response->status()} for {$method} {$endpoint}.",
                    );
                }

                return $body;
            } catch (ConnectionException $e) {
                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $this->log($transaction, $method, $endpoint, null, $durationMs, $payload, ['error' => 'connection_failed']);
                $lastException = new TapApiException(
                    "Could not reach Tap for {$method} {$endpoint}: {$e->getMessage()}",
                    connectionFailure: true,
                    previous: $e,
                );

                if ($attempt < $attempts) {
                    usleep(300_000);

                    continue;
                }
            }
        }

        throw $lastException;
    }
}
