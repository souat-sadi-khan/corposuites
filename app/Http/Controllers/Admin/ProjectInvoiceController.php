<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectInvoiceRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectInvoice;
use App\Models\ProjectTimeEntry;
use App\Services\ProjectInvoiceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectInvoiceController extends Controller
{
    use ActivityLogger;

    protected $projectInvoiceService;

    public function __construct(ProjectInvoiceService $projectInvoiceService)
    {
        $this->projectInvoiceService = $projectInvoiceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectInvoice::with('project.client')->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->invoice_status) {
                $query->where('invoice_status', $request->invoice_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('invoice_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-invoices.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('invoice_number_col', function ($row) {
                    if (! $row->project) {
                        return '<b class="tl-name-txt">' . e($row->invoice_number) . '</b><br><span class="text-danger">Project removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->invoice_number) . '</b><br><small>' . e($row->project->name)
                        . ' (' . e($row->project->project_code) . ')'
                        . ($row->project->client ? ' · ' . e($row->project->client->name) : '') . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' line' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('invoice_date_formatted', function ($row) {
                    return $row->invoice_date->format('d M Y');
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('balance_due_formatted', function ($row) {
                    $line = number_format($row->balance_due, 2);

                    if ($row->is_overdue) {
                        $line = '<span class="text-danger">' . $line . ' (Overdue)</span>';
                    }

                    return $line;
                })
                ->addColumn('invoice_status_badge', function ($row) {
                    $map = [
                        'draft' => 'bg-secondary',
                        'sent' => 'bg-info',
                        'partially_paid' => 'bg-warning',
                        'paid' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->invoice_status] ?? 'bg-secondary') . '">' . e($row->invoice_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-invoices.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'invoice_number_col', 'balance_due_formatted', 'invoice_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-invoices.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-invoices.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectInvoiceRequest $request)
    {
        DB::beginTransaction();

        try {
            $invoice = $this->projectInvoiceService->create($request->validated());
            $invoice->load(['project', 'items']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'create',
                'model' => 'ProjectInvoice',
                'model_id' => $invoice->id,
                'description' => 'Invoice "' . $this->invoiceLabel($invoice) . '" created',
                'new_data' => $invoice->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Invoice created successfully.'
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
    public function edit(ProjectInvoice $projectInvoice)
    {
        $projectInvoice->load('items');

        return view('admin.project-invoices.edit', array_merge($this->formData($projectInvoice), [
            'projectInvoice' => $projectInvoice,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectInvoiceRequest $request, ProjectInvoice $projectInvoice)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectInvoice->load('items')->toArray();
            $updated = $this->projectInvoiceService->update($projectInvoice, $request->validated());
            $updated->load(['project', 'items']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'update',
                'model' => 'ProjectInvoice',
                'model_id' => $projectInvoice->id,
                'description' => 'Invoice "' . $this->invoiceLabel($updated) . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-invoices.index'),
                'message' => 'Invoice updated successfully.'
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
    public function destroy(ProjectInvoice $projectInvoice)
    {
        DB::beginTransaction();

        try {
            $projectInvoice->load(['project', 'items']);
            $oldData = $projectInvoice->toArray();
            $label = $this->invoiceLabel($projectInvoice);

            $this->projectInvoiceService->delete($projectInvoice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'delete',
                'model' => 'ProjectInvoice',
                'model_id' => $oldData['id'],
                'description' => 'Invoice "' . $label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Invoice deleted successfully.'
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

        $model = ProjectInvoice::find($id);
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

    public function markSent(ProjectInvoice $projectInvoice)
    {
        try {
            $invoice = $this->projectInvoiceService->markSent($projectInvoice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'mark-sent',
                'model' => 'ProjectInvoice',
                'model_id' => $invoice->id,
                'description' => 'Invoice "' . $this->invoiceLabel($invoice) . '" marked sent',
                'new_data' => $invoice->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Invoice marked as sent.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function recordPayment(Request $request, ProjectInvoice $projectInvoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $invoice = $this->projectInvoiceService->recordPayment($projectInvoice, (float) $request->input('amount'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'record-payment',
                'model' => 'ProjectInvoice',
                'model_id' => $invoice->id,
                'description' => number_format($request->input('amount'), 2) . ' recorded against invoice "' . $this->invoiceLabel($invoice) . '"',
                'new_data' => $invoice->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment recorded — balance now ' . number_format($invoice->balance_due, 2) . '.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function cancel(ProjectInvoice $projectInvoice)
    {
        try {
            $invoice = $this->projectInvoiceService->cancel($projectInvoice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-invoices',
                'action' => 'cancel',
                'model' => 'ProjectInvoice',
                'model_id' => $invoice->id,
                'description' => 'Invoice "' . $this->invoiceLabel($invoice) . '" cancelled',
                'new_data' => $invoice->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Invoice cancelled — its billed entries and expenses are billable again.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    protected function invoiceLabel(ProjectInvoice $invoice): string
    {
        return $invoice->invoice_number . ' (' . ($invoice->project->project_code ?? 'unknown project') . ')';
    }

    /**
     * Dropdown/picker collections shared by index, create and edit.
     *
     * Time entries and expenses both carry their project id so the form can
     * narrow the picker to the header's selected project client-side.
     * Excludes anything already billed on another still-active invoice; on
     * edit, this invoice's own currently-linked sources are included too
     * (via orWhere), the same "don't lose the current selection" precedent
     * PaymentReceiveController/BankReconciliationController already use.
     */
    protected function formData(?ProjectInvoice $projectInvoice = null): array
    {
        $existingTimeEntryIds = $projectInvoice
            ? $projectInvoice->items->pluck('project_time_entry_id')->filter()->all()
            : [];

        $existingExpenseIds = $projectInvoice
            ? $projectInvoice->items->pluck('project_expense_id')->filter()->all()
            : [];

        $pickableTimeEntries = ProjectTimeEntry::active()
            ->where('is_billable', true)
            ->where(fn ($q) => $q->whereNull('started_at')->orWhereNotNull('ended_at'))
            ->where(function ($q) use ($existingTimeEntryIds) {
                $q->whereDoesntHave('invoiceItem', fn ($q2) => $q2->whereHas('projectInvoice', fn ($q3) => $q3->where('invoice_status', '!=', 'cancelled')));

                if (!empty($existingTimeEntryIds)) {
                    $q->orWhereIn('id', $existingTimeEntryIds);
                }
            })
            ->orderBy('work_date')
            ->get();

        $pickableExpenses = ProjectExpense::billableApproved()
            ->where(function ($q) use ($existingExpenseIds) {
                $q->whereDoesntHave('invoiceItem', fn ($q2) => $q2->whereHas('projectInvoice', fn ($q3) => $q3->where('invoice_status', '!=', 'cancelled')));

                if (!empty($existingExpenseIds)) {
                    $q->orWhereIn('id', $existingExpenseIds);
                }
            })
            ->orderBy('expense_date')
            ->get();

        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'pickableTimeEntries' => $pickableTimeEntries,
            'pickableExpenses' => $pickableExpenses,
        ];
    }
}
