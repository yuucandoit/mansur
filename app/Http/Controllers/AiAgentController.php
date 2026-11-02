<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\AiAgentService;
use App\Services\AiSettings;
use App\Support\AiCapabilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $configured = AiSettings::isConfigured();
        $model = AiSettings::model();
        $assistantName = AiSettings::name();
        $capabilitySentence = AiCapabilities::sentence();
        $workspaceCount = Workspace::forUser($user)->count();

        return view('ai.index', compact(
            'configured',
            'model',
            'assistantName',
            'capabilitySentence',
            'workspaceCount',
        ));
    }

    public function chat(Request $request, AiAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        if (! AiSettings::isConfigured()) {
            return response()->json([
                'message' => 'AI is not configured. Add an OpenRouter API key in Admin → AI Agent.',
            ], 503);
        }

        $result = $agent->chat(
            $request->user(),
            $data['message'],
        );

        return response()->json($result);
    }
}
