<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmailProviderRequest;
use App\Http\Requests\Admin\UpdateEmailProviderRequest;
use App\Models\Email\EmailProvider;
use App\Models\Email\EmailTestLog;
use App\Services\Email\EmailService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class EmailProviderController extends Controller
{
    use ActivityLogger;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of email providers.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmailProvider::query()
                ->withCount('senderIdentities')
                ->select('email_providers.*');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('is_enabled', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            }

            $query->orderBy('is_default', 'desc')->orderBy('id', 'desc');

            return DataTables::eloquent($query)
                ->addColumn('type_badge', function ($row) {
                    return '<span class="badge bg-info">' . strtoupper($row->type) . '</span>';
                })
                ->addColumn('sender_count', function ($row) {
                    return $row->sender_identities_count;
                })
                ->addColumn('health_status', function ($row) {
                    $status = $row->health_status ?? 'unknown';
                    $color = match ($status) {
                        'healthy' => 'done',
                        'unhealthy' => 'canc',
                        default => 'pend',
                    };
                    return '<span class="badge-s bs-' . $color . '">' . ucfirst($status) . '</span>';
                })
                ->editColumn('is_enabled', function ($row) {
                    $checked = $row->is_enabled ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.email.providers.status', $row->id) . '" class="switch form-control-sm form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->editColumn('is_default', function ($row) {
                    return $row->is_default ? '<i class="ri-check-line text-success fs-5"></i>' : '';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.email.providers.action', compact('row'))->render();
                })
                ->rawColumns(['type_badge', 'health_status', 'is_enabled', 'is_default', 'action'])
                ->make(true);
        }

        return view('admin.email.providers.index');
    }

    /**
     * Show the form for creating a new provider.
     */
    public function create()
    {
        return view('admin.email.providers.create');
    }

    /**
     * Store a newly created provider.
     */
    public function store(StoreEmailProviderRequest $request)
    {
        // The request already validated, but we still use the service
        $result = $this->emailService->createProvider($request->validated());

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ]);
        }

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'create',
            'model' => 'EmailProvider',
            'model_id' => $result['data']->id,
            'description' => 'Email provider ' . $result['data']->name . ' created',
            'new_data' => $result['data']->toArray(),
            'old_data' => null,
        ]);

        return response()->json([
            'status' => true,
            'goto' => route('admin.email.providers.index'),
            'message' => $result['message'],
        ]);
    }

    /**
     * Show the form for editing the specified provider.
     */
    public function edit(string $id)
    {
        $provider = EmailProvider::with('senderIdentities')->findOrFail($id);
        return view('admin.email.providers.edit', compact('provider'));
    }

    /**
     * Update the specified provider.
     */
    public function update(UpdateEmailProviderRequest $request, string $id)
    {
        $result = $this->emailService->updateProvider($id, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ]);
        }

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'update',
            'model' => 'EmailProvider',
            'model_id' => $id,
            'description' => 'Email provider ' . $result['data']->name . ' updated',
            'new_data' => $result['data']->toArray(),
            'old_data' => null, // optional
        ]);

        return response()->json([
            'status' => true,
            'goto' => route('admin.email.providers.index'),
            'message' => $result['message'],
        ]);
    }

    /**
     * Remove the specified provider.
     */
    public function destroy(string $id)
    {
        $provider = EmailProvider::findOrFail($id);
        $oldData = $provider->toArray();

        $result = $this->emailService->deleteProvider($id);

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ]);
        }

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'delete',
            'model' => 'EmailProvider',
            'model_id' => $id,
            'description' => 'Email provider ' . $oldData['name'] . ' deleted',
            'old_data' => $oldData,
            'new_data' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Toggle provider status (enable/disable).
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $result = $request->input('status')
            ? $this->emailService->enableProvider($id)
            : $this->emailService->disableProvider($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Set a provider as default.
     */
    public function setDefault(string $id)
    {
        $result = $this->emailService->setDefaultProvider($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Test connection for a provider (AJAX).
     */
    public function testConnection(string $id)
    {
        $provider = EmailProvider::findOrFail($id);
        $result = $this->emailService->testConnection($provider);

        // Log activity
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'test_connection',
            'model' => 'EmailProvider',
            'model_id' => $id,
            'description' => 'Connection test for provider ' . $provider->name,
            'new_data' => ['result' => $result],
        ]);

        return response()->json($result);
    }

    /**
     * Send a test email (AJAX).
     */
    public function sendTestEmail(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'sender_identity_id' => 'required|exists:email_sender_identities,id',
            'recipient_email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ]);
        }

        $provider = EmailProvider::findOrFail($id);
        $senderIdentity = \App\Models\Email\EmailSenderIdentity::findOrFail($request->sender_identity_id);

        $result = $this->emailService->sendTestEmail(
            $provider,
            $senderIdentity,
            $request->recipient_email,
            $request->subject ?: 'Test Email',
            $request->message ?: 'This is a test email from the system.'
        );

        // Log activity
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'send_test_email',
            'model' => 'EmailProvider',
            'model_id' => $id,
            'description' => 'Test email sent via provider ' . $provider->name . ' to ' . $request->recipient_email,
            'new_data' => ['result' => $result],
        ]);

        return response()->json($result);
    }

    /**
     * Display test logs for a provider.
     */
    public function logs(string $id)
    {
        $provider = EmailProvider::findOrFail($id);
        $logs = EmailTestLog::where('provider_id', $id)
            ->with('senderIdentity')
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('admin.email.providers.logs', compact('provider', 'logs'));
    }
}
