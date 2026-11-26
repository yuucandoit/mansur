<?php

namespace App\Console\Commands;

use App\Services\TelegramDigestService;
use Illuminate\Console\Command;

class TelegramSendDigestCommand extends Command
{
    protected $signature = 'telegram:send-digest
        {--slot= : Force a specific slot (morning or evening)}
        {--user= : Target a specific user ID}
        {--force : Ignore schedule window and duplicate-slot guard}';

    protected $description = 'Send scheduled morning/evening Telegram task digests to subscribed users';

    public function handle(TelegramDigestService $digest): int
    {
        $slot = $this->option('slot');
        if ($slot !== null && ! in_array($slot, ['morning', 'evening'], true)) {
            $this->error('Invalid --slot value. Use "morning" or "evening".');
            return self::FAILURE;
        }

        $userId = $this->option('user') !== null ? (int) $this->option('user') : null;
        $force = (bool) $this->option('force');

        $stats = $digest->sendDueDigests($slot, $userId, $force);

        $this->info("Checked {$stats['checked']} user(s): {$stats['sent']} sent, {$stats['failed']} failed.");
        return self::SUCCESS;
    }
}
