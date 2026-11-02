<?php

namespace App\Http\Controllers;

use App\Services\AiSettings;
use App\Support\ModelLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAiController extends Controller
{
    public function index(): View
    {
        $provider = AiSettings::provider();
        $model = AiSettings::model();
        $assistantName = AiSettings::name();
        $persona = AiSettings::persona();
        $knowledge = AiSettings::knowledge();
        $fallbackModel = config('services.openrouter.fallback_model');
        $primaryLabel = ModelLabel::for($model);
        $fallbackLabel = $fallbackModel ? ModelLabel::for($fallbackModel) : null;
        $enabled = AiSettings::enabled();
        $maskedKey = AiSettings::maskedKey();
        $configured = AiSettings::isConfigured();

        return view('admin.ai.index', compact(
            'provider',
            'model',
            'assistantName',
            'persona',
            'knowledge',
            'fallbackModel',
            'primaryLabel',
            'fallbackLabel',
            'enabled',
            'maskedKey',
            'configured',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider'  => 'required|string|in:openrouter',
            'model'     => 'required|string|max:200',
            'api_key'   => 'nullable|string|max:500',
            'enabled'   => 'nullable|boolean',
            'name'      => 'nullable|string|max:50',
            'persona'   => 'nullable|string|max:2000',
            'knowledge' => 'nullable|string|max:8000',
        ]);

        AiSettings::save(
            $data['provider'],
            $data['model'],
            $request->boolean('enabled'),
        );

        AiSettings::saveAssistant(
            $data['name'] ?? null,
            $data['persona'] ?? null,
            $data['knowledge'] ?? null,
        );

        if ($request->filled('api_key')) {
            AiSettings::saveApiKey($data['api_key']);
        }

        return back()->with('status', 'AI settings saved.');
    }

    public function clearKey(): RedirectResponse
    {
        AiSettings::saveApiKey(null);

        return back()->with('status', 'API key removed.');
    }
}
