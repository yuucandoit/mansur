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
                $this->telegram->sendMessage(
                    $chatId,
                    "Akun Telegram kamu (User ID: {$fromId}) belum terhubung ke ProTask. Tambahkan di Settings → Telegram Integration terlebih dahulu."
                );
                return;
            }

            $this->telegram->sendChatAction($chatId, 'typing');
            $this->digest->sendToUser($user);
            return;
        }

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "Akun Telegram kamu (User ID: {$fromId}) belum terhubung ke akun ProTask.\n\n"
                ."Buka ProTask → Settings → Telegram Integration, masukkan User ID {$fromId}, lalu klik Save."
            );
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

        $this->telegram->sendMessage(
            $chatId,
            "Halo! Ini adalah bot ProTask (" . AiSettings::name() . ").\n\n"
            ."Telegram User ID kamu adalah:\n{$fromId}\n\n"
            ."Cara menghubungkan:\n"
            ."1. Buka ProTask di browser\n"
            ."2. Klik menu Settings di sidebar kiri\n"
            ."3. Masukkan {$fromId} pada kolom Telegram User ID lalu klik Save."
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
