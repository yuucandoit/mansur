<?php

namespace App\Http\Controllers;

use App\Services\TelegramDigestService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    /**
     * Persist a user interface preference for the signed-in user.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'   => 'required|string|in:task_notifications,compact_cards',
            'value' => 'required|boolean',
        ]);

        $request->user()->update([
            $data['key'] => (bool) $data['value'],
        ]);

        return response()->json([
            'message' => 'Preference saved.',
            'key'     => $data['key'],
            'value'   => (bool) $data['value'],
        ]);
    }

    /**
     * Save or clear the signed-in user's Telegram User ID.
     */
    public function updateTelegram(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'telegram_user_id' => [
                'nullable',
                'string',
                'regex:/^[0-9]{5,20}$/',
                Rule::unique('users', 'telegram_user_id')->ignore($user->id),
            ],
        ], [
            'telegram_user_id.regex'  => 'Telegram User ID must be 5 to 20 digits.',
            'telegram_user_id.unique' => 'This Telegram User ID is already linked to another account.',
        ]);

        $telegramId = filled($data['telegram_user_id'] ?? null)
            ? trim((string) $data['telegram_user_id'])
            : null;

        $user->update([
            'telegram_user_id' => $telegramId,
        ]);

        return response()->json([
            'message'          => $telegramId ? 'Telegram User ID saved.' : 'Telegram User ID unlinked.',
            'telegram_user_id' => $telegramId,
        ]);
    }

    /**
     * Send a verification message to the user's linked Telegram ID.
     */
    public function testTelegram(Request $request, TelegramService $telegram): JsonResponse
    {
        $user = $request->user();

        if (! filled($user->telegram_user_id)) {
            return response()->json([
                'message' => 'Please save your Telegram User ID first.',
            ], 422);
        }

        if (! $telegram->isConfigured()) {
            return response()->json([
                'message' => 'Telegram bot token is not configured on the server.',
            ], 503);
        }

        $res = $telegram->sendMessage(
            (string) $user->telegram_user_id,
            "Halo {$user->name}! Koneksi Telegram dengan ProTask berhasil."
        );

        if (! ($res['ok'] ?? false)) {
            return response()->json([
                'message' => 'Telegram error: '.($res['description'] ?? 'Make sure you have pressed Start on the bot first.'),
            ], 422);
        }

        return response()->json([
            'message' => 'Test message sent to your Telegram!',
        ]);
    }

    /**
     * Save the user's Telegram daily digest schedule (morning & evening).
     */
    public function updateTelegramDigest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'morning' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'evening' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ], [
            'morning.regex' => 'Morning time must be in HH:MM format.',
            'evening.regex' => 'Evening time must be in HH:MM format.',
        ]);

        $user = $request->user();

        if ($data['enabled'] && ! filled($user->telegram_user_id)) {
            return response()->json([
                'message' => 'Please save your Telegram User ID first before enabling Daily Digest.',
            ], 422);
        }

        $user->update([
            'telegram_digest_enabled' => (bool) $data['enabled'],
            'telegram_digest_morning' => $data['morning'],
            'telegram_digest_evening' => $data['evening'],
        ]);

        return response()->json([
            'message' => $data['enabled']
                ? "Daily digest enabled ({$data['morning']} & {$data['evening']} WIB)."
                : 'Daily digest paused.',
        ]);
    }

    /**
     * Send the user's current task digest to Telegram immediately.
     */
    public function sendTelegramDigestNow(Request $request, TelegramDigestService $digest): JsonResponse
    {
        $user = $request->user();

        if (! filled($user->telegram_user_id)) {
            return response()->json([
                'message' => 'Please save your Telegram User ID first.',
            ], 422);
        }

        $res = $digest->sendToUser($user);

        if (! ($res['ok'] ?? false)) {
            return response()->json([
                'message' => 'Telegram error: '.($res['description'] ?? 'Failed to send digest.'),
            ], 422);
        }

        return response()->json([
            'message' => 'Daily task digest sent to your Telegram!',
        ]);
    }
}
