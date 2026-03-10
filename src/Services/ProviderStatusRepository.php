<?php

namespace ParabellumKoval\AiContentGenerator\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;
use ParabellumKoval\AiContentGenerator\Exceptions\ProviderUnavailableException;
use ParabellumKoval\AiContentGenerator\Models\AiProviderStatus;

class ProviderStatusRepository
{
    protected bool $checked = false;
    protected bool $tableExists = false;

    public function get(string $driver): AiProviderStatus
    {
        if (!$this->tableReady()) {
            return new AiProviderStatus([
                'driver' => $driver,
                'status' => AiProviderStatus::STATUS_AVAILABLE,
            ]);
        }

        return AiProviderStatus::query()->firstOrCreate(
            ['driver' => $driver],
            ['status' => AiProviderStatus::STATUS_AVAILABLE]
        );
    }

    public function assertAvailable(string $driver, bool $force = false): AiProviderStatus
    {
        $status = $this->get($driver);

        if (!$force && $status->isBlocked()) {
            if ($this->canAutoRecover($status)) {
                $this->markAvailable($driver);

                return $this->get($driver);
            }

            $reason = $status->message ?? 'provider is marked as unavailable';
            throw new ProviderUnavailableException($driver, $reason);
        }

        return $status;
    }

    public function markAvailable(string $driver): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $this->get($driver)->update([
            'status' => AiProviderStatus::STATUS_AVAILABLE,
            'error_code' => null,
            'message' => null,
            'blocked_until' => null,
        ]);
    }

    public function markRateLimited(string $driver, string $message, ?int $retryAfter = null): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $blockedUntil = $retryAfter
            ? now()->addSeconds($retryAfter)
            : now()->addMinutes(config('ai-content-generator.rate_limit.cooldown_minutes', 10));

        $this->persistStatus(
            $driver,
            AiProviderStatus::STATUS_RATE_LIMITED,
            $message,
            'rate_limit',
            $blockedUntil
        );
    }

    public function markInvalidKey(string $driver, string $message): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $this->persistStatus(
            $driver,
            AiProviderStatus::STATUS_INVALID_KEY,
            $message,
            'invalid_key'
        );
    }

    public function markError(string $driver, string $message, ?string $code = null): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $cooldownMinutes = max(0, (int) config('ai-content-generator.error.cooldown_minutes', 2));
        $blockedUntil = $cooldownMinutes > 0
            ? now()->addMinutes($cooldownMinutes)
            : null;

        $this->persistStatus(
            $driver,
            AiProviderStatus::STATUS_ERROR,
            $message,
            $code,
            $blockedUntil
        );
    }

    public function clear(string $driver): void
    {
        if (!$this->tableReady()) {
            return;
        }

        $this->get($driver)->update([
            'status' => AiProviderStatus::STATUS_AVAILABLE,
            'error_code' => null,
            'message' => null,
            'last_error_at' => null,
            'blocked_until' => null,
            'meta' => null,
        ]);
    }

    public function all(): array
    {
        if (!$this->tableReady()) {
            return [];
        }

        return AiProviderStatus::query()->orderBy('driver')->get()->keyBy('driver')->toArray();
    }

    protected function persistStatus(string $driver, string $status, string $message, ?string $code = null, ?CarbonInterface $blockedUntil = null): void
    {
        $this->get($driver)->update([
            'status' => $status,
            'message' => $message,
            'error_code' => $code,
            'last_error_at' => now(),
            'blocked_until' => $blockedUntil,
        ]);
    }

    protected function canAutoRecover(AiProviderStatus $status): bool
    {
        if ($status->status !== AiProviderStatus::STATUS_ERROR) {
            return false;
        }

        return $status->blocked_until === null || now()->greaterThanOrEqualTo($status->blocked_until);
    }

    protected function tableReady(): bool
    {
        if ($this->checked) {
            return $this->tableExists;
        }

        $this->checked = true;

        try {
            $this->tableExists = Schema::hasTable(config('ai-content-generator.tables.providers', 'ai_provider_statuses'));
        } catch (\Throwable $e) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }
}
