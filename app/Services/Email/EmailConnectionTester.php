<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Services\Email\Drivers\EmailDriverFactory;

class EmailConnectionTester
{
    public function test(EmailProvider $provider): array
    {
        if (!$provider->is_enabled) {
            return ['status' => false, 'message' => 'Provider is disabled', 'error' => 'Cannot test disabled provider'];
        }

        try {
            $driver = EmailDriverFactory::make($provider->type, $provider->config ?? []);
            $result = $driver->testConnection();

            $provider->health_status = $result['success'] ? 'healthy' : 'unhealthy';
            $provider->last_health_check_at = now();
            $provider->save();

            return $result;
        } catch (\Exception $e) {
            $provider->health_status = 'unhealthy';
            $provider->last_health_check_at = now();
            $provider->save();

            return [
                'status' => false,
                'message' => 'Test failed with exception',
                'error' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }
}
