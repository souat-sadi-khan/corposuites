<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Services\TicketEscalationService;
use App\Services\TicketService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TicketController extends Controller
{
    use ActivityLogger;

    protected $ticketService;

    protected $ticketEscalationService;

    public function __construct(TicketService $ticketService, TicketEscalationService $ticketEscalationService)
    {
        $this->ticketService = $ticketService;
        $this->ticketEscalationService = $ticketEscalationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Ticket::with(['category', 'customer', 'raisedByEmployee', 'ticketStatus', 'ticketPriority']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->ticket_category_id) {
                $query->where('ticket_category_id', $request->ticket_category_id);
            }

            if ($request->ticket_status) {
                $query->where('ticket_status', $request->ticket_status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            // Overdue is a computed condition (no stored flag), so it is
            // expressed as a date comparison scoped to still-open tickets.
            if ($request->overdue) {
                $query->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->whereNotIn('ticket_status', Ticket::CLOSED_STATUSES);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('ticket_number', 'like', "%{$search}%")
                        ->orWhere('requester_name', 'like', "%{$search}%")
                        ->orWhere('requester_email', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.tickets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('subject_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->subject) . '</b><br><small>' . e($row->ticket_number) . '</small>';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category
                        ? e($row->category->name)
                        : '<span class="text-danger">Uncategorised</span>';
                })
                ->addColumn('requester_col', function ($row) {
                    $label = $row->requester_label ?: '<span class="text-muted">Unknown</span>';
                    $sub = $row->requester_email ?: $row->requester_phone;

                    return e($label) . ($sub ? '<br><small>' . e($sub) . '</small>' : '');
                })
                ->addColumn('due_col', function ($row) {
                    if (! $row->due_date) {
                        return '<span class="text-muted">No due date</span>';
                    }

                    $line = $row->due_date->format('d M Y');

                    if ($row->is_overdue) {
                        $late = now()->startOfDay()->diffInDays($row->due_date->copy()->startOfDay());
                        $line .= '<br><small class="text-danger">Overdue by ' . $late . ' ' . Str::plural('day', $late) . '</small>';
                    } elseif ($row->ticket_status === 'resolved' && $row->resolved_at) {
                        $line .= '<br><small class="text-success">Resolved ' . $row->resolved_at->format('d M Y') . '</small>';
                    } elseif ($row->ticket_status === 'closed' && $row->closed_at) {
                        $line .= '<br><small class="text-muted">Closed ' . $row->closed_at->format('d M Y') . '</small>';
                    }

                    return $line;
                })
                ->addColumn('priority_badge', function ($row) {
                    $map = [
                        'low' => 'bg-secondary',
                        'medium' => 'bg-info',
                        'high' => 'bg-warning',
                        'urgent' => 'bg-danger',
                    ];

                    $badge = '<span class="badge ' . ($map[$row->priority] ?? 'bg-secondary') . '">' . e($row->priority_label) . '</span>';

                    // Same additive display Ticket Status uses for its own
                    // optional custom status — the fixed enum badge is never
                    // replaced, just given an optional second line.
                    if ($row->ticketPriority) {
                        $color = $row->ticketPriority->color ? ' style="color:' . e($row->ticketPriority->color) . ';"' : '';
                        $badge .= '<br><small' . $color . '>' . e($row->ticketPriority->name) . '</small>';
                    }

                    return $badge;
                })
                ->addColumn('ticket_status_badge', function ($row) {
                    $map = [
                        'open' => 'bg-primary',
                        'in_progress' => 'bg-info',
                        'on_hold' => 'bg-warning',
                        'resolved' => 'bg-success',
                        'closed' => 'bg-secondary',
                    ];

                    $badge = '<span class="badge ' . ($map[$row->ticket_status] ?? 'bg-secondary') . '">' . e($row->ticket_status_label) . '</span>';

                    // The custom, configurable status (if one is set) is shown
                    // as an additional line under the fixed enum badge, not in
                    // place of it — the same additive display Chart of
                    // Accounts uses for its own optional Account Type badge.
                    if ($row->ticketStatus) {
                        $color = $row->ticketStatus->color ? ' style="color:' . e($row->ticketStatus->color) . ';"' : '';
                        $badge .= '<br><small' . $color . '>' . e($row->ticketStatus->name) . '</small>';
                    }

                    return $badge;
                })
                ->addColumn('sla_col', function ($row) {
                    if (! $row->first_response_due_at && ! $row->resolution_due_at) {
                        return '<span class="text-muted">No SLA</span>';
                    }

                    $lines = [];

                    if ($row->first_response_due_at) {
                        if ($row->first_responded_at) {
                            $lines[] = '<small class="text-success">Responded ' . $row->first_responded_at->format('d M, H:i') . '</small>';
                        } elseif ($row->is_response_breached) {
                            $lines[] = '<small class="text-danger">Response overdue (' . $row->first_response_due_at->format('d M, H:i') . ')</small>';
                        } else {
                            $lines[] = '<small>Respond by ' . $row->first_response_due_at->format('d M, H:i') . '</small>';
                        }
                    }

                    if ($row->resolution_due_at) {
                        if ($row->is_resolution_breached) {
                            $lines[] = '<small class="text-danger">Resolution overdue (' . $row->resolution_due_at->format('d M, H:i') . ')</small>';
                        } else {
                            $lines[] = '<small>Resolve by ' . $row->resolution_due_at->format('d M, H:i') . '</small>';
                        }
                    }

                    return implode('<br>', $lines);
                })
                ->addColumn('action', function ($row) {
                    return view('admin.tickets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'subject_col', 'category_name', 'requester_col', 'due_col', 'priority_badge', 'ticket_status_badge', 'sla_col', 'action'])
                ->make(true);
        }

        return view('admin.tickets.index', [
            'categories' => TicketCategory::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tickets.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {
        DB::beginTransaction();

        try {
            $ticket = $this->ticketService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tickets',
                'action' => 'create',
                'model' => 'Ticket',
                'model_id' => $ticket->id,
                'description' => 'Ticket "' . $ticket->subject . ' (' . $ticket->ticket_number . ')" created',
                'new_data' => $ticket->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket created successfully.',
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
    public function edit(Ticket $ticket)
    {
        return view('admin.tickets.edit', array_merge($this->formData(), compact('ticket')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketRequest $request, Ticket $ticket)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticket->toArray();
            $updatedTicket = $this->ticketService->update($ticket, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tickets',
                'action' => 'update',
                'model' => 'Ticket',
                'model_id' => $ticket->id,
                'description' => 'Ticket "' . $updatedTicket->subject . ' (' . $updatedTicket->ticket_number . ')" updated',
                'new_data' => $updatedTicket->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.tickets.index'),
                'message' => 'Ticket updated successfully.',
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
    public function destroy(Ticket $ticket)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticket->toArray();

            $this->ticketService->delete($ticket);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tickets',
                'action' => 'delete',
                'model' => 'Ticket',
                'model_id' => $oldData['id'],
                'description' => 'Ticket "' . $oldData['subject'] . ' (' . $oldData['ticket_number'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket deleted successfully.',
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
     * Stamp the moment this ticket was first actually responded to.
     */
    public function recordFirstResponse(Ticket $ticket)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticket->toArray();
            $updatedTicket = $this->ticketService->recordFirstResponse($ticket);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tickets',
                'action' => 'record-first-response',
                'model' => 'Ticket',
                'model_id' => $ticket->id,
                'description' => 'First response recorded for ticket "' . $updatedTicket->ticket_number . '"',
                'new_data' => $updatedTicket->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'First response recorded.',
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
     * Apply the applicable escalation rule to a breached ticket.
     */
    public function escalate(Ticket $ticket)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticket->toArray();
            $escalation = $this->ticketEscalationService->escalate($ticket);
            $ticket->refresh();

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tickets',
                'action' => 'escalate',
                'model' => 'Ticket',
                'model_id' => $ticket->id,
                'description' => 'Ticket "' . $ticket->ticket_number . '" escalated (rule #' . $escalation->escalation_rule_id . ')',
                'new_data' => $ticket->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket escalated successfully.',
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

        $model = Ticket::find($id);
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
     * Dropdown collections shared by create and edit.
     */
    protected function formData(): array
    {
        return [
            'categories' => TicketCategory::active()->orderBy('name')->get(),
            'customers' => Customer::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'ticketStatuses' => TicketStatus::active()->orderBy('sort_order')->orderBy('name')->get(),
            'ticketPriorities' => TicketPriority::active()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}
