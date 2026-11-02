<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private ?string $lastError = null;

    private ?int $lastStatus = null;

    private ?string $lastModel = null;

    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
        private ?string $fallbackModel = null,
    ) {
        $this->apiKey = $this->apiKey ?: AiSettings::apiKey();
        $this->model = $this->model ?: AiSettings::model();
        $this->fallbackModel = $this->fallbackModel ?: config('services.openrouter.fallback_model');
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->model);
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function lastStatus(): ?int
    {
        return $this->lastStatus;
    }

    public function lastModel(): ?string
    {
        return $this->lastModel;
    }

    /**
     * Send a chat completion request to OpenRouter.
     *
     * Falls back to the configured fallback model when the primary model is
     * rate-limited (429), unavailable (5xx), or missing (404).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?string $model = null, float $temperature = 0.2): ?string
    {
        if (! $this->isConfigured()) {
            $this->lastError = 'OpenRouter is not configured.';
            $this->lastStatus = null;

            return null;
        }

        $models = $this->modelsToTry($model);

        foreach ($models as $index => $candidate) {
            $content = $this->attempt($messages, $candidate, $temperature);
            $this->lastModel = $candidate;

            if (filled($content)) {
                return $content;
            }

            $isLast = $index === array_key_last($models);

            // A 200 response with empty content (for example, the whole
            // budget spent on reasoning) is worth retrying on the fallback.
            $canRetry = $this->isRetryable($this->lastStatus) || $this->lastStatus === 200;

            if ($isLast || ! $canRetry) {
                break;
            }

            Log::info('Retrying OpenRouter request with fallback model.', [
                'failed_model' => $candidate,
                'fallback_model' => $models[$index + 1] ?? null,
                'status' => $this->lastStatus,
            ]);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function modelsToTry(?string $model): array
    {
        return array_values(array_unique(array_filter([
            $model ?: $this->model,
            $this->fallbackModel,
        ])));
    }

    private function isRetryable(?int $status): bool
    {
        return $status === 404 || $status === 429 || ($status !== null && $status >= 500);
    }

    /**
     * Keep the HTTP timeout below PHP's execution limit so a slow provider
     * becomes a normal error response instead of a fatal timeout.
     */
    private function requestTimeout(): int
    {
        $limit = (int) ini_get('max_execution_time');

        if ($limit <= 0) {
            return 75;
        }

        return max(10, min(75, $limit - 5));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function attempt(array $messages, string $model, float $temperature): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'HTTP-Referer'  => config('app.url', 'http://localhost'),
                'X-Title'       => config('app.name', 'ProTask').' AI',
                'Content-Type'  => 'application/json',
            ])->connectTimeout(10)->timeout($this->requestTimeout())->post(
                config('services.openrouter.url', 'https://openrouter.ai/api/v1/chat/completions'),
                [
                    'model'       => $model,
                    'messages'    => $messages,
                    'temperature' => $temperature,
                    'max_tokens'  => 3000,
                ],
            );

            $this->lastStatus = $response->status();

            if (! $response->successful()) {
                $this->lastError = (string) (
                    data_get($response->json(), 'error.message')
                    ?: $response->body()
                );

                Log::warning('OpenRouter request failed.', [
                    'model'  => $model,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $this->lastError = null;
            $content = data_get($response->json(), 'choices.0.message.content');

            if (! filled($content)) {
                $this->lastError = 'The model returned an empty response.';

                Log::warning('OpenRouter returned an empty response.', [
                    'model'  => $model,
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 1000),
                ]);
            }

            return $content;
        } catch (\Throwable $e) {
            $this->lastStatus = null;
            $this->lastError = $e->getMessage();

            Log::error('OpenRouter request error: '.$e->getMessage());

            return null;
        }
    }
}
