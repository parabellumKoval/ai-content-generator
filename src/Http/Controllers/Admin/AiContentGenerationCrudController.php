<?php

namespace ParabellumKoval\AiContentGenerator\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use ParabellumKoval\AiContentGenerator\Models\AiContentGeneration;

class AiContentGenerationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\BulkDeleteOperation;

    public function setup(): void
    {
        CRUD::setModel(AiContentGeneration::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/ai-content-generations');
        CRUD::setEntityNameStrings('AI генерация', 'AI генерации');
        CRUD::orderBy('id', 'desc');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumns([
            ['name' => 'id', 'label' => '#'],
            ['name' => 'driver', 'label' => 'Драйвер'],
            ['name' => 'model', 'label' => 'Модель'],
            ['name' => 'api_key_mask', 'label' => 'API ключ'],
            ['name' => 'status', 'label' => 'Статус'],
            ['name' => 'response_format', 'label' => 'Формат'],
            ['name' => 'output_type', 'label' => 'Тип'],
            ['name' => 'quantity', 'label' => 'Кол-во'],
            ['name' => 'duration_ms', 'label' => 'Время, мс'],
            ['name' => 'total_tokens', 'label' => 'Токены'],
            ['name' => 'created_at', 'label' => 'Создано'],
        ]);

        CRUD::addFilter([
            'name' => 'driver',
            'type' => 'dropdown',
            'label' => 'Драйвер',
        ], function () {
            return collect(config('ai-content-generator.drivers', []))
                ->mapWithKeys(fn ($driver, $key) => [$key => $driver['name'] ?? $key])
                ->toArray();
        }, function ($value) {
            CRUD::addClause('where', 'driver', $value);
        });

        CRUD::addFilter([
            'name' => 'status',
            'type' => 'dropdown',
            'label' => 'Статус',
        ], [
            'success' => 'success',
            'failed' => 'failed',
            'skipped' => 'skipped',
            'pending' => 'pending',
        ], function ($value) {
            CRUD::addClause('where', 'status', $value);
        });
    }

    protected function setupShowOperation(): void
    {
        CRUD::addColumns([
            ['name' => 'driver', 'label' => 'Драйвер'],
            ['name' => 'model', 'label' => 'Модель'],
            ['name' => 'api_key_mask', 'label' => 'API ключ'],
            ['name' => 'status', 'label' => 'Статус'],
            ['name' => 'response_format', 'label' => 'Формат'],
            ['name' => 'output_type', 'label' => 'Тип'],
            ['name' => 'quantity', 'label' => 'Кол-во'],
            ['name' => 'temperature', 'label' => 'Temperature'],
            ['name' => 'max_tokens', 'label' => 'Max tokens'],
            [
                'name' => 'prompt',
                'label' => 'Prompt',
                'type' => 'textarea',
                'wrapper' => [
                    'style' => 'white-space: pre-wrap; word-wrap: break-word; max-width: 100%;',
                ],
            ],
            [
                'name' => 'system_instruction',
                'label' => 'System instruction',
                'type' => 'textarea',
                'wrapper' => [
                    'style' => 'white-space: pre-wrap; word-wrap: break-word; max-width: 100%;',
                ],
            ],
            ['name' => 'payload', 'label' => 'Payload', 'type' => 'json'],
            [
                'name' => 'parsed_response',
                'label' => 'Result',
                'type' => 'json',
                'wrapper' => [
                    'style' => 'white-space: pre-wrap; word-wrap: break-word; max-width: 100%;',
                ],
            ],
            [
                'name' => 'raw_response',
                'label' => 'Raw',
                'type' => 'textarea',
                'wrapper' => [
                    'style' => 'white-space: pre-wrap; word-wrap: break-word; max-width: 100%;',
                ],
            ],
            [
                'name' => 'error_message',
                'label' => 'Ошибка',
                'type' => 'textarea',
                'wrapper' => [
                    'style' => 'white-space: pre-wrap; word-wrap: break-word; max-width: 100%;',
                ],
            ],
            ['name' => 'duration_ms', 'label' => 'Время, мс'],
            ['name' => 'prompt_tokens', 'label' => 'Prompt tokens'],
            ['name' => 'completion_tokens', 'label' => 'Completion tokens'],
            ['name' => 'total_tokens', 'label' => 'Total tokens'],
            ['name' => 'created_at', 'label' => 'Создано'],
        ]);
    }
}
