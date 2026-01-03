<?php

namespace ParabellumKoval\AiContentGenerator\Services\Drivers;

use Illuminate\Http\Client\RequestException;
use ParabellumKoval\AiContentGenerator\DTO\DriverResponse;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;
use ParabellumKoval\AiContentGenerator\Exceptions\InvalidKeyException;
use ParabellumKoval\AiContentGenerator\Exceptions\RateLimitException;
use ParabellumKoval\AiContentGenerator\Exceptions\TimeoutException;

class GrokDriver extends BaseDriver
{
    public function generate(GenerationRequest $request): DriverResponse
    {
        $this->ensureApiKey();

        $body = [
            'model' => $request->model ?? $this->config['default_model'] ?? null,
            'messages' => $this->buildMessages($request),
            'temperature' => $request->temperature ?? 1.0,
        ];

        if ($request->maxTokens) {
            $body['max_tokens'] = $request->maxTokens;
        }

        if ($request->outputType === 'collection' && $request->quantity > 1) {
            $body['n'] = $request->quantity;
        }

        if (!empty($request->payload)) {
            $body = array_replace_recursive($body, $request->payload);
        }

        $endpoint = rtrim($this->config['base_uri'], '/') . '/chat/completions';

        try {
            $response = $this->http()
                ->withToken($this->config['api_key'])
                ->acceptJson()
                ->post($endpoint, $body)
                ->throw();
        } catch (RequestException $e) {
            $this->mapException($e);
        }

        $data = $response->json();
        $choices = $data['choices'] ?? [];

        $messages = [];
        foreach ($choices as $choice) {
            $messages[] = $choice['message']['content'] ?? '';
        }

        return new DriverResponse(
            raw: $data,
            messages: $messages,
            usage: [
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? null,
                'total_tokens' => $data['usage']['total_tokens'] ?? null,
            ],
            model: $data['model'] ?? $body['model'],
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
            throw new TimeoutException('Grok request timed out.');
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
