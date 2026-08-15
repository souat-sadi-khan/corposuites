<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketStatusRequest;
use App\Models\TicketStatus;
use App\Services\TicketStatusService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TicketStatusController extends Controller
{
    use ActivityLogger;

    protected $ticketStatusService;

    public function __construct(TicketStatusService $ticketStatusService)
    {
        $this->ticketStatusService = $ticketStatusService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TicketStatus::withCount('tickets');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by which fixed bucket this custom status maps to
            if ($request->maps_to) {
                $query->where('maps_to', $request->maps_to);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('sort_order')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.ticket-statuses.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    $swatch = $row->color
                        ? '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' . e($row->color) . ';margin-right:6px;"></span>'
                        : '';

                    return $swatch . '<b class="tl-name-txt">' . e($row->name) . '</b>';
                })
                ->addColumn('maps_to_badge', function ($row) {
                    $colors = [
                        'open' => 'primary',
                        'in_progress' => 'info',
                        'on_hold' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'secondary',
                    ];
                    $color = $colors[$row->maps_to] ?? 'secondary';

                    return '<span class="badge bg-' . $color . '">' . e($row->maps_to_label) . '</span>'
                        . ($row->is_terminal ? '<br><small class="text-muted">Terminal</small>' : '');
                })
                ->addColumn('tickets_count_label', function ($row) {
                    return (int) $row->tickets_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.ticket-statuses.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'maps_to_badge', 'action'])
                ->make(true);
        }

        return view('admin.ticket-statuses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ticket-statuses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketStatusRequest $request)
    {
        DB::beginTransaction();

        try {
            $ticketStatus = $this->ticketStatusService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-statuses',
                'action' => 'create',
                'model' => 'TicketStatus',
                'model_id' => $ticketStatus->id,
                'description' => 'Ticket Status "' . $ticketStatus->name . '" created',
                'new_data' => $ticketStatus->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket status created successfully.',
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
    public function edit(TicketStatus $ticketStatus)
    {
        return view('admin.ticket-statuses.edit', compact('ticketStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketStatusRequest $request, TicketStatus $ticketStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketStatus->toArray();
            $updatedTicketStatus = $this->ticketStatusService->update($ticketStatus, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-statuses',
                'action' => 'update',
                'model' => 'TicketStatus',
                'model_id' => $ticketStatus->id,
                'description' => 'Ticket Status "' . $updatedTicketStatus->name . '" updated',
                'new_data' => $updatedTicketStatus->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.ticket-statuses.index'),
                'message' => 'Ticket status updated successfully.',
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
    public function destroy(TicketStatus $ticketStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketStatus->toArray();

            $this->ticketStatusService->delete($ticketStatus);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-statuses',
                'action' => 'delete',
                'model' => 'TicketStatus',
                'model_id' => $oldData['id'],
                'description' => 'Ticket Status "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket status deleted successfully.',
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

        $model = TicketStatus::find($id);
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
}
