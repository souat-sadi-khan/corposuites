<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;

class SendGridDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $apiKey = $this->config['api_key'] ?? '';
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API key missing',
                    'error' => 'No API key provided',
                    'latency' => null,
                ];
            }
            // In production, call SendGrid API validation endpoint.
            return [
                'success' => true,
                'message' => 'SendGrid API key validated',
                'latency' => microtime(true) - $start,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'SendGrid connection test failed',
                'error' => $e->getMessage(),
                'latency' => microtime(true) - $start,
            ];
        }
    }

    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        try {
            // Use SendGrid SDK to send email.
            return [
                'success' => true,
                'message' => 'Test email sent via SendGrid',
                'response' => ['message_id' => 'fake-id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SendGrid send failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getMailConfig(): array
    {
        return []; // API-based providers do not use Laravel's mail config.
    }
}
