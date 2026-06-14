<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transfer;
use App\Helpers\ArabicNumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /**
     * Return the public URL of the receipt web view.
     *
     * @param Transfer $transfer
     * @return string Publicly accessible URL of the receipt
     */
    public function generatePdf(Transfer $transfer): string
    {
        return route('receipt.view', ['number' => $transfer->transfer_number]);
    }
}
