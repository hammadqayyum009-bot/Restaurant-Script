<?php

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\User;

class PaymentTransactionPolicy
{
    public function view(User $user, PaymentTransaction $transaction): bool
    {
        return $user->can('manage-payments');
    }

    /**
     * A separate ability from manage-payments so a future role (e.g. a
     * cashier who should never touch driver credentials) can be granted
     * mark-payment-paid without also getting settings access.
     */
    public function markPaid(User $user, PaymentTransaction $transaction): bool
    {
        return $user->can('mark-payment-paid') && $transaction->status === 'pending';
    }
}
