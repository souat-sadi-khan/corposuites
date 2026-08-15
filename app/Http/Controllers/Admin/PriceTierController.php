<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceTierRequest;
use App\Models\PriceTier;
use App\Services\PriceTierService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PriceTierController extends Controller
{
    use ActivityLogger;

    protected $priceTierService;

    public function __construct(PriceTierService $priceTierService)
    {
        $this->priceTierService = $priceTierService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PriceTier::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.price-tiers.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.price-tiers.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.price-tiers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.price-tiers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PriceTierRequest $request)
    {
        DB::beginTransaction();

        try {
            $priceTier = $this->priceTierService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-tiers',
                'action' => 'create',
                'model' => 'PriceTier',
                'model_id' => $priceTier->id,
                'description' => 'Price Tier "' . $priceTier->name . '" created',
                'new_data' => $priceTier->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Price tier created successfully.'
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
    public function edit(PriceTier $priceTier)
    {
        return view('admin.price-tiers.edit', compact('priceTier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PriceTierRequest $request, PriceTier $priceTier)
    {
        DB::beginTransaction();

        try {
            $oldData = $priceTier->toArray();
            $updatedPriceTier = $this->priceTierService->update($priceTier, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-tiers',
                'action' => 'update',
                'model' => 'PriceTier',
                'model_id' => $priceTier->id,
                'description' => 'Price Tier "' . $priceTier->name . '" updated',
                'new_data' => $updatedPriceTier->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.price-tiers.index'),
                'message' => 'Price tier updated successfully.'
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
    public function destroy(PriceTier $priceTier)
    {
        DB::beginTransaction();

        try {
            $oldData = $priceTier->toArray();

            $this->priceTierService->delete($priceTier);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-tiers',
                'action' => 'delete',
                'model' => 'PriceTier',
                'model_id' => $oldData['id'],
                'description' => 'Price Tier "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Price tier deleted successfully.'
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

        $model = PriceTier::find($id);
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
