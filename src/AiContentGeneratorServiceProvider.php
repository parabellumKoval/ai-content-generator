<?php

namespace ParabellumKoval\AiContentGenerator;

use Illuminate\Support\ServiceProvider;
use ParabellumKoval\AiContentGenerator\Services\ContentGenerator;
use ParabellumKoval\AiContentGenerator\Services\DriverRegistry;
use ParabellumKoval\AiContentGenerator\Services\Parsing\ResponseParser;
use ParabellumKoval\AiContentGenerator\Services\ProviderStatusRepository;

class AiContentGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-content-generator.php', 'ai-content-generator');

        $this->app->singleton(ResponseParser::class, fn () => new ResponseParser());
        $this->app->singleton(ProviderStatusRepository::class, fn () => new ProviderStatusRepository());
        $this->app->singleton(DriverRegistry::class, fn ($app) => new DriverRegistry($app));
        $this->app->singleton(ContentGenerator::class, function ($app) {
            return new ContentGenerator(
                $app->make(DriverRegistry::class),
                $app->make(ProviderStatusRepository::class),
                $app->make(ResponseParser::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-content-generator');

        $this->publishes([
            __DIR__ . '/../config/ai-content-generator.php' => config_path('ai-content-generator.php'),
        ], 'config');
    }
}
