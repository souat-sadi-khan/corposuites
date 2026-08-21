<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\EmploymentStatus;
use App\Models\Shift;

class MasterDetailController extends Controller
{
    public function show(string $type, int $id)
    {
        $masters = [
            'employee-type' => ['model' => EmployeeType::class, 'title' => 'Employee Type'],
            'employment-status' => ['model' => EmploymentStatus::class, 'title' => 'Employment Status'],
            'department' => ['model' => Department::class, 'title' => 'Department'],
            'designation' => ['model' => Designation::class, 'title' => 'Designation'],
            'shift' => ['model' => Shift::class, 'title' => 'Shift'],
        ];

        abort_unless(isset($masters[$type]), 404);
        $config = $masters[$type];
        $master = $config['model']::with(['employees.admin.roles'])->findOrFail($id);

        $activities = ActivityLog::with('admin')
            ->where('model', class_basename($config['model']))
            ->where('model_id', $master->id)
            ->latest()
            ->get();

        $designations = $master instanceof Department
            ? $master->designations()->withCount('employees')->orderBy('name')->get()
            : collect();

        return view('admin.master-details.show', [
            'master' => $master,
            'title' => $config['title'],
            'employees' => $master->employees->sortBy('full_name'),
            'activities' => $activities,
            'designations' => $designations,
        ]);
    }
}
