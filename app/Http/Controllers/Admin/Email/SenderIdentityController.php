<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSenderIdentityRequest;
use App\Http\Requests\Admin\UpdateSenderIdentityRequest;
use App\Models\Email\EmailProvider;
use App\Models\Email\EmailSenderIdentity;
use App\Services\Email\EmailService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SenderIdentityController extends Controller
{
    use ActivityLogger;

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of sender identities for a provider.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmailSenderIdentity::select('email_sender_identities.*');

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Filter by default
            // if ($request->has('is_default')) {
            //     $query->where('is_default', $request->is_default);
            // }

            $query->orderBy('is_default', 'desc')->orderBy('id', 'desc');

            return DataTables::eloquent($query)
                ->addColumn('full_name', function ($row) {
                    return $row->name ? $row->name . ' <' . $row->email . '>' : $row->email;
                })
                ->addColumn('provider', function($row) {
                    return $row->provider ? $row->provider->name : 'N/A';
                })
                ->editColumn('is_default', function ($row) {
                    return $row->is_default ? '<i class="ri-check-line text-success fs-5"></i>' : '';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.email.sender-identities.action', compact('row'))->render();
                })
                ->rawColumns(['is_default', 'provider', 'action'])
                ->make(true);
        }

        return view('admin.email.sender-identities.index');
    }

    /**
     * Show the form for creating a new sender identity.
     */
    public function create()
    {
        $providers = EmailProvider::where('is_enabled', 1)->get();

        return view('admin.email.sender-identities.create', compact('providers'));
    }

    /**
     * Store a newly created sender identity.
     */
    public function store(StoreSenderIdentityRequest $request)
    {
        $data = $request->validated();

        // Use service (we'll add method to EmailService or use directly)
        // For now, we'll use the service's create method (assuming we added it)
        $result = $this->emailService->createSenderIdentity($data);

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ]);
        }

        $provider = EmailProvider::find($request->provider_id);

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'email',
            'action' => 'create',
            'model' => 'EmailSenderIdentity',
            'model_id' => $result['data']->id,
            'description' => 'Sender identity ' . $result['data']->email . ' created for provider ' . $provider->name,
            'new_data' => $result['data']->toArray(),
            'old_data' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Show the form for editing the specified sender identity.
     */
    public function edit(EmailProvider $provider, EmailSenderIdentity $identity)
    {
        $providers = EmailProvider::where('is_enabled', 1)->get();
        return view('admin.email.sender-identities.edit', compact('providers', 'identity'));
    }

    /**
     * Update the specified sender identity.
     */
    public function update(UpdateSenderIdentityRequest $request, EmailSenderIdentity $identity)
    {
        $result = $this->emailService->updateSenderIdentity($identity->id, $request->validated());

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
            'model' => 'EmailSenderIdentity',
            'model_id' => $identity->id,
            'description' => 'Sender identity ' . $identity->email . ' updated',
            'new_data' => $result['data']->toArray(),
            'old_data' => $identity->toArray(),
        ]);

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Remove the specified sender identity.
     */
    public function destroy(EmailProvider $provider, EmailSenderIdentity $identity)
    {
        $oldData = $identity->toArray();

        $result = $this->emailService->deleteSenderIdentity($identity->id);

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
            'model' => 'EmailSenderIdentity',
            'model_id' => $identity->id,
            'description' => 'Sender identity ' . $oldData['email'] . ' deleted',
            'old_data' => $oldData,
            'new_data' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Set a sender identity as default for the provider.
     */
    public function setDefault(Request $request, EmailProvider $provider, EmailSenderIdentity $identity)
    {
        $result = $this->emailService->setDefaultSenderIdentity($identity->id);

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

    public function testEmail(EmailSenderIdentity $identity)
    {
        return view('admin.email.sender-identities.send-email', compact('identity'));
    }

    public function sendTestEmail(Request $request, EmailSenderIdentity $identity)
    {
        $provider = EmailProvider::findOrFail($identity->provider_id);
        $result = $this->emailService->sendTestEmail($provider, $identity, $request->to, $request->subject, $request->content);

        return response()->json($result);
    }
}
