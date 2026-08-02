<?php

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * A single TLV length byte can only express 0-255. Thrown rather than
 * truncating, because a truncated value would still scan — with wrong tax data.
 */
class TlvValueTooLongException extends RuntimeException
{
}
