<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketPriorityRequest;
use App\Models\TicketPriority;
use App\Services\TicketPriorityService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TicketPriorityController extends Controller
{
    use ActivityLogger;

    protected $ticketPriorityService;

    public function __construct(TicketPriorityService $ticketPriorityService)
    {
        $this->ticketPriorityService = $ticketPriorityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TicketPriority::withCount('tickets');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by which fixed bucket this custom priority maps to
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.ticket-priorities.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    $swatch = $row->color
                        ? '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' . e($row->color) . ';margin-right:6px;"></span>'
                        : '';

                    return $swatch . '<b class="tl-name-txt">' . e($row->name) . '</b>';
                })
                ->addColumn('maps_to_badge', function ($row) {
                    $colors = [
                        'low' => 'secondary',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                    ];
                    $color = $colors[$row->maps_to] ?? 'secondary';

                    return '<span class="badge bg-' . $color . '">' . e($row->maps_to_label) . '</span>';
                })
                ->addColumn('tickets_count_label', function ($row) {
                    return (int) $row->tickets_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.ticket-priorities.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'maps_to_badge', 'action'])
                ->make(true);
        }

        return view('admin.ticket-priorities.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ticket-priorities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketPriorityRequest $request)
    {
        DB::beginTransaction();

        try {
            $ticketPriority = $this->ticketPriorityService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-priorities',
                'action' => 'create',
                'model' => 'TicketPriority',
                'model_id' => $ticketPriority->id,
                'description' => 'Ticket Priority "' . $ticketPriority->name . '" created',
                'new_data' => $ticketPriority->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket priority created successfully.',
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
    public function edit(TicketPriority $ticketPriority)
    {
        return view('admin.ticket-priorities.edit', compact('ticketPriority'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketPriorityRequest $request, TicketPriority $ticketPriority)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketPriority->toArray();
            $updatedTicketPriority = $this->ticketPriorityService->update($ticketPriority, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-priorities',
                'action' => 'update',
                'model' => 'TicketPriority',
                'model_id' => $ticketPriority->id,
                'description' => 'Ticket Priority "' . $updatedTicketPriority->name . '" updated',
                'new_data' => $updatedTicketPriority->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.ticket-priorities.index'),
                'message' => 'Ticket priority updated successfully.',
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
    public function destroy(TicketPriority $ticketPriority)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketPriority->toArray();

            $this->ticketPriorityService->delete($ticketPriority);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-priorities',
                'action' => 'delete',
                'model' => 'TicketPriority',
                'model_id' => $oldData['id'],
                'description' => 'Ticket Priority "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket priority deleted successfully.',
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

        $model = TicketPriority::find($id);
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
