<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketAssignmentRequest;
use App\Models\Admin;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Services\TicketAssignmentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TicketAssignmentController extends Controller
{
    use ActivityLogger;

    protected $ticketAssignmentService;

    public function __construct(TicketAssignmentService $ticketAssignmentService)
    {
        $this->ticketAssignmentService = $ticketAssignmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TicketAssignment::query()->with(['ticket', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->assignment_status) {
                $query->where('assignment_status', $request->assignment_status);
            }

            if ($request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->ticket_id) {
                $query->where('ticket_id', $request->ticket_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('ticket', function ($sub) use ($search) {
                        $sub->where('subject', 'like', "%{$search}%")
                            ->orWhere('ticket_number', 'like', "%{$search}%");
                    })->orWhereHas('assignedTo', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('assigned_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.ticket-assignments.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('ticket_col', function ($row) {
                    if (! $row->ticket) {
                        return '<span class="text-danger">Ticket removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->ticket->subject) . '</b><br><small>' . e($row->ticket->ticket_number) . '</small>';
                })
                ->addColumn('agent_col', function ($row) {
                    if (! $row->assignedTo) {
                        return '<span class="text-danger">Agent removed</span>';
                    }

                    return e($row->assignedTo->name) . '<br><small>' . e($row->assignedTo->email) . '</small>';
                })
                ->addColumn('assigned_date_formatted', function ($row) {
                    return $row->assigned_date->format('d M, Y');
                })
                ->addColumn('assignment_status_badge', function ($row) {
                    $map = [
                        'assigned' => 'bg-success',
                        'reassigned' => 'bg-secondary',
                        'cancelled' => 'bg-danger',
                    ];
                    $class = $map[$row->assignment_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . ucfirst($row->assignment_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.ticket-assignments.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'ticket_col', 'agent_col', 'assignment_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.ticket-assignments.index', [
            'admins' => Admin::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ticket-assignments.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketAssignmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $assignment = $this->ticketAssignmentService->create($request->validated());
            $assignment->load('ticket', 'assignedTo');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-assignments',
                'action' => 'create',
                'model' => 'TicketAssignment',
                'model_id' => $assignment->id,
                'description' => 'Ticket "' . ($assignment->ticket->ticket_number ?? $assignment->ticket_id) . '" assigned to ' . ($assignment->assignedTo->name ?? $assignment->assigned_to),
                'new_data' => $assignment->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket assigned successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TicketAssignment $ticketAssignment)
    {
        return view('admin.ticket-assignments.edit', array_merge(
            $this->formData($ticketAssignment),
            ['ticketAssignment' => $ticketAssignment]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketAssignmentRequest $request, TicketAssignment $ticketAssignment)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketAssignment->toArray();
            $updated = $this->ticketAssignmentService->update($ticketAssignment, $request->validated());
            $updated->load('ticket', 'assignedTo');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-assignments',
                'action' => 'update',
                'model' => 'TicketAssignment',
                'model_id' => $ticketAssignment->id,
                'description' => 'Assignment for ticket "' . ($updated->ticket->ticket_number ?? $updated->ticket_id) . '" updated (' . $updated->assignment_status . ')',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.ticket-assignments.index'),
                'message' => 'Assignment updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketAssignment $ticketAssignment)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketAssignment->toArray();

            $this->ticketAssignmentService->delete($ticketAssignment);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-assignments',
                'action' => 'delete',
                'model' => 'TicketAssignment',
                'model_id' => $oldData['id'],
                'description' => 'Assignment deleted for ticket id ' . $oldData['ticket_id'],
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Assignment deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
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

        $model = TicketAssignment::find($id);
        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.',
        ]);
    }

    /**
     * Dropdown data shared by create/edit. Closed tickets are excluded from
     * the picker so the UI doesn't invite reassigning already-finished work;
     * on edit, this record's own ticket is added back so the current
     * selection is never lost — the same picker technique
     * `AssetAssignmentController`/`AssetPurchaseController` use.
     */
    protected function formData(?TicketAssignment $ticketAssignment = null): array
    {
        $tickets = Ticket::active()
            ->where('ticket_status', '!=', 'closed')
            ->when($ticketAssignment, fn ($q) => $q->orWhere('id', $ticketAssignment->ticket_id))
            ->orderBy('ticket_number')
            ->get();

        return [
            'tickets' => $tickets,
            'admins' => Admin::orderBy('name')->get(),
        ];
    }
}
