<?php

namespace ParabellumKoval\AiContentGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderStatus extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RATE_LIMITED = 'rate_limited';
    public const STATUS_INVALID_KEY = 'invalid_key';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_ERROR = 'error';
    public const STATUS_DISABLED = 'disabled';

    protected $table = 'ai_provider_statuses';

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'last_error_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('ai-content-generator.tables.providers', 'ai_provider_statuses'));
    }

    public function isBlocked(): bool
    {
        if ($this->blocked_until && now()->lessThan($this->blocked_until)) {
            return true;
        }

        return in_array($this->status, [
            self::STATUS_RATE_LIMITED,
            self::STATUS_INVALID_KEY,
            self::STATUS_UNPAID,
            self::STATUS_ERROR,
            self::STATUS_DISABLED,
        ], true);
    }
}
