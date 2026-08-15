<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailCommunicationRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\EmailCommunication;
use App\Models\Lead;
use App\Services\EmailCommunicationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmailCommunicationController extends Controller
{
    use ActivityLogger;

    protected $emailCommunicationService;

    public function __construct(EmailCommunicationService $emailCommunicationService)
    {
        $this->emailCommunicationService = $emailCommunicationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmailCommunication::query()->with(['lead', 'contact', 'company', 'createdBy']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by direction
            if ($request->direction) {
                $query->where('direction', $request->direction);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('from_email', 'like', "%{$search}%")
                      ->orWhere('to_email', 'like', "%{$search}%");
                });
            }

            $query->orderBy('sent_at', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.email-communications.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('subject', function ($row) {
                    return '<b class="tl-name-txt">' . $row->subject . '</b><br><small>' . ucfirst($row->direction) . '</small>';
                })
                ->addColumn('participants', function ($row) {
                    return ($row->from_email ?? '-') . ' &rarr; ' . ($row->to_email ?? '-');
                })
                ->addColumn('related_to', function ($row) {
                    if ($row->lead) {
                        return 'Lead: ' . $row->lead->name;
                    }
                    if ($row->contact) {
                        return 'Contact: ' . $row->contact->name;
                    }
                    if ($row->company) {
                        return 'Company: ' . $row->company->name;
                    }
                    return '-';
                })
                ->addColumn('sent_at_formatted', function ($row) {
                    return $row->sent_at ? $row->sent_at->format('d M, Y h:i A') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.email-communications.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'subject', 'participants', 'action'])
                ->make(true);
        }

        return view('admin.email-communications.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();

        return view('admin.email-communications.create', compact('leads', 'contacts', 'companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmailCommunicationRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = auth()->guard('admin')->id();

            $emailCommunication = $this->emailCommunicationService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'email-communications',
                'action' => 'create',
                'model' => 'EmailCommunication',
                'model_id' => $emailCommunication->id,
                'description' => 'Email communication "' . $emailCommunication->subject . '" logged',
                'new_data' => $emailCommunication->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Email communication logged successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailCommunication $emailCommunication)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();

        return view('admin.email-communications.edit', compact('emailCommunication', 'leads', 'contacts', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmailCommunicationRequest $request, EmailCommunication $emailCommunication)
    {
        DB::beginTransaction();

        try {
            $oldData = $emailCommunication->toArray();
            $updatedEmailCommunication = $this->emailCommunicationService->update($emailCommunication, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'email-communications',
                'action' => 'update',
                'model' => 'EmailCommunication',
                'model_id' => $emailCommunication->id,
                'description' => 'Email communication "' . $emailCommunication->subject . '" updated',
                'new_data' => $updatedEmailCommunication->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.email-communications.index'),
                'message' => 'Email communication updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailCommunication $emailCommunication)
    {
        DB::beginTransaction();

        try {
            $oldData = $emailCommunication->toArray();

            $this->emailCommunicationService->delete($emailCommunication);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'email-communications',
                'action' => 'delete',
                'model' => 'EmailCommunication',
                'model_id' => $oldData['id'],
                'description' => 'Email communication "' . $oldData['subject'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Email communication deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = EmailCommunication::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.'
        ]);
    }
}
