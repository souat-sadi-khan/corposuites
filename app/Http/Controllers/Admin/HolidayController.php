<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayRequest;
use App\Models\Holiday;
use App\Services\HolidayService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class HolidayController extends Controller
{
    use ActivityLogger;

    protected $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    /**
     * Display a modal for how to use the employee holidays.
     */
    public function howTo()
    {
        return view('admin.holidays.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Holiday::query();

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

            $query->orderBy('date', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.holidays.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('date_formatted', function ($row) {
                    return $row->date->format('d-m-Y') . ' <span class="text-muted">(' . $row->date->format('l') . ')</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.holidays.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'date_formatted', 'action'])
                ->make(true);
        }

        return view('admin.holidays.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.holidays.create');
    }

    /**
     * Display holidays in calendar view.
     */
    public function calendar()
    {
        return view('admin.holidays.calendar');
    }

    /**
     * Calendar events feed (AJAX).
     */
    public function calendarEvents(Request $request)
    {
        $holidays = Holiday::active()->get();

        $events = $holidays->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'description' => $holiday->description,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HolidayRequest $request)
    {
        DB::beginTransaction();

        try {
            $holiday = $this->holidayService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'holidays',
                'action' => 'create',
                'model' => 'Holiday',
                'model_id' => $holiday->id,
                'description' => 'Holiday "' . $holiday->name . '" created',
                'new_data' => $holiday->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Holiday created successfully.'
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
    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HolidayRequest $request, Holiday $holiday)
    {
        DB::beginTransaction();

        try {
            $oldData = $holiday->toArray();
            $updatedHoliday = $this->holidayService->update($holiday, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'holidays',
                'action' => 'update',
                'model' => 'Holiday',
                'model_id' => $holiday->id,
                'description' => 'Holiday "' . $holiday->name . '" updated',
                'new_data' => $updatedHoliday->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.holidays.index'),
                'message' => 'Holiday updated successfully.'
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
    public function destroy(Holiday $holiday)
    {
        DB::beginTransaction();

        try {
            $oldData = $holiday->toArray();

            $this->holidayService->delete($holiday);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'holidays',
                'action' => 'delete',
                'model' => 'Holiday',
                'model_id' => $oldData['id'],
                'description' => 'Holiday "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Holiday deleted successfully.'
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

        $model = Holiday::find($id);
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
