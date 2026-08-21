<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MinimumWageRuleRequest;
use App\Models\MinimumWageRule;
use App\Services\MinimumWageRuleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MinimumWageRuleController extends Controller
{
    use ActivityLogger;

    protected $minimumWageRuleService;

    public function __construct(MinimumWageRuleService $minimumWageRuleService)
    {
        $this->minimumWageRuleService = $minimumWageRuleService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MinimumWageRule::query();

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->wage_type) {
                $query->where('wage_type', $request->wage_type);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%");
                });
            }

            $query->orderByDesc('effective_date')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.minimum-wage-rules.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('scope_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->scope_label) . '</b>';
                })
                ->addColumn('wage_type_label', function ($row) {
                    return $row->wage_type_label;
                })
                ->addColumn('minimum_wage_formatted', function ($row) {
                    return number_format($row->minimum_wage, 2) . ' / ' . ($row->wage_type === 'daily' ? 'day' : 'month');
                })
                ->addColumn('effective_date_formatted', function ($row) {
                    return $row->effective_date?->format('d M, Y');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.minimum-wage-rules.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'scope_col', 'action'])
                ->make(true);
        }

        return view('admin.minimum-wage-rules.index');
    }

    public function create()
    {
        return view('admin.minimum-wage-rules.create');
    }

    public function store(MinimumWageRuleRequest $request)
    {
        DB::beginTransaction();

        try {
            $rule = $this->minimumWageRuleService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'minimum-wage-rules',
                'action' => 'create',
                'model' => 'MinimumWageRule',
                'model_id' => $rule->id,
                'description' => 'Minimum wage rule for "' . $rule->scope_label . '" (' . $rule->wage_type_label . ') created',
                'new_data' => $rule->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Minimum wage rule created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit(MinimumWageRule $minimumWageRule)
    {
        return view('admin.minimum-wage-rules.edit', compact('minimumWageRule'));
    }

    public function update(MinimumWageRuleRequest $request, MinimumWageRule $minimumWageRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $minimumWageRule->toArray();
            $updated = $this->minimumWageRuleService->update($minimumWageRule, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'minimum-wage-rules',
                'action' => 'update',
                'model' => 'MinimumWageRule',
                'model_id' => $minimumWageRule->id,
                'description' => 'Minimum wage rule for "' . $updated->scope_label . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.minimum-wage-rules.index'),
                'message' => 'Minimum wage rule updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(MinimumWageRule $minimumWageRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $minimumWageRule->toArray();

            $this->minimumWageRuleService->delete($minimumWageRule);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'minimum-wage-rules',
                'action' => 'delete',
                'model' => 'MinimumWageRule',
                'model_id' => $oldData['id'],
                'description' => 'Minimum wage rule for "' . $oldData['country'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Minimum wage rule deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = MinimumWageRule::find($id);
        if (!$model) {
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
