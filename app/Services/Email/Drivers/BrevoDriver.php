<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoDriver implements EmailDriver
{
    protected array $config;

    protected const BASE_URL = 'https://api.brevo.com/v3';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * API key ভ্যালিড কিনা যাচাই করার জন্য Brevo-র /account endpoint হিট করি।
     */
    public function testConnection(): array
    {
        if (empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'API key missing'];
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->config['api_key'],
                'Accept' => 'application/json',
            ])->get(self::BASE_URL . '/account');

            if ($response->successful()) {
                $data = $response->json();
                $email = $data['email'] ?? 'unknown';

                return [
                    'success' => true,
                    'message' => "Brevo API key valid (account: {$email})",
                ];
            }

            return [
                'success' => false,
                'message' => 'Brevo API error: ' . $this->extractErrorMessage($response),
            ];
        } catch (\Throwable $e) {
            Log::error('Brevo testConnection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Brevo connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Brevo-র /smtp/email endpoint দিয়ে আসল test email পাঠাই।
     */
    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        if (empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'API key missing'];
        }

        try {
            $payload = [
                'sender' => array_filter([
                    'email' => $from,
                    'name' => $fromName,
                ]),
                'to' => [
                    ['email' => $to],
                ],
                'subject' => $subject,
                'htmlContent' => nl2br(e($message)),
                'textContent' => $message,
            ];

            $response = Http::withHeaders([
                'api-key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(self::BASE_URL . '/smtp/email', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'Brevo test email sent successfully (messageId: ' . ($data['messageId'] ?? 'n/a') . ')',
                ];
            }

            return [
                'success' => false,
                'message' => 'Brevo send failed: ' . $this->extractErrorMessage($response),
            ];
        } catch (\Throwable $e) {
            Log::error('Brevo sendTestEmail failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Brevo send failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Laravel mailer config (SMTP transport দিয়ে দিলে queue/mailable সিস্টেমের সাথেও কাজ করবে)।
     */
    public function getMailConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $this->config['smtp_login'] ?? ($this->config['username'] ?? null),
            'password' => $this->config['smtp_key'] ?? ($this->config['api_key'] ?? null),
        ];
    }

    /**
     * Brevo error response থেকে readable message বের করা।
     */
    protected function extractErrorMessage($response): string
    {
        $data = $response->json();

        if (isset($data['message'])) {
            return $data['message'];
        }

        return 'HTTP ' . $response->status();
    }
}
