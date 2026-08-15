<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Http\Requests\Email\SendTestEmailRequest;
use App\Models\Email\EmailProvider;
use App\Models\Email\EmailSenderIdentity;
use App\Models\Email\EmailTestLog;
use App\Services\Email\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTestController extends Controller
{
    public function __construct(protected EmailService $emailService)
    {
    }

    /**
     * Show the test email form.
     */
    public function index(EmailProvider $provider): View
    {
        $senderIdentities = $provider->senderIdentities;
        return view('admin.email.test.index', compact('provider', 'senderIdentities'));
    }

    /**
     * Test connection to a provider (AJAX).
     */
    public function testConnection(EmailProvider $provider): JsonResponse
    {
        $result = $this->emailService->testConnection($provider);

        return response()->json($result);
    }

    /**
     * Send a test email (AJAX).
     */
    public function sendTestEmail(Request $request, EmailProvider $provider): JsonResponse
    {
        $senderIdentity = EmailSenderIdentity::find($request->sender_identity_id);
        if (!$senderIdentity || $senderIdentity->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid sender identity.',
            ], 422);
        }

        $result = $this->emailService->sendTestEmail(
            $provider,
            $senderIdentity,
            $request->recipient_email,
            $request->subject,
            $request->message
        );

        return response()->json($result);
    }

    /**
     * Display test logs for a provider.
     */
    public function logs(EmailProvider $provider, Request $request): View
    {
        $logs = EmailTestLog::where('provider_id', $provider->id)
            ->with('senderIdentity')
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('admin.email.test.logs', compact('provider', 'logs'));
    }
}
