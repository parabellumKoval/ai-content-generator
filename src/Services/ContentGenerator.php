<?php

namespace ParabellumKoval\AiContentGenerator\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use ParabellumKoval\AiContentGenerator\DTO\GenerationRequest;
use ParabellumKoval\AiContentGenerator\DTO\ResponseDto;
use ParabellumKoval\AiContentGenerator\Exceptions\InvalidKeyException;
use ParabellumKoval\AiContentGenerator\Exceptions\ProviderUnavailableException;
use ParabellumKoval\AiContentGenerator\Exceptions\RateLimitException;
use ParabellumKoval\AiContentGenerator\Exceptions\TimeoutException;
use ParabellumKoval\AiContentGenerator\Models\AiContentGeneration;
use ParabellumKoval\AiContentGenerator\Models\AiProviderStatus;
use ParabellumKoval\AiContentGenerator\Services\Parsing\ResponseParser;

class ContentGenerator
{
    public function __construct(
        protected DriverRegistry $drivers,
        protected ProviderStatusRepository $providerStatuses,
        protected ResponseParser $parser,
    ) {
    }

    /**
     * @param array<string, mixed>|GenerationRequest $payload
     */
    public function generate(array|GenerationRequest $payload): ResponseDto
    {
        $request = $payload instanceof GenerationRequest ? $payload : GenerationRequest::fromArray($payload);
        $startedAt = microtime(true);

        $driverConfig = $this->drivers->configFor($request->driver);
        $payloadModel = $request->payload['model'] ?? null;
        if (!$request->model && is_string($payloadModel) && $payloadModel !== '') {
            $request->model = $payloadModel;
        }

        if (!$request->model) {
            $request->model = $driverConfig['default_model'] ?? null;
        }
        $this->recoverMissingKeyStatus($request->driver, $driverConfig);

        $log = null;
        if (config('ai-content-generator.logging.enabled', true)) {
            $log = AiContentGeneration::startFromRequest($request);
            $apiKeyMask = $this->maskKey($driverConfig['api_key'] ?? null);
            if ($apiKeyMask && Schema::hasColumn($log->getTable(), 'api_key_mask')) {
                $log->update(['api_key_mask' => $apiKeyMask]);
            }
        }

        try {
            if (isset($driverConfig['enabled']) && !$driverConfig['enabled']) {
                throw new ProviderUnavailableException($request->driver, 'Драйвер отключен в настройках.');
            }

            $this->providerStatuses->assertAvailable($request->driver, $request->force);

            $driver = $this->drivers->get($request->driver);
            $driverResponse = $driver->generate($request);

            $result = $this->parser->parse(
                $driverResponse->messages,
                $request->responseFormat,
                $request->outputType,
                $request->quantity,
                $driverResponse->artifacts
            );

            $duration = (int) ((microtime(true) - $startedAt) * 1000);

            if ($log) {
                $log->markSuccess([
                    'model' => $driverResponse->model ?? $request->model,
                    'raw_response' => json_encode($driverResponse->raw),
                    'parsed_response' => $result,
                    'duration_ms' => $duration,
                    'prompt_tokens' => $driverResponse->usage['prompt_tokens'] ?? null,
                    'completion_tokens' => $driverResponse->usage['completion_tokens'] ?? null,
                    'total_tokens' => $driverResponse->usage['total_tokens'] ?? null,
                ]);
            }

            $this->providerStatuses->markAvailable($request->driver);

            return new ResponseDto(
                driver: $request->driver,
                status: 'success',
                result: $result,
                meta: [
                    'model' => $driverResponse->model ?? $request->model,
                    'usage' => $driverResponse->usage,
                    'response_format' => $request->responseFormat,
                    'output_type' => $request->outputType,
                    'duration_ms' => $duration,
                    'artifacts' => $driverResponse->artifacts,
                ],
                raw: $driverResponse->raw,
            );
        } catch (ProviderUnavailableException $e) {
            $this->markFailure($log, 'skipped', 'provider_unavailable', $e->getMessage(), $startedAt);
            throw $e;
        } catch (RateLimitException $e) {
            $this->providerStatuses->markRateLimited($request->driver, $e->getMessage(), $e->retryAfter());
            $this->markFailure($log, 'failed', 'rate_limit', $e->getMessage(), $startedAt);
            throw $e;
        } catch (InvalidKeyException $e) {
            $this->providerStatuses->markInvalidKey($request->driver, $e->getMessage());
            $this->markFailure($log, 'failed', 'invalid_key', $e->getMessage(), $startedAt);
            throw $e;
        } catch (TimeoutException $e) {
            $this->providerStatuses->markError($request->driver, $e->getMessage(), 'timeout');
            $this->markFailure($log, 'failed', 'timeout', $e->getMessage(), $startedAt);
            throw $e;
        } catch (\Throwable $e) {
            $this->providerStatuses->markError($request->driver, $e->getMessage(), 'unexpected');
            $this->markFailure($log, 'failed', 'unexpected', $e->getMessage(), $startedAt);
            Log::channel('daily')->error('[ai-content-generator] ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    protected function markFailure(?AiContentGeneration $log, string $status, ?string $code, string $message, float $startedAt): void
    {
        if (!$log) {
            return;
        }

        $duration = (int) ((microtime(true) - $startedAt) * 1000);

        $log->markFailure($status, $code, $message, [
            'duration_ms' => $duration,
        ]);
    }

    protected function recoverMissingKeyStatus(string $driver, array $driverConfig): void
    {
        if (empty($driverConfig['api_key'])) {
            return;
        }

        try {
            $status = $this->providerStatuses->get($driver);
        } catch (\Throwable) {
            return;
        }

        if (
            $status->status !== AiProviderStatus::STATUS_INVALID_KEY
            || $status->error_code !== 'invalid_key'
            || !str_contains((string) $status->message, 'API key is missing')
        ) {
            return;
        }

        $this->providerStatuses->markAvailable($driver);
    }

    protected function maskKey(?string $key): ?string
    {
        if (!$key) {
            return null;
        }

        $start = substr($key, 0, 15);
        $end = substr($key, -15);

        return $start . '...' . $end;
    }
}
