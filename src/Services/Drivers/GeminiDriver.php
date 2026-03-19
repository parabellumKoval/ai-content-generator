<?php

namespace ParabellumKoval\AiContentGenerator\Services\Drivers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
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

        $requestedModel = trim((string) ($request->model ?? $this->config['default_model'] ?? 'gemini-2.5-flash'));
        $model = $this->resolveModelAlias($requestedModel);

        $payload = is_array($request->payload) ? $request->payload : [];
        $parts = $this->buildUserParts($request, $payload);

        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => array_filter([
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxTokens,
            ], fn ($value) => $value !== null),
        ];

        $responseModalities = Arr::pull($payload, 'response_modalities');
        $imageOutputMimeType = Arr::pull($payload, 'image_output_mime_type');

        if ($request->responseFormat === 'image' && empty($responseModalities)) {
            $responseModalities = ['IMAGE'];
        }

        if (is_array($responseModalities) && $responseModalities !== []) {
            $body['generationConfig']['responseModalities'] = array_values($responseModalities);
        }

        if (is_string($imageOutputMimeType) && trim($imageOutputMimeType) !== '') {
            $body['generationConfig']['responseMimeType'] = trim($imageOutputMimeType);
        }

        if (
            $request->outputType === 'collection'
            && $request->quantity > 1
            && !array_key_exists('candidateCount', $body['generationConfig'])
        ) {
            $body['generationConfig']['candidateCount'] = $request->quantity;
        }

        if ($request->systemInstruction) {
            $body['system_instruction'] = [
                'parts' => [['text' => $request->systemInstruction]],
            ];
        }

        if (!empty($payload)) {
            $body = array_replace_recursive($body, $payload);
        }

        $endpoint = rtrim($this->config['base_uri'], '/') . "/models/{$model}:generateContent";

        try {
            $response = $this->http()
                ->acceptJson()
                ->withQueryParameters(['key' => $this->config['api_key']])
                ->post($endpoint, $body)
                ->throw();
        } catch (RequestException $e) {
            $this->mapException($e);
        }

        $data = $response->json();
        $candidates = $data['candidates'] ?? [];

        $messages = [];
        $images = [];
        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $messages[] = $part['text'];
                }

                $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
                if (!is_array($inline)) {
                    continue;
                }

                $base64 = trim((string) ($inline['data'] ?? ''));
                if ($base64 === '') {
                    continue;
                }

                $mimeType = trim((string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png'));
                $images[] = [
                    'mime_type' => $mimeType,
                    'base64' => $base64,
                    'data_uri' => sprintf('data:%s;base64,%s', $mimeType, $base64),
                ];
            }
        }

        return new DriverResponse(
            raw: $data,
            messages: $messages,
            artifacts: [
                'images' => $images,
            ],
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
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function buildUserParts(GenerationRequest $request, array &$payload): array
    {
        $parts = [
            ['text' => $this->decoratePrompt($request)],
        ];

        $inputImages = Arr::pull($payload, 'input_images', []);
        foreach ($this->normalizeInputImages($inputImages) as $imagePart) {
            $parts[] = $imagePart;
        }

        return $parts;
    }

    protected function normalizeInputImages(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $parts = [];

        foreach ($value as $item) {
            $normalized = $this->toInlineImagePart($item);
            if ($normalized !== null) {
                $parts[] = $normalized;
            }
        }

        return $parts;
    }

    protected function toInlineImagePart(mixed $input): ?array
    {
        if (is_string($input)) {
            $input = ['url' => $input];
        }

        if (!is_array($input)) {
            return null;
        }

        $mimeType = trim((string) ($input['mime_type'] ?? $input['mimeType'] ?? 'image/jpeg'));

        $dataUri = $input['data_uri'] ?? $input['dataUri'] ?? null;
        if (is_string($dataUri) && trim($dataUri) !== '') {
            $parsed = $this->decodeDataUri($dataUri);
            if ($parsed !== null) {
                return ['inlineData' => $parsed];
            }
        }

        $base64 = $input['base64'] ?? null;
        if (is_string($base64) && trim($base64) !== '') {
            return [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => preg_replace('/\s+/', '', trim($base64)),
                ],
            ];
        }

        $url = $input['url'] ?? null;
        if (is_string($url) && trim($url) !== '') {
            $downloaded = $this->downloadImageAsInlineData($url);
            if ($downloaded !== null) {
                return ['inlineData' => $downloaded];
            }
        }

        return null;
    }

    protected function decodeDataUri(string $dataUri): ?array
    {
        if (!preg_match('#^data:(?P<mime>[^;]+);base64,(?P<data>.+)$#si', trim($dataUri), $matches)) {
            return null;
        }

        $mimeType = trim((string) ($matches['mime'] ?? 'image/jpeg'));
        $data = preg_replace('/\s+/', '', (string) ($matches['data'] ?? ''));

        if ($data === '') {
            return null;
        }

        return [
            'mimeType' => $mimeType,
            'data' => $data,
        ];
    }

    protected function downloadImageAsInlineData(string $url): ?array
    {
        try {
            $response = $this->http()->get($url)->throw();
        } catch (\Throwable) {
            return null;
        }

        $binary = $response->body();
        if (!is_string($binary) || $binary === '') {
            return null;
        }

        $contentType = trim((string) ($response->header('Content-Type') ?? 'image/jpeg'));
        $mimeType = explode(';', $contentType)[0] ?: 'image/jpeg';

        return [
            'mimeType' => $mimeType,
            'data' => base64_encode($binary),
        ];
    }

    protected function resolveModelAlias(string $model): string
    {
        if ($model === '') {
            return 'gemini-2.5-flash';
        }

        $aliases = (array) ($this->config['model_aliases'] ?? []);
        $resolved = (string) ($aliases[$model] ?? $model);

        return trim($resolved) !== '' ? trim($resolved) : $model;
    }
}
