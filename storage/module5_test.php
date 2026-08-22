<?php

use App\Models\Employee;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Admin;
use App\Http\Controllers\Admin\AttendancePortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$dept1 = Department::create(['name' => 'M5DeptA-' . uniqid(), 'status' => 1]);
$dept2 = Department::create(['name' => 'M5DeptB-' . uniqid(), 'status' => 1]);
$shift = Shift::first() ?? Shift::create(['name' => 'M5Shift', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'status' => 1]);

$empA = Employee::create(['employee_code' => 'M5A' . rand(1000, 9999), 'first_name' => 'Alice', 'last_name' => 'M5', 'email' => 'm5a' . uniqid() . '@x.com', 'phone' => '1', 'gender' => 'female', 'date_of_birth' => '1990-01-01', 'date_of_joining' => now()->subYear(), 'department_id' => $dept1->id, 'shift_id' => $shift->id, 'status' => 1]);
$empB = Employee::create(['employee_code' => 'M5B' . rand(1000, 9999), 'first_name' => 'Bob', 'last_name' => 'M5', 'email' => 'm5b' . uniqid() . '@x.com', 'phone' => '1', 'gender' => 'male', 'date_of_birth' => '1990-01-01', 'date_of_joining' => now()->subYear(), 'department_id' => $dept2->id, 'shift_id' => $shift->id, 'status' => 1]);
$empC = Employee::create(['employee_code' => 'M5C' . rand(1000, 9999), 'first_name' => 'Carl', 'last_name' => 'M5', 'email' => 'm5c' . uniqid() . '@x.com', 'phone' => '1', 'gender' => 'male', 'date_of_birth' => '1990-01-01', 'date_of_joining' => now()->subYear(), 'department_id' => $dept1->id, 'shift_id' => $shift->id, 'status' => 1]);

$admin = Admin::first(); // any admin to satisfy auth() calls if needed (monthly() has no self-scoping)

$monthStart = \Carbon\Carbon::parse('2026-08-01');
Attendance::create(['employee_id' => $empA->id, 'attendance_date' => $monthStart->copy()->day(3), 'check_in' => '09:00:00', 'check_out' => '18:00:00', 'attendance_status' => 'present', 'status' => 1]);
Attendance::create(['employee_id' => $empA->id, 'attendance_date' => $monthStart->copy()->day(4), 'check_in' => '09:20:00', 'check_out' => '18:00:00', 'attendance_status' => 'late', 'status' => 1]);
Attendance::create(['employee_id' => $empB->id, 'attendance_date' => $monthStart->copy()->day(3), 'attendance_status' => 'on_leave', 'status' => 1]);
Attendance::create(['employee_id' => $empC->id, 'attendance_date' => $monthStart->copy()->day(5), 'check_in' => '09:00:00', 'check_out' => '18:00:00', 'attendance_status' => 'present', 'status' => 1]);

$controller = app(AttendancePortalController::class);

echo '=== TEST 1: All employees, August 2026 — query count check (must NOT scale per employee) ===' . PHP_EOL;
DB::enableQueryLog();
$req = Request::create('/x', 'GET', ['month' => '2026-08']);
$resp = $controller->monthly($req);
$html = $resp->render();
$queries = DB::getQueryLog();
DB::disableQueryLog();
echo 'Total queries for the whole sheet (all employees): ' . count($queries) . PHP_EOL;
echo 'Rendered length: ' . strlen($html) . PHP_EOL;

echo PHP_EOL . '=== TEST 2: All three test employees appear ===' . PHP_EOL;
echo 'Contains Alice: ' . (str_contains($html, 'Alice M5') ? 'yes' : 'NO') . PHP_EOL;
echo 'Contains Bob: ' . (str_contains($html, 'Bob M5') ? 'yes' : 'NO') . PHP_EOL;
echo 'Contains Carl: ' . (str_contains($html, 'Carl M5') ? 'yes' : 'NO') . PHP_EOL;

echo PHP_EOL . '=== TEST 3: Department filter narrows correctly (dept1 = Alice + Carl, NOT Bob) ===' . PHP_EOL;
$req2 = Request::create('/x', 'GET', ['month' => '2026-08', 'department_id' => $dept1->id]);
$html2 = $controller->monthly($req2)->render();
echo 'Contains Alice: ' . (str_contains($html2, 'Alice M5') ? 'yes' : 'NO') . PHP_EOL;
echo 'Contains Carl: ' . (str_contains($html2, 'Carl M5') ? 'yes' : 'NO') . PHP_EOL;
echo 'Contains Bob (should be NO): ' . (str_contains($html2, 'Bob M5') ? 'YES - BUG' : 'no, correct') . PHP_EOL;

echo PHP_EOL . '=== TEST 4: Single-employee filter ===' . PHP_EOL;
$req3 = Request::create('/x', 'GET', ['month' => '2026-08', 'employee_id' => $empB->id]);
$html3 = $controller->monthly($req3)->render();
echo 'Contains Bob only: ' . (str_contains($html3, 'Bob M5') && !str_contains($html3, 'Alice M5') ? 'yes, correct' : 'NO') . PHP_EOL;

echo PHP_EOL . '=== TEST 5: Data correctness via buildForEmployees vs single build() — must match exactly ===' . PHP_EOL;
$service = app(\App\Services\AttendanceReportService::class);
$from = \Carbon\Carbon::parse('2026-08-01')->startOfMonth();
$to = \Carbon\Carbon::parse('2026-08-01')->endOfMonth();
$single = $service->build($empA->fresh(), $from, $to);
$batch = $service->buildForEmployees(collect([$empA->fresh(), $empB->fresh(), $empC->fresh()]), $from, $to);
echo 'Single-employee summary for Alice: ' . json_encode($single['summary']) . PHP_EOL;
echo 'Batch summary for Alice (must be IDENTICAL): ' . json_encode($batch[$empA->id]['summary']) . PHP_EOL;
echo 'Match: ' . ($single['summary'] === $batch[$empA->id]['summary'] ? 'YES, identical' : 'NO - MISMATCH BUG') . PHP_EOL;

echo PHP_EOL . '=== TEST 6: Weekend column highlighting uses the app setting, not Carbon default ===' . PHP_EOL;
echo 'Contains msheet-weekend-col class: ' . (str_contains($html, 'msheet-weekend-col') ? 'yes' : 'NO') . PHP_EOL;

echo PHP_EOL . '=== TEST 7: hover tooltip data attribute present with real check-in/out data ===' . PHP_EOL;
echo 'Contains data-tip with Alice + 09:00 AM: ' . (str_contains($html, 'Alice M5|03 Aug 2026|09:00 AM') ? 'yes' : 'NO') . PHP_EOL;

echo PHP_EOL . '=== CLEANUP ===' . PHP_EOL;
Attendance::whereIn('employee_id', [$empA->id, $empB->id, $empC->id])->delete();
$empA->delete(); $empB->delete(); $empC->delete();
$dept1->delete(); $dept2->delete();
echo 'Done.' . PHP_EOL;
