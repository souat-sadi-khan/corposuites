<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Models\Email\EmailSenderIdentity;
use App\Models\Email\EmailTestLog;
use App\Services\Email\Drivers\EmailDriverFactory;
use Illuminate\Support\Facades\DB;

class TestEmailService
{
    public function sendTest(
        EmailProvider $provider,
        EmailSenderIdentity $senderIdentity,
        string $recipientEmail,
        string $subject,
        string $message
    ): array {
        if ($senderIdentity->provider_id !== $provider->id) {
            return [
                'status' => false,
                'message' => 'Sender identity mismatch',
                'errors' => ['sender' => 'Does not belong to this provider'],
            ];
        }

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Invalid recipient email', 'errors' => ['recipient' => 'Invalid email']];
        }

        if (!$provider->is_enabled) {
            return ['status' => false, 'message' => 'Provider is disabled'];
        }

        try {
            $driver = EmailDriverFactory::make($provider->type, $provider->config ?? []);
            $sendResult = $driver->sendTestEmail(
                $recipientEmail,
                $subject,
                $message,
                $senderIdentity->email,
                $senderIdentity->name
            );

            $log = EmailTestLog::create([
                'provider_id' => $provider->id,
                'sender_identity_id' => $senderIdentity->id,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => $sendResult['success'] ? 'success' : 'failed',
                'error_message' => $sendResult['error'] ?? null,
                'response' => $sendResult['response'] ?? null,
                'sent_at' => now(),
            ]);

            // Update provider health
            $provider->health_status = $sendResult['success'] ? 'healthy' : 'unhealthy';
            $provider->last_health_check_at = now();
            $provider->save();

            return [
                'status' => $sendResult['success'],
                'message' => $sendResult['message'],
                'data' => $log,
            ];
        } catch (\Exception $e) {
            $log = EmailTestLog::create([
                'provider_id' => $provider->id,
                'sender_identity_id' => $senderIdentity->id,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response' => null,
                'sent_at' => now(),
            ]);

            return [
                'status' => false,
                'message' => 'Test email failed',
                'errors' => ['exception' => $e->getMessage()],
                'data' => $log,
            ];
        }
    }
}
