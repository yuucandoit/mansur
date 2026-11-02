<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
