<?php

namespace App\Http\Controllers;

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
}
