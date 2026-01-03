<?php

namespace ParabellumKoval\AiContentGenerator\Models;

use Illuminate\Database\Eloquent\Model;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class AiContentGeneration extends Model
{
    use CrudTrait;
    protected $table = 'ai_content_generations';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'parsed_response' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('ai-content-generator.tables.history', 'ai_content_generations'));
    }

    public static function startFromRequest(GenerationRequest $request): self
    {
        return static::create([
            'driver' => $request->driver,
            'model' => $request->model,
            'prompt' => $request->prompt,
            'system_instruction' => $request->systemInstruction,
            'payload' => $request->payload,
            'response_format' => $request->responseFormat,
            'output_type' => $request->outputType,
            'quantity' => $request->quantity,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'status' => 'pending',
        ]);
    }

    public function markSuccess(array $data): void
    {
        $this->update(array_merge($data, ['status' => 'success']));
    }

    public function markFailure(string $status, ?string $code, ?string $message, array $data = []): void
    {
        $payload = array_merge($data, [
            'status' => $status,
            'error_code' => $code,
            'error_message' => $message,
        ]);

        $this->update($payload);
    }
}
