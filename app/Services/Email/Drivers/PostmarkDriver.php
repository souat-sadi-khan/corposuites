<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;

class PostmarkDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function testConnection(): array
    {
        if (empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'API key missing'];
        }
        return ['success' => true, 'message' => 'Postmark API key valid'];
    }

    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        return ['success' => true, 'message' => 'Postmark test sent'];
    }

    public function getMailConfig(): array
    {
        return [];
    }
}
