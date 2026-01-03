<?php

namespace ParabellumKoval\AiContentGenerator\Contracts;

use ParabellumKoval\AiContentGenerator\DTO\DriverResponse;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;

interface ContentDriver
{
    public function generate(GenerationRequest $request): DriverResponse;

    public function healthCheck(): array;
}
