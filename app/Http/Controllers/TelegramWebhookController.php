<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transfer;
use App\Services\TelegramService;
use App\Services\ReceiptService;
use App\Notifications\TransferStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function handle(Request $request)
    {
        $data = $request->all();
        
        Log::info('Telegram Webhook Payload:', $data);

        // Handle standard messages (e.g., /start command)
        if (isset($data['message'])) {
            $this->handleMessage($data['message']);
        }

        // Handle inline keyboard callbacks (e.g., Approve/Reject buttons)
        if (isset($data['callback_query'])) {
            $this->handleCallbackQuery($data['callback_query']);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';

        if (!$chatId || empty($text)) return;

        // Check if the message is the start command with a token
        if (preg_match('/^\/start\s+(.+)$/', $text, $matches)) {
            $token = $matches[1];
            
            // Find user by token
            $user = User::where('telegram_link_token', $token)->first();

            if ($user) {
                // Link account
                $user->telegram_chat_id = (string) $chatId;
                $user->telegram_link_token = null; // Invalidate token after linking
                $user->save();

                $this->telegramService->sendMessage((string) $chatId, "🎉 أهلاً بك يا {$user->name}!\n\nتم ربط حسابك في نظام الحوالات بنجاح. ستتلقى جميع إشعاراتك، وأرقام الـ OTP، وصولات الحوالات عبر هذه المحادثة مباشرةً.");
            } else {
                $this->telegramService->sendMessage((string) $chatId, "⚠️ رمز الربط غير صحيح أو منتهي الصلاحية.");
            }
        } elseif ($text === '/start') {
            $this->telegramService->sendMessage((string) $chatId, "مرحباً بك في بوت نظام الحوالات. لربط حسابك، يرجى الضغط على الزر المخصص داخل لوحة التحكم الخاصة بك في النظام.");
        }
    }

    protected function handleCallbackQuery(array $callbackQuery)
    {
        $callbackQueryId = $callbackQuery['id'];
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $fromId = $callbackQuery['from']['id'] ?? null;
        $fromName = $callbackQuery['from']['first_name'] ?? 'إداري';

        if (!$chatId || !$data) return;

        // Parse action
        // Example data: approve_transfer_123 or reject_transfer_123
        $parts = explode('_', $data);
        if (count($parts) < 3) return;

        $action = $parts[0]; // approve or reject
        $entity = $parts[1]; // transfer
        $id = $parts[2];

        if ($entity !== 'transfer') return;

        $transfer = Transfer::find($id);

        if (!$transfer) {
            $this->telegramService->answerCallbackQuery($callbackQueryId, '⚠️ الحوالة غير موجودة.', true);
            return;
        }

        if ($transfer->status !== 'pending' && $transfer->status !== 'new') {
            $this->telegramService->answerCallbackQuery($callbackQueryId, '⚠️ هذه الحوالة تمت معالجتها مسبقاً!', true);
            // Optionally, edit message to remove buttons
            $this->removeInlineKeyboard($chatId, $messageId, $callbackQuery['message']['text'], "تمت المعالجة مسبقاً.");
            return;
        }

        if ($action === 'approve') {
            // Approve transfer
            $transfer->status = 'received';
            $transfer->admin_notes = "تم القبول عبر تلغرام بواسطة: {$fromName}";
            
            if (empty($transfer->secret_code)) {
                $transfer->secret_code = str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            }
            
            $transfer->save();

            // Answer callback to remove loading
            $this->telegramService->answerCallbackQuery($callbackQueryId, '✅ تم قبول الحوالة بنجاح.');

            // Notify client
            try {
                if ($transfer->user_id) {
                    $transfer->user->notify(new TransferStatusNotification($transfer, 'paid'));
                }
            } catch (\Exception $e) {
                Log::error("Failed to notify user on Telegram transfer approval: " . $e->getMessage());
            }

            // Update the message text to show it was approved and add the receipt button
            try {
                $receiptService = app(ReceiptService::class);
                $pdfUrl = $receiptService->generatePdf($transfer);
                $absoluteUrl = url($pdfUrl);
                
                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📄 عرض الإيصال', 'url' => $absoluteUrl]
                        ]
                    ]
                ];
                
                $newText = $callbackQuery['message']['text'] . "\n\n✅ *الحالة:* مكتملة (بواسطة {$fromName})\n🔑 *الرقم السري:* `{$transfer->secret_code}`";
                $this->telegramService->editMessageText((string) $chatId, (string) $messageId, $newText, $replyMarkup);
                
            } catch (\Exception $e) {
                $newText = $callbackQuery['message']['text'] . "\n\n✅ *الحالة:* مكتملة (بواسطة {$fromName})\n🔑 *الرقم السري:* `{$transfer->secret_code}`";
                $this->telegramService->editMessageText((string) $chatId, (string) $messageId, $newText);
                Log::error("Failed to generate/send receipt link to Admin via Telegram for transfer {$transfer->id}: " . $e->getMessage());
            }

        } elseif ($action === 'reject') {
            // Reject transfer
            $transfer->status = 'rejected';
            $transfer->admin_notes = "تم الرفض عبر تلغرام بواسطة: {$fromName} - لعدم توافق البيانات.";
            $transfer->save();

            // Refund user balance if applicable
            if ($transfer->user_id && $transfer->user) {
                $transferUserIsAdmin = $transfer->user->hasRole('Super Admin') || $transfer->user->role === 'admin';
                if (!$transferUserIsAdmin) {
                    // Refund = amount + commission
                    $refundAmount = $transfer->amount + $transfer->commission;
                    $transfer->user->balance += $refundAmount;
                    $transfer->user->save();
                }
            }

            // Answer callback
            $this->telegramService->answerCallbackQuery($callbackQueryId, '❌ تم رفض الحوالة.');

            // Notify client
            try {
                if ($transfer->user_id) {
                    $transfer->user->notify(new TransferStatusNotification($transfer, 'cancelled'));
                }
            } catch (\Exception $e) {
                Log::error("Failed to notify user on Telegram transfer rejection: " . $e->getMessage());
            }

            // Update the message text to show it was rejected
            $newText = $callbackQuery['message']['text'] . "\n\n❌ *الحالة:* مرفوضة (بواسطة {$fromName})";
            $this->telegramService->editMessageText((string) $chatId, (string) $messageId, $newText);
        }
    }

    protected function removeInlineKeyboard($chatId, $messageId, $originalText, $appendedText = '')
    {
        $newText = $originalText . "\n\n" . $appendedText;
        $this->telegramService->editMessageText((string) $chatId, (string) $messageId, $newText);
    }
}
