<?php

namespace ParabellumKoval\AiContentGenerator\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use ParabellumKoval\AiContentGenerator\Services\DriverRegistry;
use ParabellumKoval\AiContentGenerator\Services\ProviderStatusRepository;

class ProviderStatusController extends Controller
{
    public function __construct(
        protected ProviderStatusRepository $statuses,
        protected DriverRegistry $drivers
    ) {
    }

    public function index()
    {
        $providers = [];

        foreach ($this->drivers->all() as $key => $config) {
            $status = $this->statuses->get($key);
            $providers[] = [
                'key' => $key,
                'name' => $config['name'] ?? $key,
                'status' => $status->status,
                'message' => $status->message,
                'blocked_until' => $status->blocked_until,
                'last_error_at' => $status->last_error_at,
            ];
        }

        return view('ai-content-generator::admin.provider-status', compact('providers'));
    }

    public function reset(string $driver)
    {
        $this->statuses->clear($driver);
        \Alert::add('success', "Статус драйвера {$driver} сброшен.")->flash();

        return redirect()->back();
    }
}
