<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Transfer;
use Illuminate\Support\Facades\Log;

class RecipientWhatsAppService
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager
    ) {}

    /**
     * Send transfer notification message to recipient's phone via WhatsApp.
     *
     * @param Transfer $transfer
     * @return bool
     */
    public function sendTransferMessage(Transfer $transfer): bool
    {
        if (empty($transfer->recipient_phone)) {
            Log::warning("RecipientWhatsAppService: Transfer {$transfer->id} has no recipient phone.");
            return false;
        }

        $formattedPhone = self::formatPhoneForWhatsApp($transfer->recipient_phone);
        if (!$formattedPhone) {
            Log::warning("RecipientWhatsAppService: Could not format recipient phone [{$transfer->recipient_phone}] for Transfer {$transfer->id}.");
            return false;
        }

        $message = $this->buildMessage($transfer);

        try {
            Log::info("RecipientWhatsAppService: Sending WhatsApp to recipient [{$formattedPhone}] for Transfer {$transfer->transfer_number}.");
            $result = $this->whatsAppManager->send($formattedPhone, $message);
            if ($result) {
                Log::info("RecipientWhatsAppService: WhatsApp successfully sent to recipient [{$formattedPhone}].");
            } else {
                Log::error("RecipientWhatsAppService: WhatsApp provider returned false for [{$formattedPhone}].");
            }
            return $result;
        } catch (\Exception $e) {
            Log::error("RecipientWhatsAppService Exception: " . $e->getMessage(), [
                'transfer_id' => $transfer->id,
                'recipient_phone' => $formattedPhone,
            ]);
            return false;
        }
    }

    /**
     * Build the message text formatted as requested.
     *
     * @param Transfer $transfer
     * @return string
     */
    public function buildMessage(Transfer $transfer): string
    {
        $amount = (string) (int) round((float) $transfer->received_amount);

        $flag = '🇪🇬';
        $currencyName = 'جنية مصري';
        switch (strtoupper((string) $transfer->target_currency)) {
            case 'TRY':
                $flag = '🇹🇷';
                $currencyName = 'ليرة تركية';
                break;
            case 'USD':
                $flag = '🇺🇸';
                $currencyName = 'دولار أمريكي';
                break;
            case 'EUR':
                $flag = '🇪🇺';
                $currencyName = 'يورو';
                break;
            case 'EGP':
            default:
                $flag = '🇪🇬';
                $currencyName = 'جنية مصري';
                break;
        }

        $code = $transfer->secret_code ?: $transfer->transfer_number;
        $receiptUrl = route('receipt.view', ['number' => $transfer->transfer_number]);

        return "شركة الاستاذ للخدمات المالية\n"
            . "تم تنفيذ قيد لكم\n"
            . "مبلغ  {$amount} {$flag} {$currencyName}\n"
            . "رقم القيد {$code}\n\n"
            . "رابط الإشعار:\n"
            . "{$receiptUrl}";
    }

    /**
     * Normalize and format phone number for WhatsApp with special handling for Egyptian numbers.
     *
     * Handles formats:
     * - Local Egyptian with 0: 01018496164 -> 201018496164
     * - Local Egyptian without 0: 1018496164 -> 201018496164
     * - International Egypt with + or 00: +201018496164 / 00201018496164 -> 201018496164
     * - Local Turkish: 05392065497 -> 905392065497
     * - Other international numbers: strips leading 00 / +
     *
     * @param string $phone
     * @return string|null
     */
    public static function formatPhoneForWhatsApp(string $phone): ?string
    {
        // Convert Arabic/Eastern digits to standard ASCII digits
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $clean = preg_replace('/[^0-9]/', '', $phone);
        $clean = preg_replace('/^00+/', '', (string) $clean);

        if (empty($clean)) {
            return null;
        }

        // Egyptian with mistaken 200 prefix (+20 010... -> 20010...): strip extra 0
        if (preg_match('/^200(1[0125][0-9]{8})$/', $clean, $matches)) {
            return '20' . $matches[1];
        }

        // Egyptian local 11 digits: 010..., 011..., 012..., 015...
        if (preg_match('/^01[0125][0-9]{8}$/', $clean)) {
            return '20' . substr($clean, 1);
        }

        // Egyptian local 10 digits without leading 0: 10..., 11..., 12..., 15...
        if (preg_match('/^1[0125][0-9]{8}$/', $clean)) {
            return '20' . $clean;
        }

        // Egyptian already with country code 20: 2010..., 2011..., 2012..., 2015... (12 digits)
        if (preg_match('/^201[0125][0-9]{8}$/', $clean)) {
            return $clean;
        }

        // Turkish local 11 digits: 05...
        if (preg_match('/^05[0-9]{9}$/', $clean)) {
            return '90' . substr($clean, 1);
        }

        // Turkish local 10 digits without leading 0: 5...
        if (preg_match('/^5[0-9]{9}$/', $clean)) {
            return '90' . $clean;
        }

        // Turkish already with country code 90: 905... (12 digits)
        if (preg_match('/^905[0-9]{9}$/', $clean)) {
            return $clean;
        }

        // Generic: If starts with 0 and has 10 or more digits, strip leading 0
        if (str_starts_with($clean, '0') && strlen($clean) >= 10) {
            $clean = ltrim($clean, '0');
        }

        return $clean;
    }
}
