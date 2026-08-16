<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmergencyContactRequest;
use App\Models\EmergencyContact;
use App\Models\Employee;
use App\Services\EmergencyContactService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmergencyContactController extends Controller
{
    use ActivityLogger;

    protected $emergencyContactService;

    public function __construct(EmergencyContactService $emergencyContactService)
    {
        $this->emergencyContactService = $emergencyContactService;
    }

    /**
     * Display a modal for how to use the employee documents.
     */
    public function howTo()
    {
        return view('admin.emergency-contacts.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmergencyContact::query()->with('employee');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.emergency-contacts.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    $primary = $row->is_primary ? ' <span class="badge bg-success-subtle text-success">Primary</span>' : '';
                    return '<b class="tl-name-txt">' . $row->name . '</b>' . $primary . '<br><small>Relation: ' . $row->relationship . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    $avatar = Images::show($row->employee->photo);

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code) . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('contact', function ($row) {
                    return $row->phone . ($row->alternate_phone ? '<br><small>' . $row->alternate_phone . '</small>' : '');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.emergency-contacts.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'employee_name', 'contact', 'action'])
                ->make(true);
        }

        return view('admin.emergency-contacts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.emergency-contacts.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmergencyContactRequest $request)
    {
        DB::beginTransaction();

        try {
            $emergencyContact = $this->emergencyContactService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'emergency-contacts',
                'action' => 'create',
                'model' => 'EmergencyContact',
                'model_id' => $emergencyContact->id,
                'description' => 'Emergency Contact "' . $emergencyContact->name . '" created',
                'new_data' => $emergencyContact->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Emergency contact created successfully.'
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
    public function edit(EmergencyContact $emergencyContact)
    {
        $employees = Employee::active()->get();

        return view('admin.emergency-contacts.edit', compact('emergencyContact', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmergencyContactRequest $request, EmergencyContact $emergencyContact)
    {
        DB::beginTransaction();

        try {
            $oldData = $emergencyContact->toArray();
            $updatedEmergencyContact = $this->emergencyContactService->update($emergencyContact, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'emergency-contacts',
                'action' => 'update',
                'model' => 'EmergencyContact',
                'model_id' => $emergencyContact->id,
                'description' => 'Emergency Contact "' . $emergencyContact->name . '" updated',
                'new_data' => $updatedEmergencyContact->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.emergency-contacts.index'),
                'message' => 'Emergency contact updated successfully.'
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
    public function destroy(EmergencyContact $emergencyContact)
    {
        DB::beginTransaction();

        try {
            $oldData = $emergencyContact->toArray();

            $this->emergencyContactService->delete($emergencyContact);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'emergency-contacts',
                'action' => 'delete',
                'model' => 'EmergencyContact',
                'model_id' => $oldData['id'],
                'description' => 'Emergency Contact "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Emergency contact deleted successfully.'
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

        $model = EmergencyContact::find($id);
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
