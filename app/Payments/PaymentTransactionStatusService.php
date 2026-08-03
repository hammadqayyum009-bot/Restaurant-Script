<?php

namespace App\Payments;

use App\Models\PaymentStatusLog;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\ValueObjects\PaymentStatusTransitionResult;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single place payment_transactions.status is ever written. A webhook
 * and a customer callback for the same payment race here: whichever request
 * reaches the row lock first applies its transition inside one DB
 * transaction; the second either finds the same status already applied
 * (logged as a no-op, no side effects run twice) or finds the transition now
 * illegal (e.g. a late "failed" arriving after "paid" already landed — logged
 * and dropped, never applied, transaction row untouched).
 */
class PaymentTransactionStatusService
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_CALLBACK = 'callback';

    public const SOURCE_WEBHOOK = 'webhook';

    public const SOURCE_VERIFICATION = 'verification';

    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_CALLBACK,
        self::SOURCE_WEBHOOK,
        self::SOURCE_VERIFICATION,
    ];

    /**
     * @param  Closure(PaymentTransaction):void|null  $withinTransaction  Runs
     *                                                                    inside the same DB transaction and row lock, only when the
     *                                                                    transition is actually applied — e.g. to advance the linked
     *                                                                    Order's own status atomically with the payment status write.
     *                                                                    Never runs on a no-op or a rejected transition, which is what
     *                                                                    keeps a duplicate "mark paid" from running its side effects twice.
     */
    public function transitionTo(
        PaymentTransaction $transaction,
        PaymentStatus $to,
        string $source,
        ?User $actor = null,
        ?string $note = null,
        ?Closure $withinTransaction = null,
    ): PaymentStatusTransitionResult {
        if (! in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException("Unknown payment status transition source: \"{$source}\".");
        }

        return DB::transaction(function () use ($transaction, $to, $source, $actor, $note, $withinTransaction) {
            /** @var PaymentTransaction $locked */
            $locked = PaymentTransaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            $from = PaymentStatus::from($locked->status);

            if ($from === $to) {
                $this->log($locked, $from, $to, $source, $actor, $note ?? 'No-op: already '.$to->value.'.');

                return new PaymentStatusTransitionResult(false, $locked);
            }

            if (! PaymentStatusTransitions::isLegal($from, $to)) {
                $this->log($locked, $from, $to, $source, $actor, $note ?? 'Rejected: illegal transition, ignored.');

                return new PaymentStatusTransitionResult(false, $locked);
            }

            $locked->status = $to->value;

            if ($to === PaymentStatus::Paid && $locked->paid_at === null) {
                $locked->paid_at = now();
            }

            $locked->save();

            $this->log($locked, $from, $to, $source, $actor, $note);

            if ($withinTransaction !== null) {
                $withinTransaction($locked);
            }

            return new PaymentStatusTransitionResult(true, $locked);
        });
    }

    protected function log(
        PaymentTransaction $transaction,
        PaymentStatus $from,
        PaymentStatus $to,
        string $source,
        ?User $actor,
        ?string $note,
    ): void {
        PaymentStatusLog::create([
            'payment_transaction_id' => $transaction->id,
            'actor_id' => $actor?->id,
            'old_status' => $from->value,
            'new_status' => $to->value,
            'source' => $source,
            'note' => $note,
        ]);
    }
}
