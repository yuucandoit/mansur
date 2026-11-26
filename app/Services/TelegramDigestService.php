<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TelegramDigestService
{
    public function __construct(
        private TelegramService $telegram,
    ) {
    }

    public function timezone(): string
    {
        return (string) config('services.telegram.digest_timezone', 'Asia/Jakarta');
    }

    /**
     * Send scheduled daily digests for all users whose morning/evening slot matches now.
     *
     * @return array{checked: int, sent: int, failed: int}
     */
    public function sendDueDigests(?string $forcePeriod = null, ?int $onlyUserId = null, bool $force = false): array
    {
        if (! $this->telegram->isConfigured()) {
            return ['checked' => 0, 'sent' => 0, 'failed' => 0];
        }

        $now = Carbon::now($this->timezone());
        $today = $now->toDateString();

        $query = User::query()
            ->where('is_active', true)
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '');

        if ($onlyUserId !== null) {
            $query->where('id', $onlyUserId);
        } elseif (! $force) {
            $query->where('telegram_digest_enabled', true);
        }

        $users = $query->get();
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $period = $this->resolveDuePeriod($user, $now, $forcePeriod, $force);
            if ($period === null) {
                continue;
            }

            $slotKey = "{$today}_{$period}";
            if (! $force && $user->telegram_digest_last_sent_slot === $slotKey) {
                continue;
            }

            $res = $this->sendToUser($user, $period);
            if ($res['ok'] ?? false) {
                $user->update(['telegram_digest_last_sent_slot' => $slotKey]);
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'checked' => $users->count(),
            'sent'    => $sent,
            'failed'  => $failed,
        ];
    }

    /**
     * Send the digest immediately to a specific user.
     *
     * @return array{ok: bool, description?: string}
     */
    public function sendToUser(User $user, ?string $period = null): array
    {
        if (! filled($user->telegram_user_id)) {
            return ['ok' => false, 'description' => 'User does not have a linked Telegram User ID.'];
        }

        $now = Carbon::now($this->timezone());
        $period = $period ?: ($now->hour < 12 ? 'morning' : 'evening');
        $message = $this->buildDigestForUser($user, $period);

        return $this->telegram->sendMessage((string) $user->telegram_user_id, $message);
    }

    public function buildDigestForUser(User $user, string $period = 'morning'): string
    {
        $tz = $this->timezone();
        $now = Carbon::now($tz);
        $today = $now->copy()->startOfDay();
        $soonEnd = $now->copy()->addDays(3)->endOfDay();

        $greeting = $period === 'morning'
            ? "☀️ Selamat Pagi, {$user->name}!"
            : "🌆 Selamat Sore, {$user->name}!";

        $subtitle = $period === 'morning'
            ? 'Berikut ringkasan tugas kamu untuk hari ini'
            : 'Berikut progres & sisa tugas kamu sore ini';

        $visibleProjectIds = Project::visibleTo($user)->pluck('id')->all();

        $tasks = empty($visibleProjectIds)
            ? collect()
            : Task::query()
                ->whereIn('project_id', $visibleProjectIds)
                ->where('is_archive', 0)
                ->where(function ($q) {
                    $q->where('is_done', 0)->orWhereNull('is_done');
                })
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('assignees', fn ($a) => $a->where('user_id', $user->id));
                })
                ->with(['project:id,title', 'list:id,title'])
                ->get();

        $dateLabel = $now->locale('id')->translatedFormat('l, d M Y · H:i');

        if ($tasks->isEmpty()) {
            return "{$greeting}\n{$subtitle} ({$dateLabel} WIB):\n\n"
                ."🎉 Tidak ada tugas aktif yang tertunda saat ini. Mantap!";
        }

        $overdue = $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->copy()->timezone($tz)->lt($today));
        $dueToday = $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->copy()->timezone($tz)->isSameDay($today));
        $upcoming = $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->copy()->timezone($tz)->gt($today->copy()->endOfDay()) && $t->due_date->copy()->timezone($tz)->lte($soonEnd));

        $categorizedIds = $overdue->pluck('id')
            ->merge($dueToday->pluck('id'))
            ->merge($upcoming->pluck('id'))
            ->all();

        $others = $this->sortByPriority($tasks->whereNotIn('id', $categorizedIds));

        $sections = [];

        if ($overdue->isNotEmpty()) {
            $sections[] = $this->formatSection('🔴 Terlambat (Overdue)', $this->sortByPriority($overdue), $tz);
        }
        if ($dueToday->isNotEmpty()) {
            $sections[] = $this->formatSection('📅 Jatuh Tempo Hari Ini', $this->sortByPriority($dueToday), $tz);
        }
        if ($upcoming->isNotEmpty()) {
            $sections[] = $this->formatSection('⏳ Mendatang (3 Hari Ke Depan)', $this->sortByPriority($upcoming), $tz);
        }
        if ($others->isNotEmpty()) {
            $sections[] = $this->formatSection('📋 Tugas Aktif Lainnya', $others, $tz, 6);
        }

        $total = $tasks->count();

        return "{$greeting}\n{$subtitle} ({$dateLabel} WIB):\n"
            ."Total tugas aktif: {$total}\n\n"
            .implode("\n\n", $sections)
            ."\n\nKetik instruksi langsung di sini (mis. tandai selesai / buat task) atau ketik /digest kapan saja.";
    }

    private function resolveDuePeriod(User $user, Carbon $now, ?string $forcePeriod, bool $force): ?string
    {
        if ($force && $forcePeriod) {
            return $forcePeriod;
        }

        $morning = $user->telegram_digest_morning ?: '08:00';
        $evening = $user->telegram_digest_evening ?: '17:00';

        if ($forcePeriod === 'morning' || (! $forcePeriod && $this->matchesTimeWindow($now, $morning))) {
            return 'morning';
        }

        if ($forcePeriod === 'evening' || (! $forcePeriod && $this->matchesTimeWindow($now, $evening))) {
            return 'evening';
        }

        return null;
    }

    private function matchesTimeWindow(Carbon $now, string $targetHm): bool
    {
        if (! preg_match('/^(\d{2}):(\d{2})$/', $targetHm, $m)) {
            return false;
        }

        $target = $now->copy()->setTime((int) $m[1], (int) $m[2], 0);
        $diffMinutes = $target->diffInMinutes($now, false);

        return $diffMinutes >= 0 && $diffMinutes < 5;
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, Task>
     */
    private function sortByPriority(Collection $tasks): Collection
    {
        $weights = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        return $tasks->sortBy(function (Task $t) use ($weights) {
            $p = $t->is_critical ? 'critical' : ($t->priority ?: 'medium');
            $w = $weights[$p] ?? 2;
            $d = $t->due_date?->timestamp ?? PHP_INT_MAX;

            return sprintf('%d-%012d-%010d', $w, $d, $t->id);
        })->values();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function formatSection(string $heading, Collection $tasks, string $tz, int $limit = 8): string
    {
        $lines = [$heading.' ('.$tasks->count().')'];

        foreach ($tasks->take($limit) as $task) {
            $project = $task->project?->title ? '['.$task->project->title.'] ' : '';
            $meta = [];

            $priority = $task->is_critical ? 'critical' : ($task->priority ?? null);
            if ($priority && $priority !== 'medium') {
                $meta[] = ucfirst($priority);
            }
            if ($task->list?->title) {
                $meta[] = $task->list->title;
            }
            if ($task->due_date) {
                $meta[] = 'Due '.$task->due_date->copy()->timezone($tz)->format('d M');
            }

            $suffix = $meta ? ' ('.implode(' · ', $meta).')' : '';
            $lines[] = "• {$project}{$task->title}{$suffix}";
        }

        $remaining = $tasks->count() - $limit;
        if ($remaining > 0) {
            $lines[] = "  ...dan {$remaining} tugas lainnya.";
        }

        return implode("\n", $lines);
    }
}
