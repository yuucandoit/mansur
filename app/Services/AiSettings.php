<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class AiSettings
{
    public const PROVIDER_OPENROUTER = 'openrouter';

    public static function provider(): string
    {
        return Setting::where('slug', 'ai_provider')->value('value') ?: self::PROVIDER_OPENROUTER;
    }

    public static function model(): string
    {
        return Setting::where('slug', 'ai_model')->value('value')
            ?: config('services.openrouter.model')
            ?: 'google/gemma-4-26b-a4b-it:free';
    }

    public static function name(): string
    {
        $value = Setting::where('slug', 'ai_name')->value('value');

        return filled($value) ? (string) $value : (string) config('ai.name', 'Luna');
    }

    public static function persona(): string
    {
        $value = Setting::where('slug', 'ai_persona')->value('value');

        return filled($value) ? (string) $value : (string) config('ai.persona', '');
    }

    public static function knowledge(): string
    {
        $value = Setting::where('slug', 'ai_knowledge')->value('value');

        return filled($value) ? (string) $value : (string) config('ai.knowledge', '');
    }

    public static function enabled(): bool
    {
        $value = Setting::where('slug', 'ai_enabled')->value('value');

        return $value === null ? true : (bool) $value;
    }

    public static function apiKey(): ?string
    {
        $setting = Setting::where('slug', 'ai_api_key')->first();

        // A row that exists with an empty value means the key was explicitly
        // cleared, so do not fall back to the .env value.
        if ($setting) {
            if (! $setting->value) {
                return null;
            }

            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable) {
                // Support keys saved before encryption was introduced.
                return $setting->value;
            }
        }

        return config('services.openrouter.api_key') ?: env('OPENROUTER_API_KEY');
    }

    public static function saveApiKey(?string $key): void
    {
        Setting::updateOrCreate(
            ['slug' => 'ai_api_key'],
            [
                'name'  => 'AI API Key',
                'type'  => 'encrypted',
                'value' => $key ? Crypt::encryptString($key) : null,
            ],
        );
    }

    public static function save(string $provider, string $model, bool $enabled): void
    {
        Setting::updateOrCreate(
            ['slug' => 'ai_provider'],
            ['name' => 'AI Provider', 'type' => 'string', 'value' => $provider],
        );

        Setting::updateOrCreate(
            ['slug' => 'ai_model'],
            ['name' => 'AI Model', 'type' => 'string', 'value' => $model],
        );

        Setting::updateOrCreate(
            ['slug' => 'ai_enabled'],
            ['name' => 'AI Enabled', 'type' => 'boolean', 'value' => $enabled ? '1' : '0'],
        );
    }

    /**
     * Save the administrator-editable identity and app knowledge notes.
     * Empty values fall back to config/ai.php defaults.
     */
    public static function saveAssistant(?string $name, ?string $persona, ?string $knowledge): void
    {
        Setting::updateOrCreate(
            ['slug' => 'ai_name'],
            ['name' => 'AI Assistant Name', 'type' => 'string', 'value' => filled($name) ? trim($name) : null],
        );

        Setting::updateOrCreate(
            ['slug' => 'ai_persona'],
            ['name' => 'AI Assistant Persona', 'type' => 'text', 'value' => filled($persona) ? trim($persona) : null],
        );

        Setting::updateOrCreate(
            ['slug' => 'ai_knowledge'],
            ['name' => 'AI Knowledge Notes', 'type' => 'text', 'value' => filled($knowledge) ? trim($knowledge) : null],
        );
    }

    public static function isConfigured(): bool
    {
        return self::enabled()
            && self::provider() === self::PROVIDER_OPENROUTER
            && filled(self::apiKey())
            && filled(self::model());
    }

    public static function maskedKey(): ?string
    {
        $key = self::apiKey();

        if (! $key) {
            return null;
        }

        if (strlen($key) <= 10) {
            return str_repeat('•', strlen($key));
        }

        return substr($key, 0, 6).str_repeat('•', 10).substr($key, -4);
    }
}
