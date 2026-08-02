<?php

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * Thrown when a computed document total cannot be reconciled against
 * orders.total to the minor unit. Issuing must stop — see the D2 rule.
 */
class ReconciliationFailedException extends RuntimeException
{
}
