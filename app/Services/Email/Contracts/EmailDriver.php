<?php

namespace App\Services\Email\Contracts;

interface EmailDriver
{
    /**
     * Test connection to the provider.
     *
     * @return array{success: bool, message: string, latency?: float, error?: string}
     */
    public function testConnection(): array;

    /**
     * Send a test email.
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $from
     * @param string|null $fromName
     * @return array{success: bool, message: string, response?: mixed, error?: string}
     */
    public function sendTestEmail(string $to, string $subject, string $message, string $from, ?string $fromName = null): array;

    /**
     * Get driver-specific configuration for runtime mailer (if applicable).
     *
     * @return array
     */
    public function getMailConfig(): array;
}
