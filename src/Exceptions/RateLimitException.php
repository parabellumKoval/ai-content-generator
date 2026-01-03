<?php

namespace ParabellumKoval\AiContentGenerator\Exceptions;

class RateLimitException extends AiContentGeneratorException
{
    public function __construct(string $message = 'Rate limit exceeded', protected ?int $retryAfter = null)
    {
        parent::__construct($message);
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
