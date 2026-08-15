<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    use ActivityLogger;

    protected $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = JournalEntry::query()->with('createdBy')->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by entry status
            if ($request->entry_status) {
                $query->where('entry_status', $request->entry_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('entry_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.journal-entries.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('entry_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->entry_number . '</b><br><small>' . ($row->createdBy->name ?? 'System') . '</small>';
                })
                ->addColumn('entry_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->entry_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->entry_status) . '</span>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' line' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('entry_date_formatted', function ($row) {
                    return $row->entry_date ? $row->entry_date->format('d M, Y') : '-';
                })
                ->addColumn('total_debit_formatted', function ($row) {
                    return number_format($row->total_debit, 2);
                })
                ->addColumn('action', function ($row) {
                    return view('admin.journal-entries.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'entry_number', 'entry_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.journal-entries.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $chartOfAccounts = ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();

        return view('admin.journal-entries.create', compact('chartOfAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JournalEntryRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = auth()->guard('admin')->id();

            $journalEntry = $this->journalEntryService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'journal-entries',
                'action' => 'create',
                'model' => 'JournalEntry',
                'model_id' => $journalEntry->id,
                'description' => 'Journal Entry "' . $journalEntry->entry_number . '" created',
                'new_data' => $journalEntry->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Journal entry created successfully.'
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
    public function edit(JournalEntry $journalEntry)
    {
        $chartOfAccounts = ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();
        $journalEntry->load('items');

        return view('admin.journal-entries.edit', compact('journalEntry', 'chartOfAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(JournalEntryRequest $request, JournalEntry $journalEntry)
    {
        DB::beginTransaction();

        try {
            $oldData = $journalEntry->load('items')->toArray();
            $updatedJournalEntry = $this->journalEntryService->update($journalEntry, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'journal-entries',
                'action' => 'update',
                'model' => 'JournalEntry',
                'model_id' => $journalEntry->id,
                'description' => 'Journal Entry "' . $journalEntry->entry_number . '" updated',
                'new_data' => $updatedJournalEntry->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.journal-entries.index'),
                'message' => 'Journal entry updated successfully.'
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
    public function destroy(JournalEntry $journalEntry)
    {
        DB::beginTransaction();

        try {
            $oldData = $journalEntry->load('items')->toArray();

            $this->journalEntryService->delete($journalEntry);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'journal-entries',
                'action' => 'delete',
                'model' => 'JournalEntry',
                'model_id' => $oldData['id'],
                'description' => 'Journal Entry "' . $oldData['entry_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Journal entry deleted successfully.'
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

        $model = JournalEntry::find($id);
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
