<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Models\Email\EmailTestLog;

class ProviderHealthService
{
    public function getHealth(EmailProvider $provider): array
    {
        $latestLog = EmailTestLog::where('provider_id', $provider->id)->orderBy('sent_at', 'desc')->first();
        $lastSuccess = EmailTestLog::where('provider_id', $provider->id)->where('status', 'success')->orderBy('sent_at', 'desc')->first();
        $lastFailure = EmailTestLog::where('provider_id', $provider->id)->where('status', 'failed')->orderBy('sent_at', 'desc')->first();

        $logs = EmailTestLog::where('provider_id', $provider->id)->orderBy('sent_at', 'desc')->limit(10)->get();
        $total = $logs->count();
        $successCount = $logs->where('status', 'success')->count();
        $successRate = $total > 0 ? ($successCount / $total) * 100 : 0;

        return [
            'status' => $provider->health_status ?? 'unknown',
            'last_tested_at' => $latestLog?->sent_at?->toDateTimeString(),
            'last_success_at' => $lastSuccess?->sent_at?->toDateTimeString(),
            'last_failure_at' => $lastFailure?->sent_at?->toDateTimeString(),
            'success_rate' => round($successRate, 2),
        ];
    }

    public function checkNow(EmailProvider $provider, EmailConnectionTester $tester): array
    {
        $result = $tester->test($provider);
        $health = $this->getHealth($provider);
        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'health' => $health,
        ];
    }
}
