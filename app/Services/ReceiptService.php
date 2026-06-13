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
     * Generate PDF receipt for a transfer and return its public URL.
     *
     * @param Transfer $transfer
     * @return string Publicly accessible URL of the receipt PDF
     */
    public function generatePdf(Transfer $transfer): string
    {
        $currencyName = 'عملة';
        switch (strtoupper($transfer->target_currency)) {
            case 'EGP':
                $currencyName = 'جنيه مصري';
                break;
            case 'TRY':
                $currencyName = 'ليرة تركية';
                break;
            case 'USD':
                $currencyName = 'دولار أمريكي';
                break;
            case 'EUR':
                $currencyName = 'يورو';
                break;
        }

        $amountInWords = ArabicNumberToWords::convert((float) $transfer->received_amount, $currencyName);

        // Generate QR code using Google Charts API (fast, reliable, requires no extra dependencies)
        $qrData = route('transfers.verify', ['number' => $transfer->transfer_number]);
        $qrCodeUrl = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($qrData);

        $pdf = Pdf::loadView('receipts.transfer', compact('transfer', 'amountInWords', 'qrCodeUrl'))
            ->setPaper('a6', 'portrait');

        // Ensure directories exist
        if (!Storage::disk('public')->exists('receipts')) {
            Storage::disk('public')->makeDirectory('receipts');
        }

        $fileName = 'receipts/' . $transfer->transfer_number . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        return Storage::url($fileName);
    }
}
