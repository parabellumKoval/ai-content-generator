<?php

use Illuminate\Support\Facades\Route;
use ParabellumKoval\AiContentGenerator\Http\Controllers\Admin\AiContentGenerationCrudController;
use ParabellumKoval\AiContentGenerator\Http\Controllers\Admin\ProviderStatusController;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => ['web', config('backpack.base.middleware_key', 'admin')],
], function () {
    Route::crud('ai-content-generations', AiContentGenerationCrudController::class);

    Route::get('ai-content-generator/providers', [ProviderStatusController::class, 'index'])
        ->name('ai-content-generator.providers.index');

    Route::post('ai-content-generator/providers/{driver}/reset', [ProviderStatusController::class, 'reset'])
        ->name('ai-content-generator.providers.reset');
});
