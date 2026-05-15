<?php

namespace ParabellumKoval\AiContentGenerator;

use Backpack\Settings\Events\SettingsGroupChanged;
use Illuminate\Support\Facades\Event;
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
        $this->registerSettingsChangeListener();

        $this->publishes([
            __DIR__ . '/../config/ai-content-generator.php' => config_path('ai-content-generator.php'),
        ], 'config');
    }

    protected function registerSettingsChangeListener(): void
    {
        if (!class_exists(SettingsGroupChanged::class)) {
            return;
        }

        Event::listen(SettingsGroupChanged::class, function (SettingsGroupChanged $event): void {
            if ($event->group !== 'ai-content') {
                return;
            }

            $drivers = $this->changedProviderDrivers(array_keys($event->diff));
            if ($drivers === []) {
                return;
            }

            $statuses = $this->app->make(ProviderStatusRepository::class);
            foreach ($drivers as $driver) {
                $statuses->clear($driver);
            }

            $this->app->forgetInstance(DriverRegistry::class);
            $this->app->forgetInstance(ContentGenerator::class);
        });
    }

    protected function changedProviderDrivers(array $changedKeys): array
    {
        $drivers = [];

        foreach ($changedKeys as $key) {
            if (!is_string($key)) {
                continue;
            }

            if ($key === 'ai_content_generator.default_driver') {
                foreach (array_keys((array) config('ai-content-generator.drivers', [])) as $driver) {
                    $drivers[] = $driver;
                }
                continue;
            }

            if (preg_match('/^ai_content_generator\.providers\.([^\.]+)\.(api_key|enabled|model)$/', $key, $matches) === 1) {
                $drivers[] = $matches[1];
            }
        }

        return array_values(array_unique($drivers));
    }
}
