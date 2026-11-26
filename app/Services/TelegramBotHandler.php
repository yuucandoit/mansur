<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramBotHandler
{
    public function __construct(
        private TelegramService $telegram,
        private AiAgentService $aiAgent,
        private TelegramDigestService $digest,
    ) {
    }

    /**
     * Process a single Telegram update payload (shared by webhook & polling).
     *
     * @param  array<string, mixed>  $update
     */
    public function handleUpdate(array $update): void
    {
        $updateId = (int) ($update['update_id'] ?? 0);
        if ($updateId > 0 && ! Cache::add("tg_update_{$updateId}", true, 300)) {
            return;
        }

        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === '' || $fromId === '' || $text === '') {
            return;
        }

        // Only respond in private chats for personal task operations, or handle /id anywhere.
        $command = strtolower(strtok($text, " \n\t") ?: '');
        $command = explode('@', $command)[0];

        $user = User::where('telegram_user_id', $fromId)
            ->where('is_active', true)
            ->first();

        if ($command === '/start' || $command === '/id') {
            $this->replyIdentity($chatId, $fromId, $user);
            return;
        }

        if ($command === '/help') {
            $this->replyHelp($chatId, $user);
            return;
        }

        if ($command === '/digest') {
            if (! $user) {
                $this->replyNotLinked($chatId, $fromId);
                return;
            }

            $this->telegram->sendChatAction($chatId, 'typing');
            $this->digest->sendToUser($user);
            return;
        }

        if (! $user) {
            $this->replyNotLinked($chatId, $fromId);
            return;
        }

        $this->telegram->sendChatAction($chatId, 'typing');

        try {
            $result = $this->aiAgent->chat($user, $text);
            $reply = trim((string) ($result['reply'] ?? 'Selesai.'));
            $actions = is_array($result['results'] ?? null) ? $result['results'] : [];

            if (! empty($actions)) {
                $lines = [];
                foreach ($actions as $act) {
                    $statusIcon = ($act['status'] ?? '') === 'ok' ? '✅' : '⚠️';
                    $msg = trim((string) ($act['message'] ?? $act['type'] ?? ''));
                    if ($msg !== '') {
                        $lines[] = "{$statusIcon} {$msg}";
                    }
                }
                if (! empty($lines)) {
                    $reply .= "\n\n" . implode("\n", $lines);
                }
            }

            $this->telegram->sendMessage($chatId, $reply);
        } catch (\Throwable $e) {
            Log::error('Telegram bot AI execution failed: '.$e->getMessage());
            $this->telegram->sendMessage(
                $chatId,
                'Maaf, terjadi kendala saat memproses permintaan kamu. Silakan coba lagi sebentar.'
            );
        }
    }

    private function replyIdentity(string $chatId, string $fromId, ?User $user): void
    {
        if ($user) {
            $this->telegram->sendMessage(
                $chatId,
                "Halo {$user->name}! Akun Telegram kamu sudah terhubung dengan ProTask.\n"
                ."Telegram User ID: {$fromId}\n\n"
                ."Kamu bisa langsung kirim perintah seperti:\n"
                ."• Buatkan task Review UI di project Website besok\n"
                ."• Tambah checklist di task Implementasi QA: 1. Test login, 2. Test board\n"
                ."• Apa saja task saya yang belum selesai?"
            );
            return;
        }

        $this->replyNotLinked($chatId, $fromId);
    }

    private function replyNotLinked(string $chatId, string $fromId): void
    {
        $appUrl = rtrim((string) config('app.url', 'https://protask.rexensoft.com'), '/');
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $appUrl = 'https://protask.rexensoft.com';
        }

        $this->telegram->sendMessage(
            $chatId,
            "Kamu belum setup Telegram User ID kamu di ProTask.\n\n"
            ."Telegram User ID kamu: {$fromId}\n\n"
            ."Cara setup:\n"
            ."1. Cari tahu User ID kamu di @userinfobot (atau salin ID di atas: {$fromId})\n"
            ."2. Login ke aplikasi {$appUrl}\n"
            ."3. Buka Settings → Telegram Integration → Telegram User ID, masukkan {$fromId}, lalu klik Save ID."
        );
    }

    private function replyHelp(string $chatId, ?User $user): void
    {
        $status = $user
            ? "Terhubung sebagai: {$user->name} ({$user->email})"
            : 'Status: Belum terhubung. Ketik /id untuk melihat User ID kamu.';

        $this->telegram->sendMessage(
            $chatId,
            "Panduan Bot ProTask\n{$status}\n\n"
            ."Perintah:\n"
            ."/id — Lihat Telegram User ID kamu\n"
            ."/digest — Kirim ringkasan tugas aktif saat ini\n"
            ."/help — Tampilkan bantuan ini\n\n"
            ."Atau langsung ketik instruksi biasa untuk mengelola project, task, checklist, komentar, dan assignment."
        );
    }
}
