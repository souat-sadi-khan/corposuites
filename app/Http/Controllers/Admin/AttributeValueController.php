<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeValueRequest;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Services\AttributeValueService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AttributeValueController extends Controller
{
    use ActivityLogger;

    protected $attributeValueService;

    public function __construct(AttributeValueService $attributeValueService)
    {
        $this->attributeValueService = $attributeValueService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AttributeValue::query()->with('productAttribute');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by attribute
            if ($request->product_attribute_id) {
                $query->where('product_attribute_id', $request->product_attribute_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('value', 'like', "%{$search}%")
                      ->orWhereHas('productAttribute', function ($aq) use ($search) {
                          $aq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.attribute-values.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('value', function ($row) {
                    return '<b class="tl-name-txt">' . $row->value . '</b><br><small>' . ($row->productAttribute->name ?? 'No Attribute') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.attribute-values.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'value', 'action'])
                ->make(true);
        }

        $productAttributes = ProductAttribute::active()->get();

        return view('admin.attribute-values.index', compact('productAttributes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productAttributes = ProductAttribute::active()->get();

        return view('admin.attribute-values.create', compact('productAttributes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttributeValueRequest $request)
    {
        DB::beginTransaction();

        try {
            $attributeValue = $this->attributeValueService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attribute-values',
                'action' => 'create',
                'model' => 'AttributeValue',
                'model_id' => $attributeValue->id,
                'description' => 'Attribute Value "' . $attributeValue->value . '" created',
                'new_data' => $attributeValue->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attribute value created successfully.'
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
    public function edit(AttributeValue $attributeValue)
    {
        $productAttributes = ProductAttribute::active()->get();

        return view('admin.attribute-values.edit', compact('attributeValue', 'productAttributes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttributeValueRequest $request, AttributeValue $attributeValue)
    {
        DB::beginTransaction();

        try {
            $oldData = $attributeValue->toArray();
            $updatedAttributeValue = $this->attributeValueService->update($attributeValue, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attribute-values',
                'action' => 'update',
                'model' => 'AttributeValue',
                'model_id' => $attributeValue->id,
                'description' => 'Attribute Value "' . $attributeValue->value . '" updated',
                'new_data' => $updatedAttributeValue->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.attribute-values.index'),
                'message' => 'Attribute value updated successfully.'
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
    public function destroy(AttributeValue $attributeValue)
    {
        DB::beginTransaction();

        try {
            $oldData = $attributeValue->toArray();

            $this->attributeValueService->delete($attributeValue);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attribute-values',
                'action' => 'delete',
                'model' => 'AttributeValue',
                'model_id' => $oldData['id'],
                'description' => 'Attribute Value "' . $oldData['value'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attribute value deleted successfully.'
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

        $model = AttributeValue::find($id);
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
