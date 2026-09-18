<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', ''));
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    /**
     * Send a text message with optional inline keyboard.
     */
    public function sendMessage(string $chatId, string $text, array $replyMarkup = null): bool
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token is not set.');
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            Log::info("Sending Telegram Message to {$chatId}: " . substr($text, 0, 50) . "...");
            $response = Http::timeout(15)->post($this->apiUrl . 'sendMessage', $payload);
            
            if (!$response->successful()) {
                Log::error('Telegram API Error (sendMessage): ' . $response->body());
                return false;
            }

            Log::info("Telegram Message Sent Successfully to {$chatId}");
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (sendMessage): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to WhatsApp Group via Wakeel API
     */
    public function sendToWhatsAppGroup(string $text): void
    {
        $waApiKey = config('services.wakeel_whatsapp.api_key', env('WAKEEL_WHATSAPP_API_KEY'));
        $waGroupId = config('services.wakeel_whatsapp.group_id', env('WAKEEL_WHATSAPP_GROUP_ID'));
        
        if (empty($waApiKey) || empty($waGroupId)) {
            return;
        }

        try {
            Log::info("Sending WhatsApp Group Message: " . substr($text, 0, 50) . "...");
            $response = Http::timeout(10)
                ->withToken($waApiKey)
                ->post('https://provider.wakeel.cc/api/v1/message/send', [
                    'phone' => $waGroupId,
                    'message' => $text
                ]);
                
            if ($response->successful()) {
                Log::info("WhatsApp Group Message Sent Successfully");
            } else {
                Log::error("WhatsApp Group API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Group API Exception: ' . $e->getMessage());
        }
    }

    /**
     * Send a document (e.g., PDF receipt).
     */
    public function sendDocument(string $chatId, string $documentUrl, string $caption = ''): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'document' => $documentUrl,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        try {
            $response = Http::timeout(15)->post($this->apiUrl . 'sendDocument', $payload);
            
            if (!$response->successful()) {
                Log::error('Telegram API Error (sendDocument): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (sendDocument): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Edit a message text.
     */
    public function editMessageText(string $chatId, string $messageId, string $text, array $replyMarkup = null): bool
    {
        if (empty($this->botToken)) return false;
        
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(15)->post($this->apiUrl . 'editMessageText', $payload);
            if (!$response->successful()) {
                Log::error('Telegram API Error (editMessageText): ' . $response->body());
                return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (editMessageText): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send an answer to a callback query to remove the loading state on the button.
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        if (empty($this->botToken)) return false;

        $payload = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ];

        try {
            $response = Http::timeout(15)->post($this->apiUrl . 'answerCallbackQuery', $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (answerCallbackQuery): ' . $e->getMessage());
            return false;
        }
    }
}
