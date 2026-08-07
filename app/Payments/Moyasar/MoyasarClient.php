<?php

namespace App\Payments\Moyasar;

use App\Models\PaymentTransaction;
use App\Payments\LogsGatewayRequestsSafely;
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
    use LogsGatewayRequestsSafely;

    public const BASE_URL = 'https://api.moyasar.com/v1';

    public const CONNECT_TIMEOUT_SECONDS = 5;

    public const REQUEST_TIMEOUT_SECONDS = 15;

    public function __construct(protected string $secretKey, protected string $driver = 'moyasar') {}

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
}
