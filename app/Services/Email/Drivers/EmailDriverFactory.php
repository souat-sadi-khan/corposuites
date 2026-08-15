<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;
use InvalidArgumentException;

class EmailDriverFactory
{
    public static function make(string $type, array $config): EmailDriver
    {
        return match ($type) {
            'smtp' => new SmtpDriver($config),
            'sendgrid' => new SendGridDriver($config),
            'mailgun' => new MailgunDriver($config),
            'ses' => new SesDriver($config),
            'resend' => new ResendDriver($config),
            'postmark' => new PostmarkDriver($config),
            'brevo' => new BrevoDriver($config),
            'custom_api' => new CustomApiDriver($config),
            default => throw new InvalidArgumentException("Unsupported provider type: $type"),
        };
    }
}
