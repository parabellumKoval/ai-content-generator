<?php

namespace ParabellumKoval\AiContentGenerator\DTO;

class GenerationRequest
{
    public function __construct(
        public string $prompt,
        public ?string $systemInstruction = null,
        public array $payload = [],
        public ?string $driver = null,
        public ?string $model = null,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public string $responseFormat = 'text',
        public string $outputType = 'single',
        public int $quantity = 1,
        public bool $force = false,
    ) {
        $this->driver ??= \Settings::get('ai_content_generator.default_driver', config('ai-content-generator.default_driver', 'openai'));
        $this->responseFormat = $this->responseFormat ?: config('ai-content-generator.response_format', 'text');
        $this->outputType = $this->outputType ?: config('ai-content-generator.output_type', 'single');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            prompt: $data['prompt'] ?? '',
            systemInstruction: $data['system_instruction'] ?? $data['systemInstruction'] ?? null,
            payload: $data['payload'] ?? [],
            driver: $data['driver'] ?? null,
            model: $data['model'] ?? null,
            temperature: $data['temperature'] ?? null,
            maxTokens: $data['max_tokens'] ?? $data['maxTokens'] ?? null,
            responseFormat: $data['response_format'] ?? $data['responseFormat'] ?? config('ai-content-generator.response_format', 'text'),
            outputType: $data['output_type'] ?? $data['outputType'] ?? config('ai-content-generator.output_type', 'single'),
            quantity: (int)($data['quantity'] ?? 1),
            force: (bool)($data['force'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'prompt' => $this->prompt,
            'system_instruction' => $this->systemInstruction,
            'payload' => $this->payload,
            'driver' => $this->driver,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'response_format' => $this->responseFormat,
            'output_type' => $this->outputType,
            'quantity' => $this->quantity,
            'force' => $this->force,
        ];
    }
}
