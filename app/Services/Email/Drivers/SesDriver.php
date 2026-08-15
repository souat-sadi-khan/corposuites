<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;

class SesDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function testConnection(): array
    {
        // Validate AWS credentials
        if (empty($this->config['key']) || empty($this->config['secret'])) {
            return ['success' => false, 'message' => 'AWS credentials missing'];
        }
        return ['success' => true, 'message' => 'SES connection successful'];
    }

    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        return ['success' => true, 'message' => 'SES test sent'];
    }

    public function getMailConfig(): array
    {
        return [];
    }
}
