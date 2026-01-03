<?php

namespace ParabellumKoval\AiContentGenerator\Services\Drivers;

use Illuminate\Http\Client\RequestException;
use ParabellumKoval\AiContentGenerator\DTO\DriverResponse;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;
use ParabellumKoval\AiContentGenerator\Exceptions\InvalidKeyException;
use ParabellumKoval\AiContentGenerator\Exceptions\RateLimitException;
use ParabellumKoval\AiContentGenerator\Exceptions\TimeoutException;

class GeminiDriver extends BaseDriver
{
    public function generate(GenerationRequest $request): DriverResponse
    {
        $this->ensureApiKey();

        $model = $request->model ?? $this->config['default_model'] ?? 'gemini-pro';

        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->decoratePrompt($request)],
                    ],
                ],
            ],
            'generationConfig' => array_filter([
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxTokens,
            ], fn ($value) => $value !== null),
        ];

        if ($request->systemInstruction) {
            $body['system_instruction'] = [
                'parts' => [['text' => $request->systemInstruction]],
            ];
        }

        if (!empty($request->payload)) {
            $body = array_replace_recursive($body, $request->payload);
        }

        $endpoint = rtrim($this->config['base_uri'], '/') . "/models/{$model}:generateContent";

        try {
            $response = $this->http()
                ->acceptJson()
                ->post($endpoint, array_merge($body, ['key' => $this->config['api_key']]))
                ->throw();
        } catch (RequestException $e) {
            $this->mapException($e);
        }

        $data = $response->json();
        $candidates = $data['candidates'] ?? [];

        $messages = [];
        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $messages[] = $part['text'];
                }
            }
        }

        return new DriverResponse(
            raw: $data,
            messages: $messages,
            usage: [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? null,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? null,
                'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? null,
            ],
            model: $model,
        );
    }

    public function healthCheck(): array
    {
        return [
            'ok' => !empty($this->config['api_key']),
            'message' => empty($this->config['api_key']) ? 'API key is not configured.' : 'Configured',
        ];
    }

    protected function mapException(RequestException $e): never
    {
        $response = $e->response;

        if (!$response) {
            throw $e;
        }

        $status = $response->status();

        if ($status === 429) {
            $retryAfter = $this->normalizeRetryAfter($response->header('Retry-After'));
            throw new RateLimitException($response->json('error.message', 'Rate limit exceeded'), $retryAfter);
        }

        if ($status === 401 || $status === 403) {
            throw new InvalidKeyException($response->json('error.message', 'Invalid API key'));
        }

        if ($status === 408) {
            throw new TimeoutException('Gemini request timed out.');
        }

        throw $e;
    }

    protected function normalizeRetryAfter(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
