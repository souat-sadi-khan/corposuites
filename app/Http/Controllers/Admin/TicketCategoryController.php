<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketCategoryRequest;
use App\Models\TicketCategory;
use App\Services\TicketCategoryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TicketCategoryController extends Controller
{
    use ActivityLogger;

    protected $ticketCategoryService;

    public function __construct(TicketCategoryService $ticketCategoryService)
    {
        $this->ticketCategoryService = $ticketCategoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TicketCategory::query();

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.ticket-categories.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.ticket-categories.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('admin.ticket-categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ticket-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $ticketCategory = $this->ticketCategoryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-categories',
                'action' => 'create',
                'model' => 'TicketCategory',
                'model_id' => $ticketCategory->id,
                'description' => 'Ticket Category "' . $ticketCategory->name . '" created',
                'new_data' => $ticketCategory->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket category created successfully.',
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
    public function edit(TicketCategory $ticketCategory)
    {
        return view('admin.ticket-categories.edit', compact('ticketCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketCategoryRequest $request, TicketCategory $ticketCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketCategory->toArray();
            $updatedTicketCategory = $this->ticketCategoryService->update($ticketCategory, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-categories',
                'action' => 'update',
                'model' => 'TicketCategory',
                'model_id' => $ticketCategory->id,
                'description' => 'Ticket Category "' . $updatedTicketCategory->name . '" updated',
                'new_data' => $updatedTicketCategory->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.ticket-categories.index'),
                'message' => 'Ticket category updated successfully.',
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
    public function destroy(TicketCategory $ticketCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $ticketCategory->toArray();

            $this->ticketCategoryService->delete($ticketCategory);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'ticket-categories',
                'action' => 'delete',
                'model' => 'TicketCategory',
                'model_id' => $oldData['id'],
                'description' => 'Ticket Category "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Ticket category deleted successfully.',
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

        $model = TicketCategory::find($id);
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
