<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotHandler $handler): JsonResponse
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        if ($expectedSecret !== '') {
            $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($expectedSecret, $provided)) {
                return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
            }
        }

        $handler->handleUpdate($request->all());

        return response()->json(['ok' => true]);
    }
}
