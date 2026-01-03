<?php

namespace ParabellumKoval\AiContentGenerator\DTO;

class ResponseDto
{
    public function __construct(
        public string $driver,
        public string $status,
        public mixed $result,
        public array $meta = [],
        public mixed $raw = null,
        public ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'status' => $this->status,
            'result' => $this->result,
            'meta' => $this->meta,
            'raw' => $this->raw,
            'error' => $this->error,
        ];
    }
}
