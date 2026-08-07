<?php

namespace App\Payments;

/**
 * The single, authoritative legal-transition table for a payment
 * transaction's status. Nothing outside PaymentTransactionStatusService
 * should ever write to payment_transactions.status directly — that would
 * bypass this table.
 *
 * paid is terminal for the forward path: it can only move to refunded or
 * partially_refunded, never back to pending/processing/failed/cancelled. A
 * late "failed" webhook arriving after a payment already cleared must be
 * logged and dropped, not applied — see PaymentTransactionStatusService.
 *
 * failed/cancelled are terminal outright: a retried payment attempt is a
 * new PaymentTransaction row (initiate() is called again), not a reused one.
 */
class PaymentStatusTransitions
{
    /** @var array<string, list<string>> */
    private const MAP = [
        'pending' => ['processing', 'paid', 'failed', 'cancelled'],
        'processing' => ['paid', 'failed', 'cancelled'],
        'paid' => ['refunded', 'partially_refunded'],
        'partially_refunded' => ['partially_refunded', 'refunded'],
        'failed' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

    public static function isLegal(PaymentStatus $from, PaymentStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to->value, self::MAP[$from->value] ?? [], true);
    }
}
