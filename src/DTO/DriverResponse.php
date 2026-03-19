<?php

namespace ParabellumKoval\AiContentGenerator\DTO;

class DriverResponse
{
    public function __construct(
        public mixed $raw,
        public array $messages,
        public array $artifacts = [],
        public array $usage = [],
        public ?string $model = null,
    ) {
    }

    public function firstMessage(): ?string
    {
        return $this->messages[0] ?? null;
    }

    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'messages' => $this->messages,
            'artifacts' => $this->artifacts,
            'usage' => $this->usage,
            'model' => $this->model,
        ];
    }
}
