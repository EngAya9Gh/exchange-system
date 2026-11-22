<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Business-rule failure while placing/processing a SIM order.
 * The message is already translated and safe to show to the user;
 * the code ("insufficient_balance", "package_unavailable", …) is for the partner API.
 */
class SimOrderException extends RuntimeException
{
    public function __construct(string $message = '', public readonly string $errorCode = 'order_error')
    {
        parent::__construct($message);
    }
}
