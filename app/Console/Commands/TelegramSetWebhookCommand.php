<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Public HTTPS webhook URL (defaults to APP_URL/telegram/webhook)}';

    protected $description = 'Register the Telegram bot webhook URL';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return self::FAILURE;
        }

        $url = $this->argument('url') ?: rtrim((string) config('app.url'), '/').'/telegram/webhook';
        $secret = config('services.telegram.webhook_secret');

        $res = $telegram->setWebhook($url, $secret ?: null);

        if (! ($res['ok'] ?? false)) {
            $this->error('Failed to set webhook: '.($res['description'] ?? 'Unknown error'));
            return self::FAILURE;
        }

        $this->info("Webhook registered: {$url}");
        return self::SUCCESS;
    }
}
