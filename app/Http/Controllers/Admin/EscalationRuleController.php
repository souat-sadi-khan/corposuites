<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EscalationRuleRequest;
use App\Models\Admin;
use App\Models\EscalationRule;
use App\Services\EscalationRuleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EscalationRuleController extends Controller
{
    use ActivityLogger;

    protected $escalationRuleService;

    public function __construct(EscalationRuleService $escalationRuleService)
    {
        $this->escalationRuleService = $escalationRuleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EscalationRule::with('escalateToAdmin')->withCount('ticketEscalations');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($request->trigger) {
                $query->where('trigger', $request->trigger);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")->orderBy('trigger');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.escalation-rules.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b>';
                })
                ->addColumn('trigger_col', function ($row) {
                    $map = [
                        'low' => 'bg-secondary',
                        'medium' => 'bg-info',
                        'high' => 'bg-warning',
                        'urgent' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->priority] ?? 'bg-secondary') . '">' . e($row->priority_label) . '</span>'
                        . '<br><small>' . e($row->trigger_label) . '</small>';
                })
                ->addColumn('action_col', function ($row) {
                    $lines = [];

                    if ($row->escalate_to_admin_id) {
                        $lines[] = 'Reassign to ' . e($row->escalateToAdmin->name ?? '—');
                    }

                    if ($row->escalate_priority_to) {
                        $lines[] = 'Bump priority to ' . ucfirst($row->escalate_priority_to);
                    }

                    return $lines ? implode('<br>', $lines) : '<span class="text-muted">No action set</span>';
                })
                ->addColumn('escalations_count_label', function ($row) {
                    return (int) $row->ticket_escalations_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.escalation-rules.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'trigger_col', 'action_col', 'action'])
                ->make(true);
        }

        return view('admin.escalation-rules.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.escalation-rules.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EscalationRuleRequest $request)
    {
        DB::beginTransaction();

        try {
            $escalationRule = $this->escalationRuleService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'escalation-rules',
                'action' => 'create',
                'model' => 'EscalationRule',
                'model_id' => $escalationRule->id,
                'description' => 'Escalation Rule "' . $escalationRule->name . '" created',
                'new_data' => $escalationRule->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Escalation rule created successfully.',
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
    public function edit(EscalationRule $escalationRule)
    {
        return view('admin.escalation-rules.edit', array_merge($this->formData(), compact('escalationRule')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EscalationRuleRequest $request, EscalationRule $escalationRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $escalationRule->toArray();
            $updatedEscalationRule = $this->escalationRuleService->update($escalationRule, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'escalation-rules',
                'action' => 'update',
                'model' => 'EscalationRule',
                'model_id' => $escalationRule->id,
                'description' => 'Escalation Rule "' . $updatedEscalationRule->name . '" updated',
                'new_data' => $updatedEscalationRule->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.escalation-rules.index'),
                'message' => 'Escalation rule updated successfully.',
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
    public function destroy(EscalationRule $escalationRule)
    {
        DB::beginTransaction();

        try {
            $oldData = $escalationRule->toArray();

            $this->escalationRuleService->delete($escalationRule);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'escalation-rules',
                'action' => 'delete',
                'model' => 'EscalationRule',
                'model_id' => $oldData['id'],
                'description' => 'Escalation Rule "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Escalation rule deleted successfully.',
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

        $model = EscalationRule::find($id);
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
            'admins' => Admin::orderBy('name')->get(),
        ];
    }
}
