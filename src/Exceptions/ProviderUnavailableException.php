<?php

namespace ParabellumKoval\AiContentGenerator\Exceptions;

class ProviderUnavailableException extends AiContentGeneratorException
{
    public function __construct(string $driver, string $reason)
    {
        $message = "Driver '{$driver}' is temporarily unavailable: {$reason}. (src/api/packages/ai-content-generator)";
        parent::__construct($message);
    }
}
