<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Business-rule failure while placing/processing a SIM order.
 * The message is already translated and safe to show to the user.
 */
class SimOrderException extends RuntimeException
{
}
