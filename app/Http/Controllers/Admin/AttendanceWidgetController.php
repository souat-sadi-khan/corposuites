<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceStatusService;

/**
 * Backs the header attendance widget's own refresh-after-action call — the
 * check-in/check-out endpoints themselves stay on AttendancePortalController
 * (no duplicate business logic), this just re-resolves the widget's current
 * state so the dropdown can update itself without a full page reload.
 *
 * Deliberately ungated like attendance-portal/* (see routes/admin.php) — an
 * employee reading their own live status is not something a permission slug
 * should be able to withhold from them.
 */
class AttendanceWidgetController extends Controller
{
    public function status()
    {
        $employee = auth()->guard('admin')->user()?->employee;
        abort_unless($employee, 403, 'This account is not linked to an employee.');

        // Returns the SAME server-rendered partial the header composer uses
        // on a normal page load, so the dropdown's markup only ever exists
        // in one place (this Blade partial), never duplicated into JS.
        return view('admin.layout.partials.attendance-widget-body', [
            'w' => AttendanceStatusService::forEmployee($employee),
        ]);
    }
}
