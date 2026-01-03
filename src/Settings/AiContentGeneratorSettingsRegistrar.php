<?php

namespace ParabellumKoval\AiContentGenerator\Settings;

use Backpack\Settings\Contracts\SettingsRegistrarInterface;
use Backpack\Settings\Services\Registry\Field;
use Backpack\Settings\Services\Registry\Registry;
use Illuminate\Support\Facades\Route;
use ParabellumKoval\AiContentGenerator\Services\ProviderStatusRepository;

class AiContentGeneratorSettingsRegistrar implements SettingsRegistrarInterface
{
    public function __construct(protected ProviderStatusRepository $statuses)
    {
    }

    public function register(Registry $registry): void
    {
        $drivers = config('ai-content-generator.drivers', []);

        $registry->group('ai-content', function ($group) use ($drivers) {
            $group->title('AI генератор')->icon('la la-robot')
                ->page('Провайдеры', function ($page) use ($drivers) {
                    $page->add(
                        Field::make('ai_content_generator.default_driver', 'select_from_array')
                            ->label('Драйвер по умолчанию')
                            ->options(collect($drivers)->mapWithKeys(fn ($driver, $key) => [$key => $driver['name'] ?? $key])->toArray())
                            ->default(config('ai-content-generator.default_driver', 'openai'))
                            ->cast('string')
                    );

                    foreach ($drivers as $key => $driver) {
                        $name = $driver['name'] ?? $key;
                        $page->add(
                            Field::make("ai_content_generator.providers.{$key}.enabled", 'checkbox')
                                ->label("Включен: {$name}")
                                ->default(true)
                                ->cast('boolean')
                        );

                        $page->add(
                            Field::make("ai_content_generator.providers.{$key}.api_key", 'text')
                                ->label("API ключ ({$name})")
                                ->hint('Хранится в базе, перекрывает значение из env.')
                                ->cast('string')
                        );

                        $models = $driver['models'] ?? [];
                        if (!empty($models)) {
                            $page->add(
                                Field::make("ai_content_generator.providers.{$key}.model", 'select_from_array')
                                    ->label("Модель ({$name})")
                                    ->options($models)
                                    ->allows_null(false)
                                    ->default($driver['default_model'] ?? null)
                                    ->cast('string')
                            );
                        } else {
                            $page->add(
                                Field::make("ai_content_generator.providers.{$key}.model", 'text')
                                    ->label("Модель ({$name})")
                                    ->default($driver['default_model'] ?? null)
                                    ->cast('string')
                            );
                        }
                    }

                    if (!app()->runningInConsole()) {
                        $page->add(
                            Field::make('ai_content_generator.providers_statuses', 'custom_html')
                                ->label('Статусы')
                                ->value($this->renderStatusesSafely($drivers))
                        );
                    }
                });
        });
    }

    protected function renderStatuses(array $drivers): string
    {
        $statuses = [];
        foreach ($drivers as $key => $config) {
            $status = $this->statuses->get($key);
            $statuses[] = [
                'name' => $config['name'] ?? $key,
                'key' => $key,
                'status' => $status->status,
                'message' => $status->message,
                'blocked_until' => $status->blocked_until,
            ];
        }

        $providersRoute = Route::has('ai-content-generator.providers.index')
            ? route('ai-content-generator.providers.index')
            : null;

        $link = $providersRoute
            ? '<a href="' . e($providersRoute) . '">AI провайдеры</a>'
            : 'AI провайдеры (маршрут недоступен)';

        $html = '<div class="alert alert-info mb-3">Статус провайдеров. Сбросить можно в разделе ' . $link . '.</div>';
        $html .= '<ul class="list-unstyled mb-0">';

        foreach ($statuses as $item) {
            $html .= sprintf(
                '<li><strong>%s</strong> (%s): <span class="badge badge-%s">%s</span> %s</li>',
                e($item['name']),
                e($item['key']),
                $item['status'] === 'available' ? 'success' : 'warning',
                e($item['status']),
                $item['message'] ? '— ' . e($item['message']) : ''
            );
        }

        $html .= '</ul>';

        return $html;
    }

    protected function renderStatusesSafely(array $drivers): string
    {
        try {
            return $this->renderStatuses($drivers);
        } catch (\Throwable $e) {
            return '<div class="alert alert-secondary mb-3">Статусы провайдеров недоступны (нет соединения с базой).</div>';
        }
    }
}
