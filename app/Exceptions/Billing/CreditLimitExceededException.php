<?php

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * Thrown when a credit note would credit more quantity on a line, or more
 * money on the document as a whole, than the original document has left to
 * give — checked cumulatively across every credit note ever issued against
 * that original, not just the one being submitted.
 */
class CreditLimitExceededException extends RuntimeException
{
}
