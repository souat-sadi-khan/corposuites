<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryNoteRequest;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Services\DeliveryNoteService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DeliveryNoteController extends Controller
{
    use ActivityLogger;

    protected $deliveryNoteService;

    public function __construct(DeliveryNoteService $deliveryNoteService)
    {
        $this->deliveryNoteService = $deliveryNoteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DeliveryNote::query()->with(['delivery.salesOrder.customer']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('note_number', 'like', "%{$search}%")
                        ->orWhere('received_by', 'like', "%{$search}%")
                        ->orWhereHas('delivery', function ($dq) use ($search) {
                            $dq->where('delivery_number', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.delivery-notes.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('note_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->note_number . '</b><br><small>' . ($row->delivery->delivery_number ?? '-') . '</small>';
                })
                ->addColumn('received_label', function ($row) {
                    if ($row->received_by) {
                        return $row->received_by . ($row->received_date ? ' on ' . $row->received_date->format('d M, Y') : '');
                    }
                    return '<span class="text-muted">Not received yet</span>';
                })
                ->addColumn('issued_date_formatted', function ($row) {
                    return $row->issued_date ? $row->issued_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.delivery-notes.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'note_number', 'received_label', 'action'])
                ->make(true);
        }

        return view('admin.delivery-notes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $deliveries = Delivery::active()->whereDoesntHave('deliveryNote')->get();

        return view('admin.delivery-notes.create', compact('deliveries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeliveryNoteRequest $request)
    {
        DB::beginTransaction();

        try {
            $deliveryNote = $this->deliveryNoteService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'delivery-notes',
                'action' => 'create',
                'model' => 'DeliveryNote',
                'model_id' => $deliveryNote->id,
                'description' => 'Delivery Note "' . $deliveryNote->note_number . '" created',
                'new_data' => $deliveryNote->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Delivery note created successfully.'
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
    public function edit(DeliveryNote $deliveryNote)
    {
        $deliveries = Delivery::active()
            ->where(function ($q) use ($deliveryNote) {
                $q->whereDoesntHave('deliveryNote')->orWhere('id', $deliveryNote->delivery_id);
            })
            ->get();

        return view('admin.delivery-notes.edit', compact('deliveryNote', 'deliveries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeliveryNoteRequest $request, DeliveryNote $deliveryNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $deliveryNote->toArray();
            $updatedDeliveryNote = $this->deliveryNoteService->update($deliveryNote, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'delivery-notes',
                'action' => 'update',
                'model' => 'DeliveryNote',
                'model_id' => $deliveryNote->id,
                'description' => 'Delivery Note "' . $deliveryNote->note_number . '" updated',
                'new_data' => $updatedDeliveryNote->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.delivery-notes.index'),
                'message' => 'Delivery note updated successfully.'
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
    public function destroy(DeliveryNote $deliveryNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $deliveryNote->toArray();

            $this->deliveryNoteService->delete($deliveryNote);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'delivery-notes',
                'action' => 'delete',
                'model' => 'DeliveryNote',
                'model_id' => $oldData['id'],
                'description' => 'Delivery Note "' . $oldData['note_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Delivery note deleted successfully.'
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

        $model = DeliveryNote::find($id);
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

    /**
     * Printable delivery note document (packing slip), opened in a new tab.
     */
    public function print(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load('delivery.salesOrder.customer', 'delivery.items.product');

        return view('admin.delivery-notes.print', compact('deliveryNote'));
    }
}
