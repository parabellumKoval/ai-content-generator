<?php

namespace ParabellumKoval\AiContentGenerator\Services\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use ParabellumKoval\AiContentGenerator\Contracts\ContentDriver;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;
use ParabellumKoval\AiContentGenerator\Exceptions\InvalidKeyException;

abstract class BaseDriver implements ContentDriver
{
    public function __construct(protected array $config = [])
    {
    }

    protected function http(): PendingRequest
    {
        $timeout = $this->config['timeout'] ?? 30;

        return Http::timeout($timeout);
    }

    protected function ensureApiKey(): void
    {
        if (empty($this->config['api_key'])) {
            throw new InvalidKeyException('API key is missing for this provider.');
        }
    }

    protected function buildMessages(GenerationRequest $request): array
    {
        $messages = [];

        if ($request->systemInstruction) {
            $messages[] = [
                'role' => 'system',
                'content' => $request->systemInstruction,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $this->decoratePrompt($request),
        ];

        return $messages;
    }

    protected function decoratePrompt(GenerationRequest $request): string
    {
        $prompt = $request->prompt;

        if ($request->outputType === 'collection' && $request->quantity > 1) {
            $prompt .= "\n\nReturn exactly {$request->quantity} items.";
        }

        if ($request->responseFormat !== 'text') {
            $prompt .= "\n\nRespond with {$request->responseFormat}.";
        }

        return $prompt;
    }
}
