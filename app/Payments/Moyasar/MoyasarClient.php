<?php

namespace App\Payments\Moyasar;

use App\Models\PaymentGatewayLog;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Every call goes through request(), which enforces an explicit timeout,
 * logs the attempt (redacted) to payment_gateway_logs, and only retries a
 * GET that failed at the network level — never a POST, and never on an HTTP
 * error response (that's a real answer from Moyasar, not a transient
 * failure, and blind-retrying a payment or refund creation risks a
 * duplicate).
 */
class MoyasarClient
{
    public const BASE_URL = 'https://api.moyasar.com/v1';
    public const CONNECT_TIMEOUT_SECONDS = 5;
    public const REQUEST_TIMEOUT_SECONDS = 15;

    public function __construct(protected string $secretKey, protected string $driver = 'moyasar')
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createInvoice(array $payload, ?PaymentTransaction $transaction = null): array
    {
        return $this->request('POST', '/invoices', $payload, retryable: false, transaction: $transaction);
    }

    /** @return array<string, mixed> */
    public function fetchInvoice(string $invoiceId, ?PaymentTransaction $transaction = null): array
    {
        return $this->request('GET', '/invoices/'.$invoiceId, [], retryable: true, transaction: $transaction);
    }

    /** @return array<string, mixed> */
    public function refund(string $paymentId, ?int $amountMinor, ?PaymentTransaction $transaction = null): array
    {
        $payload = $amountMinor !== null ? ['amount' => $amountMinor] : [];

        return $this->request('POST', '/payments/'.$paymentId.'/refund', $payload, retryable: false, transaction: $transaction);
    }

    /**
     * A harmless, read-only authenticated call used by the admin "test
     * connection" button — never creates or touches a real invoice.
     */
    public function testConnection(): bool
    {
        try {
            $this->request('GET', '/invoices?per_page=1', [], retryable: true);

            return true;
        } catch (MoyasarApiException) {
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
                $response = Http::withBasicAuth($this->secretKey, '')
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->{strtolower($method)}(self::BASE_URL.$endpoint, $payload);

                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $body = $response->json() ?? [];

                $this->log($transaction, $method, $endpoint, $response->status(), $durationMs, $payload, $body);

                if ($response->failed()) {
                    throw new MoyasarApiException(
                        "Moyasar API returned HTTP {$response->status()} for {$method} {$endpoint}.",
                    );
                }

                return $body;
            } catch (ConnectionException $e) {
                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $this->log($transaction, $method, $endpoint, null, $durationMs, $payload, ['error' => 'connection_failed']);
                $lastException = new MoyasarApiException(
                    "Could not reach Moyasar for {$method} {$endpoint}: {$e->getMessage()}",
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

    /**
     * @param  array<string, mixed>  $request
     * @param  array<string, mixed>  $response
     */
    protected function log(
        ?PaymentTransaction $transaction,
        string $method,
        string $endpoint,
        ?int $status,
        int $durationMs,
        array $request,
        array $response,
    ): void {
        PaymentGatewayLog::create([
            'payment_transaction_id' => $transaction?->id,
            'driver' => $this->driver,
            'endpoint' => $endpoint,
            'http_method' => $method,
            'http_status' => $status,
            'duration_ms' => $durationMs,
            'provider_reference' => $response['id'] ?? $transaction?->provider_reference,
            'request_summary' => json_encode(self::redact($request)),
            'response_summary' => json_encode(self::redact($response)),
        ]);
    }

    /**
     * Masks anything that looks like a secret or a card number before it
     * ever reaches the database — the Basic Auth secret key is never part of
     * $request/$response to begin with (it's a header, not logged here at
     * all), this is defense in depth for whatever Moyasar's own response
     * bodies might echo back.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function redact(array $data): array
    {
        $sensitiveKeyPattern = '/token|secret|password|cvc|cvv|api[_-]?key/i';

        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::redact($value);

                continue;
            }

            if (is_string($key) && preg_match($sensitiveKeyPattern, $key)) {
                $result[$key] = '[redacted]';

                continue;
            }

            if (is_string($value) && preg_match('/^\d{13,19}$/', str_replace(' ', '', $value))) {
                $result[$key] = '[redacted-card-like-number]';

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
