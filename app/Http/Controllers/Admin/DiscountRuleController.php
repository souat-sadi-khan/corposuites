<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscountRuleRequest;
use App\Models\Category;
use App\Models\DiscountRule;
use App\Models\Product;
use App\Services\DiscountRuleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DiscountRuleController extends Controller
{
    use ActivityLogger;

    protected $discountRuleService;

    public function __construct(DiscountRuleService $discountRuleService)
    {
        $this->discountRuleService = $discountRuleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DiscountRule::query()->with(['category', 'product']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by scope
            if ($request->scope_type) {
                $query->where('scope_type', $request->scope_type);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.discount-rules.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    $value = $row->discount_type === 'percentage' ? $row->value . '%' : number_format($row->value, 2);
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $value . ' off</small>';
                })
                ->addColumn('scope_label', function ($row) {
                    return match ($row->scope_type) {
                        'category' => 'Category: ' . ($row->category->name ?? '-'),
                        'product' => 'Product: ' . ($row->product->name ?? '-'),
                        default => 'All Products',
                    };
                })
                ->addColumn('validity', function ($row) {
                    if (!$row->start_date && !$row->end_date) {
                        return 'Always';
                    }
                    return ($row->start_date?->format('d M, Y') ?? '-') . ' to ' . ($row->end_date?->format('d M, Y') ?? '-');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.discount-rules.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.discount-rules.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::active()->get();
        $products = Product::active()->get();

        return view('admin.discount-rules.create', compact('categories', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiscountRuleRequest $request)
    {
        DB::beginTransaction();

        try {
            $discountRule = $this->discountRuleService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'discount-rules',
                'action' => 'create',
                'model' => 'DiscountRule',
                'model_id' => $discountRule->id,
                'description' => 'Discount Rule "' . $discountRule->name . '" created',
                'new_data' => $discountRule->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Discount rule created successfully.'
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
    public function edit(DiscountRule $discountRule)
    {
        $categories = Category::active()->get();
        $products = Product::active()->get();

        return view('admin.discount-rules.edit', compact('discountRule', 'categories', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiscountRuleRequest $request, DiscountRule $discountRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $discountRule->toArray();
            $updatedDiscountRule = $this->discountRuleService->update($discountRule, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'discount-rules',
                'action' => 'update',
                'model' => 'DiscountRule',
                'model_id' => $discountRule->id,
                'description' => 'Discount Rule "' . $discountRule->name . '" updated',
                'new_data' => $updatedDiscountRule->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.discount-rules.index'),
                'message' => 'Discount rule updated successfully.'
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
    public function destroy(DiscountRule $discountRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $discountRule->toArray();

            $this->discountRuleService->delete($discountRule);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'discount-rules',
                'action' => 'delete',
                'model' => 'DiscountRule',
                'model_id' => $oldData['id'],
                'description' => 'Discount Rule "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Discount rule deleted successfully.'
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

        $model = DiscountRule::find($id);
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
