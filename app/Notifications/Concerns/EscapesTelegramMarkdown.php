<?php

namespace App\Notifications\Concerns;

trait EscapesTelegramMarkdown
{
    /**
     * Escape user-supplied text for Telegram's legacy "Markdown" parse mode
     * (only _ * ` [ are escapable there).
     */
    protected function tg(?string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], (string) $text);
    }
}
