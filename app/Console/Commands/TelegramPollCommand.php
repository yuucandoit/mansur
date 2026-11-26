<?php

namespace App\Console\Commands;

use App\Services\TelegramBotHandler;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Fetch pending updates once and exit}';

    protected $description = 'Poll Telegram Bot API updates locally and route messages to Luna AI';

    public function handle(TelegramService $telegram, TelegramBotHandler $handler): int
    {
        if (! $telegram->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return self::FAILURE;
        }

        $me = $telegram->getMe();
        if (! ($me['ok'] ?? false)) {
            $this->error('Failed to connect to Telegram: '.($me['description'] ?? 'Unknown error'));
            return self::FAILURE;
        }

        $botUsername = $me['result']['username'] ?? 'bot';
        $this->info("Connected as @{$botUsername}. Clearing any active webhook for polling...");
        $telegram->deleteWebhook();

        $offset = 0;

        do {
            $updates = $telegram->getUpdates($offset, $this->option('once') ? 1 : 25);

            foreach ($updates as $update) {
                $updateId = (int) ($update['update_id'] ?? 0);
                if ($updateId >= $offset) {
                    $offset = $updateId + 1;
                }

                $from = $update['message']['from']['id'] ?? null;
                $text = $update['message']['text'] ?? null;
                if ($from && $text) {
                    $this->line("[TG {$from}] {$text}");
                }

                $handler->handleUpdate($update);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
