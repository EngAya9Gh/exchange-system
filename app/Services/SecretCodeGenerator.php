<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transfer;

class SecretCodeGenerator
{
    /**
     * Generate a unique 5-digit secret code.
     *
     * @return string
     */
    public function generate(): string
    {
        do {
            // Generate a 5-digit number
            $code = (string) random_int(10000, 99999);
        } while (Transfer::where('secret_code', $code)->whereIn('status', ['pending'])->exists());

        return $code;
    }
}
