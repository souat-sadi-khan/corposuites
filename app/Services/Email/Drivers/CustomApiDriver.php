<?php

namespace App\Services\Email\Drivers;

use App\Services\Email\Contracts\EmailDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomApiDriver implements EmailDriver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Endpoint আর API key আছে কিনা check করে, তারপর হালকা একটা request
     * পাঠিয়ে দেখে endpoint reachable কিনা।
     */
    public function testConnection(): array
    {
        if (empty($this->config['endpoint']) || empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'Endpoint or API key missing'];
        }

        if (! filter_var($this->config['endpoint'], FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Invalid endpoint URL'];
        }

        try {
            // অনেক custom API-তে dedicated "ping"/"health" route থাকে না,
            // তাই শুধু connectivity check করি (timeout সহ)। কোনো নির্দিষ্ট
            // "test" route থাকলে সেটা এখানে বসাও।
            $response = Http::withToken($this->config['api_key'])
                ->timeout(10)
                ->connectTimeout(5)
                ->get($this->config['endpoint']);

            // অনেক API root/POST-only endpoint-এ GET করলে 404/405 দেয়,
            // কিন্তু সেটা মানে server reachable + auth handshake হচ্ছে।
            // তাই connection-level failure না হলে success ধরছি।
            if ($response->status() < 500) {
                return [
                    'success' => true,
                    'message' => 'Custom API endpoint reachable (HTTP ' . $response->status() . ')',
                ];
            }

            return [
                'success' => false,
                'message' => 'Custom API server error: HTTP ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('CustomApiDriver testConnection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Custom API connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Custom endpoint-এ POST করে test email পাঠায়।
     * NOTE: প্রতিটা custom API-র payload/field name আলাদা হতে পারে,
     * এই structure-টা একটা reasonable default (JSON body)।
     */
    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array
    {
        if (empty($this->config['endpoint']) || empty($this->config['api_key'])) {
            return ['success' => false, 'message' => 'Endpoint or API key missing'];
        }

        try {
            $payload = [
                'from' => array_filter([
                    'email' => $from,
                    'name' => $fromName,
                ]),
                'to' => [
                    ['email' => $to],
                ],
                'subject' => $subject,
                'html' => nl2br(e($message)),
                'text' => $message,
            ];

            $response = Http::withToken($this->config['api_key'])
                ->acceptJson()
                ->timeout(15)
                ->post($this->config['endpoint'], $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Custom API test email sent (HTTP ' . $response->status() . ')',
                ];
            }

            return [
                'success' => false,
                'message' => 'Custom API send failed: ' . $this->extractErrorMessage($response),
            ];
        } catch (\Throwable $e) {
            Log::error('CustomApiDriver sendTestEmail failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Custom API send failed: ' . $e->getMessage(),
            ];
        }
    }

    public function getMailConfig(): array
    {
        return [];
    }

    protected function extractErrorMessage($response): string
    {
        $data = $response->json();

        if (is_array($data)) {
            return $data['message'] ?? $data['error'] ?? ('HTTP ' . $response->status());
        }

        return 'HTTP ' . $response->status();
    }
}
