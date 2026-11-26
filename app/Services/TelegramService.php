<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?: config('services.telegram.bot_token');
    }

    public function isConfigured(): bool
    {
        return filled($this->botToken);
    }

    private function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.$this->botToken.'/'.$method;
    }

    /**
     * Send a text message to a Telegram chat ID.
     *
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, description?: string, result?: mixed}
     */
    public function sendMessage(string $chatId, string $text, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
        }

        $chunks = $this->splitText($text, 4000);
        $lastResponse = ['ok' => false, 'description' => 'Empty message.'];

        foreach ($chunks as $chunk) {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text'    => $chunk,
            ], $options);

            try {
                $res = Http::connectTimeout(10)->timeout(20)->post($this->endpoint('sendMessage'), $payload);
                $data = $res->json() ?: [];

                if (! $res->successful() && isset($payload['parse_mode'])) {
                    unset($payload['parse_mode']);
                    $res = Http::connectTimeout(10)->timeout(20)->post($this->endpoint('sendMessage'), $payload);
                    $data = $res->json() ?: [];
                }

                $lastResponse = is_array($data) ? $data : ['ok' => false];
            } catch (\Throwable $e) {
                Log::warning('Telegram sendMessage error: '.$e->getMessage());
                return ['ok' => false, 'description' => $e->getMessage()];
            }
        }

        return $lastResponse;
    }

    public function sendChatAction(string $chatId, string $action = 'typing'): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            Http::connectTimeout(5)->timeout(5)->post($this->endpoint('sendChatAction'), [
                'chat_id' => $chatId,
                'action'  => $action,
            ]);
        } catch (\Throwable) {
            // Non-critical indicator; ignore errors.
        }
    }

    /**
     * Long-poll updates for local development without HTTPS webhooks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $timeout = 25): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $res = Http::connectTimeout(10)->timeout($timeout + 10)->get($this->endpoint('getUpdates'), [
                'offset'          => $offset,
                'timeout'         => $timeout,
                'allowed_updates' => json_encode(['message']),
            ]);

            if (! $res->successful()) {
                Log::warning('Telegram getUpdates failed', ['status' => $res->status(), 'body' => $res->body()]);
                return [];
            }

            $result = $res->json('result');
            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            Log::warning('Telegram getUpdates error: '.$e->getMessage());
            return [];
        }
    }

    /**
     * @return array{ok: bool, description?: string}
     */
    public function setWebhook(string $url, ?string $secret = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
        }

        $payload = [
            'url'             => $url,
            'allowed_updates' => ['message'],
        ];

        if (filled($secret)) {
            $payload['secret_token'] = $secret;
        }

        $res = Http::connectTimeout(10)->timeout(20)->post($this->endpoint('setWebhook'), $payload);
        return $res->json() ?: ['ok' => false, 'description' => 'Unknown error'];
    }

    /**
     * @return array{ok: bool, description?: string}
     */
    public function deleteWebhook(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
        }

        $res = Http::connectTimeout(10)->timeout(15)->post($this->endpoint('deleteWebhook'));
        return $res->json() ?: ['ok' => false];
    }

    /**
     * @return array{ok: bool, result?: array<string, mixed>, description?: string}
     */
    public function getMe(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram bot token is not configured.'];
        }

        try {
            $res = Http::connectTimeout(10)->timeout(10)->get($this->endpoint('getMe'));
            return $res->json() ?: ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitText(string $text, int $maxLen): array
    {
        if (mb_strlen($text) <= $maxLen) {
            return [$text];
        }

        $chunks = [];
        $remaining = $text;

        while (mb_strlen($remaining) > $maxLen) {
            $slice = mb_substr($remaining, 0, $maxLen);
            $breakPos = mb_strrpos($slice, "\n");
            if ($breakPos !== false && $breakPos > (int) ($maxLen * 0.5)) {
                $slice = mb_substr($remaining, 0, $breakPos);
                $remaining = ltrim(mb_substr($remaining, $breakPos));
            } else {
                $remaining = mb_substr($remaining, $maxLen);
            }
            $chunks[] = $slice;
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }
}
