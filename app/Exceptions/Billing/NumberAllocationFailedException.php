<?php

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * Thrown when App\Services\Billing\DocumentNumberer cannot allocate a unique
 * number within config('billing.max_allocation_attempts') tries. Never retried
 * beyond that bound.
 */
class NumberAllocationFailedException extends RuntimeException
{
}
