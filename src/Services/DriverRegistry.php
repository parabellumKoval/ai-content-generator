<?php

namespace ParabellumKoval\AiContentGenerator\Services;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use ParabellumKoval\AiContentGenerator\Contracts\ContentDriver;

class DriverRegistry
{
    public function __construct(protected Container $app)
    {
    }

    public function get(string $driver): ContentDriver
    {
        $config = $this->getConfig($driver);

        $handler = $config['handler'] ?? null;
        if (!$handler || !class_exists($handler)) {
            throw new InvalidArgumentException("Handler for driver {$driver} is not configured.");
        }

        return $this->app->make($handler, ['config' => $config]);
    }

    public function all(): array
    {
        return config('ai-content-generator.drivers', []);
    }

    public function configFor(string $driver): array
    {
        return $this->getConfig($driver);
    }

    protected function getConfig(string $driver): array
    {
        $config = config("ai-content-generator.drivers.{$driver}");

        if (!$config) {
            throw new InvalidArgumentException("Driver {$driver} is not configured.");
        }

        $prefix = "ai_content_generator.providers.{$driver}";
        $apiKey = \Settings::get("{$prefix}.api_key", $config['api_key'] ?? null);
        $model = \Settings::get("{$prefix}.model", $config['default_model'] ?? null);
        $enabled = \Settings::get("{$prefix}.enabled", true);

        $config['api_key'] = $apiKey;
        $config['default_model'] = $model;
        $config['enabled'] = $enabled;

        return $config;
    }
}
