<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitConversionRequest;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\UnitConversionService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UnitConversionController extends Controller
{
    use ActivityLogger;

    protected $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = UnitConversion::query()->with(['fromUnit', 'toUnit']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('fromUnit', function ($fq) use ($search) {
                        $fq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('toUnit', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.unit-conversions.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('conversion', function ($row) {
                    return '<b class="tl-name-txt">1 ' . ($row->fromUnit->short_code ?? '-') . ' = ' . rtrim(rtrim(number_format($row->conversion_factor, 6), '0'), '.') . ' ' . ($row->toUnit->short_code ?? '-') . '</b><br><small>' . ($row->fromUnit->name ?? '-') . ' to ' . ($row->toUnit->name ?? '-') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.unit-conversions.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'conversion', 'action'])
                ->make(true);
        }

        return view('admin.unit-conversions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::active()->get();

        return view('admin.unit-conversions.create', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitConversionRequest $request)
    {
        DB::beginTransaction();

        try {
            $unitConversion = $this->unitConversionService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'unit-conversions',
                'action' => 'create',
                'model' => 'UnitConversion',
                'model_id' => $unitConversion->id,
                'description' => 'Unit Conversion #' . $unitConversion->id . ' created',
                'new_data' => $unitConversion->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Unit conversion created successfully.'
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
    public function edit(UnitConversion $unitConversion)
    {
        $units = Unit::active()->get();

        return view('admin.unit-conversions.edit', compact('unitConversion', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitConversionRequest $request, UnitConversion $unitConversion)
    {
        DB::beginTransaction();

        try {
            $oldData = $unitConversion->toArray();
            $updatedUnitConversion = $this->unitConversionService->update($unitConversion, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'unit-conversions',
                'action' => 'update',
                'model' => 'UnitConversion',
                'model_id' => $unitConversion->id,
                'description' => 'Unit Conversion #' . $unitConversion->id . ' updated',
                'new_data' => $updatedUnitConversion->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.unit-conversions.index'),
                'message' => 'Unit conversion updated successfully.'
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
    public function destroy(UnitConversion $unitConversion)
    {
        DB::beginTransaction();

        try {
            $oldData = $unitConversion->toArray();

            $this->unitConversionService->delete($unitConversion);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'unit-conversions',
                'action' => 'delete',
                'model' => 'UnitConversion',
                'model_id' => $oldData['id'],
                'description' => 'Unit Conversion #' . $oldData['id'] . ' deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Unit conversion deleted successfully.'
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

        $model = UnitConversion::find($id);
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
