<?php

namespace App\Models\Email;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailProvider extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Provider type constants
     */
    public const TYPE_SMTP = 'smtp';
    public const TYPE_SENDGRID = 'sendgrid';
    public const TYPE_MAILGUN = 'mailgun';
    public const TYPE_SES = 'ses';
    public const TYPE_RESEND = 'resend';
    public const TYPE_POSTMARK = 'postmark';
    public const TYPE_BREVO = 'brevo';
    public const TYPE_CUSTOM_API = 'custom_api';

    /**
     * Health status constants
     */
    public const HEALTH_UNKNOWN = 'unknown';
    public const HEALTH_HEALTHY = 'healthy';
    public const HEALTH_UNHEALTHY = 'unhealthy';

    protected $fillable = [
        'name',
        'type',
        'config',
        'is_enabled',
        'is_default',
        'sandbox_mode',
        'maintenance_mode',
        'health_status',
        'last_health_check_at',
        'timeout',
    ];

    protected $casts = [
        'config' => 'json',
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'sandbox_mode' => 'boolean',
        'maintenance_mode' => 'boolean',
        'last_health_check_at' => 'datetime',
        'timeout' => 'integer',
    ];

    /**
     * Relationships
     */
    public function senderIdentities()
    {
        return $this->hasMany(EmailSenderIdentity::class, 'provider_id');
    }

    public function testLogs()
    {
        return $this->hasMany(EmailTestLog::class);
    }

    /**
     * Simple helper methods
     */
    public function isSmtp(): bool
    {
        return $this->type === self::TYPE_SMTP;
    }

    public function isApiBased(): bool
    {
        return in_array($this->type, [
            self::TYPE_SENDGRID,
            self::TYPE_MAILGUN,
            self::TYPE_SES,
            self::TYPE_RESEND,
            self::TYPE_POSTMARK,
            self::TYPE_BREVO,
            self::TYPE_CUSTOM_API,
        ]);
    }

    public function isHealthy(): bool
    {
        return $this->health_status === self::HEALTH_HEALTHY;
    }

    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
