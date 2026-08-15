<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRateRequest;
use App\Models\ChartOfAccount;
use App\Models\TaxRate;
use App\Services\TaxRateService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TaxRateController extends Controller
{
    use ActivityLogger;

    protected $taxRateService;

    public function __construct(TaxRateService $taxRateService)
    {
        $this->taxRateService = $taxRateService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TaxRate::query()->with(['salesAccount', 'purchaseAccount']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by what the tax applies to
            if ($request->applies_to) {
                $query->where('applies_to', $request->applies_to);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.tax-rates.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    $compound = $row->is_compound ? ' <span class="badge bg-secondary">Compound</span>' : '';
                    return '<b class="tl-name-txt">' . e($row->name) . '</b>' . $compound . '<br><small>' . e($row->code) . '</small>';
                })
                ->addColumn('rate_formatted', function ($row) {
                    return rtrim(rtrim(number_format($row->rate, 4), '0'), '.') . '%';
                })
                ->addColumn('tax_type_label', function ($row) {
                    return ucfirst($row->tax_type);
                })
                ->addColumn('applies_to_badge', function ($row) {
                    $map = ['sales' => 'bg-success', 'purchase' => 'bg-info', 'both' => 'bg-primary'];
                    $class = $map[$row->applies_to] ?? 'bg-secondary';
                    return '<span class="badge ' . $class . '">' . ucfirst($row->applies_to) . '</span>';
                })
                ->addColumn('validity', function ($row) {
                    if (! $row->effective_from && ! $row->effective_to) {
                        $period = 'Always';
                    } else {
                        $from = $row->effective_from ? $row->effective_from->format('d M, Y') : 'Any';
                        $to = $row->effective_to ? $row->effective_to->format('d M, Y') : 'Ongoing';
                        $period = $from . ' - ' . $to;
                    }

                    $flag = $row->is_current
                        ? '<span class="badge bg-success">Current</span>'
                        : '<span class="badge bg-secondary">Not in force</span>';

                    return $period . '<br>' . $flag;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.tax-rates.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'applies_to_badge', 'validity', 'action'])
                ->make(true);
        }

        return view('admin.tax-rates.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $chartOfAccounts = $this->postableAccounts();

        return view('admin.tax-rates.create', compact('chartOfAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaxRateRequest $request)
    {
        DB::beginTransaction();

        try {
            $taxRate = $this->taxRateService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tax-rates',
                'action' => 'create',
                'model' => 'TaxRate',
                'model_id' => $taxRate->id,
                'description' => 'Tax Rate "' . $taxRate->name . ' (' . $taxRate->code . ')" created',
                'new_data' => $taxRate->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tax rate created successfully.'
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
    public function edit(TaxRate $taxRate)
    {
        $chartOfAccounts = $this->postableAccounts();

        return view('admin.tax-rates.edit', compact('taxRate', 'chartOfAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaxRateRequest $request, TaxRate $taxRate)
    {
        DB::beginTransaction();

        try {
            $oldData = $taxRate->toArray();
            $updatedTaxRate = $this->taxRateService->update($taxRate, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tax-rates',
                'action' => 'update',
                'model' => 'TaxRate',
                'model_id' => $taxRate->id,
                'description' => 'Tax Rate "' . $updatedTaxRate->name . ' (' . $updatedTaxRate->code . ')" updated',
                'new_data' => $updatedTaxRate->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.tax-rates.index'),
                'message' => 'Tax rate updated successfully.'
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
    public function destroy(TaxRate $taxRate)
    {
        DB::beginTransaction();

        try {
            $oldData = $taxRate->toArray();

            $this->taxRateService->delete($taxRate);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'tax-rates',
                'action' => 'delete',
                'model' => 'TaxRate',
                'model_id' => $oldData['id'],
                'description' => 'Tax Rate "' . $oldData['name'] . ' (' . $oldData['code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tax rate deleted successfully.'
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

        $model = TaxRate::find($id);
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
     * Only postable (non-group) accounts can receive a tax posting — the
     * same "exclude invalid choices from the dropdown entirely" guard
     * used by Journal Entries and Bank Accounts, backing up the Form
     * Request's own group-account rejection.
     */
    protected function postableAccounts()
    {
        return ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();
    }
}
