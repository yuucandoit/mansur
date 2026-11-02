<?php

namespace App\Support;

class ModelLabel
{
    /**
     * Human-readable model family used when the assistant describes itself.
     *
     * LLMs cannot reliably identify their own weights, so the label is derived
     * from the model ID we actually send to OpenRouter instead of asking the
     * model to guess.
     */
    public static function for(?string $model): string
    {
        $id = strtolower(trim((string) $model));

        if ($id === '') {
            return 'an AI model';
        }

        return match (true) {
            str_contains($id, 'gemini')    => 'Gemini (Google)',
            str_contains($id, 'gemma')     => 'Gemma (Google)',
            str_contains($id, 'nemotron')  => 'Nemotron (NVIDIA)',
            str_contains($id, 'claude')    => 'Claude (Anthropic)',
            str_contains($id, 'llama')     => 'Llama (Meta)',
            str_contains($id, 'mistral')   => 'Mistral',
            str_contains($id, 'qwen')      => 'Qwen (Alibaba)',
            str_contains($id, 'deepseek')  => 'DeepSeek',
            str_contains($id, 'gpt')       => 'GPT (OpenAI)',
            str_contains($id, 'grok')      => 'Grok (xAI)',
            str_contains($id, 'command-r') => 'Command R (Cohere)',
            str_contains($id, 'auto')      => 'OpenRouter auto-router',
            default                        => 'an AI model',
        };
    }
}
