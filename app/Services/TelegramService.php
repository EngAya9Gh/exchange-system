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
            $response = Http::post($this->apiUrl . 'sendMessage', $payload);
            
            if (!$response->successful()) {
                Log::error('Telegram API Error (sendMessage): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (sendMessage): ' . $e->getMessage());
            return false;
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
            $response = Http::post($this->apiUrl . 'sendDocument', $payload);
            
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
            $response = Http::post($this->apiUrl . 'editMessageText', $payload);
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
            $response = Http::post($this->apiUrl . 'answerCallbackQuery', $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram API Exception (answerCallbackQuery): ' . $e->getMessage());
            return false;
        }
    }
}
