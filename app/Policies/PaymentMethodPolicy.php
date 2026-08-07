<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-payments');
    }

    public function view(User $user, PaymentMethod $method): bool
    {
        return $user->can('manage-payments');
    }

    public function update(User $user, PaymentMethod $method): bool
    {
        return $user->can('manage-payments');
    }
}
