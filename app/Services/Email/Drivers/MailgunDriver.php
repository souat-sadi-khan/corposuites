<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;

class MailgunDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function testConnection(): array
    {
        // Simulate API key validation
        if (empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'API key missing', 'error' => 'No API key'];
        }
        return ['success' => true, 'message' => 'Mailgun API key valid', 'latency' => 0.01];
    }

    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        // Simulate sending
        return ['success' => true, 'message' => 'Mailgun test sent'];
    }

    public function getMailConfig(): array
    {
        return [];
    }
}
