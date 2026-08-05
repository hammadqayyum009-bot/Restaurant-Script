<?php

namespace App\Payments;

use App\Models\PaymentGatewayLog;
use App\Models\PaymentTransaction;

/**
 * Requires the using class to have a `protected string $driver` property
 * (both MoyasarClient and TapClient do). Redaction and the gateway-log write
 * live here so both providers' clients can never drift apart on what gets
 * masked — a secret-shaped field added and redacted in one client but
 * forgotten in the other is a real leak, not a cosmetic gap.
 */
trait LogsGatewayRequestsSafely
{
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
