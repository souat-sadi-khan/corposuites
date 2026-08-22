---

# Implementation Progress Tracker

(Audited directly against the current codebase — updated as each task is finished. One task at a time.)

CROSS-CUTTING FIX 2026-08-29 (per user question "what timezone is it using / get it from Localization settings"): found and fixed a real, app-WIDE bug — the Localization settings page lets an admin pick a "Default Timezone" (saved as the `timezone` system setting, e.g. Asia/Dhaka) but nothing ever actually applied it anywhere; the whole app (every now()/today() call, including every attendance check-in/checkout time and "today" boundary, not just Attendance) was silently running on config('app.timezone') from .env (UTC) regardless. Fixed in App\Providers\AppServiceProvider::boot() (applyConfiguredTimezone(), guarded so it never breaks a fresh-install console command like migrate before the settings table exists). Verified: now()/today() correctly return Asia/Dhaka time after the fix (was UTC before), migrate:status still runs cleanly. Also fixed a related latent bug found while reading this code: Helper.php's tz_list() (used by the Localization page's own timezone dropdown) called date_default_timezone_set() once per zone to compute GMT offsets but never restored the original timezone afterward, silently leaving the WHOLE REST OF THAT REQUEST on the last zone in PHP's list — fixed by saving/restoring it.

Module 1: Schema / Backend Foundation — RE-VERIFIED 2026-08-29 (all 8 migrations confirmed Ran; columns match models exactly)
[*] attendances table has employee_id, leave_request_id, attendance_date, check_in/out, geolocation, source, attendance_status enum, overtime_hours, leave snapshot fields, remarks, status
[*] shifts table (name, start_time, end_time, grace period usage, description, status)
[*] Employee/Attendance model relations (shift(), attendances(), employee())
[*] payrolls.attendance_deduction column

Module 2: Check-In / Check-Out Self-Service Backend — RE-VERIFIED 2026-08-29 (11-scenario live lifecycle test + 1 edge-case regression test, all passing; 1 real bug found and fixed)
[*] AttendancePortalController::checkIn() — on-time, late, and duplicate-check-in-rejected all verified correct
[*] AttendancePortalController::checkOut() — checkout-without-checkin rejected, duplicate-checkout rejected, verified correct
[*] AttendancePortalController::devicePunch() (biometric device webhook, token-authed, CSRF-exempt) — correct-token accepted, wrong-token rejected, verified
[*] Geolocation + geofence validation — verified: distant coordinate rejected with distance shown, in-radius coordinate accepted
[*] Late / half-day / early-leave detection vs shift + grace period — verified: on-time, late, half_day (<50% shift worked), early_leave (>=50% but <full shift), full-shift-worked-stays-present, all correct
[*] Overnight shift handling — BUG FOUND & FIXED: checkOut() looked up the attendance row by whereDate('attendance_date', today()), which never matches an overnight shift's row (dated the day BEFORE checkout) — every overnight check-out failed with a false "Check in before checking out." Fixed in app/Http/Controllers/Admin/AttendancePortalController.php: checkOut() now falls back to an still-open (check_in set, check_out null) record from yesterday when no record exists for today, while still correctly saying "check in before checking out" (not "already checked out") for a normal employee who has a closed record from yesterday but hasn't checked in yet today. Re-verified: overnight shift now checks out correctly (early_leave, 5min short of full 8h shift), and the no-false-positive edge case confirmed separately.

Module 3: Header Attendance Widget — COMPLETED 2026-08-29 (8-state live functional test + non-employee-linked-admin regression test, all passing)
[*] View composer / lightweight service for today's attendance+shift+leave status — app/Services/AttendanceStatusService.php (centralized, reused by both the header composer and the AJAX refresh endpoint), wired via AppServiceProvider's existing header composer (only queries when auth()->employee exists, no N+1)
[*] Header/topbar widget markup (status, check-in/out time, worked hours, shift) — resources/views/admin/layout/partials/header.blade.php (new .tb-btn/.tb-dd widget, matches existing notif/profile dropdown convention exactly) + resources/views/admin/layout/partials/attendance-widget-body.blade.php (shared partial, reused by both the initial page render and the AJAX refresh so markup exists in one place only)
[*] Check In / Check Out action buttons in widget (AJAX, loading/success/error states) — public/assets/system/js/attendance-widget.js, posts to the SAME existing admin.attendance-portal.check-in/check-out endpoints (no duplicate business logic), disables buttons + shows "Checking in/out..." while in flight (duplicate-request guard), refreshes via a new GET admin.attendance-widget.status endpoint (App\Http\Controllers\Admin\AttendanceWidgetController) after success
[*] Verified all 8 states resolve correctly: not_checked_in, checked_in, late, checked_out, on_leave (via attendance row), holiday, weekly_off, absent — all through a real employee/admin/shift fixture, not just code review
[*] Verified widget correctly absent (zero markup, zero queries triggered) for a plain admin with no linked employee
[*] REVISED 2026-08-29 per user feedback ("button not working" + "move to left side" + "must show status clearly") — reproduced live in a real browser session (not just code review), found and fixed 3 real bugs:
    1. Trigger button click did nothing: the dropdown's markup had a leftover inline style="display:none" copied from the Notifications dropdown's markup — inline styles beat the .tb-dd.is-open CSS class, so opening never worked. (Notifications only "works" because notification.js separately toggles its own inline style.display; every other dropdown in the header — profile, language, quick menus — correctly has NO inline display and relies on the CSS class alone, which is the actually-correct pattern.) Fixed by removing the inline style, matching those.
    2. Check In / Check Out buttons did nothing even after fixing 1: theme.js registers `$('.tb-dd').on('click', e => e.stopPropagation())` on every dropdown so an in-dropdown click doesn't bubble up and trigger the global click-anywhere-closes-dropdowns handler — that same stopPropagation silently swallowed my document-level delegated button handlers before they ever fired. Fixed by delegating from the stable attendanceWidgetDd container instead of document.
    3. After a successful check-in/out, the header chip's label/time/color never updated (only the dropdown body did), and the success message flashed and vanished instantly because the refresh replaced the message element in the same tick it was set. Fixed both in public/assets/system/js/attendance-widget.js.
    Redesigned as requested: moved from the right-hand icon cluster into the header's LEFT side (next to the page title, before the search box — .tb-breadcrumb area), and changed from a small icon+dot into an always-visible text chip ("● Late · 09:20 AM") so status is readable without opening the dropdown at all.
    Verified live end-to-end after all fixes: open/close, Check In → success message → chip updates to Late with the real check-in time → Check Out button appears → Check Out → success message → chip updates to "Checked Out" → action buttons correctly gone. All temporary browser-test fixtures (including 2 orphaned sets left behind by an earlier failed test script run, caught by a follow-up sweep) cleaned up.
[*] FINAL RE-VERIFICATION 2026-08-29 (post timezone-fix + UI redesign, requested explicitly) — no code drift found between the two rounds; re-ran everything fresh:
    - All 8 states re-checked via AttendanceStatusService directly under the NOW-CORRECT Asia/Dhaka timezone (previously only tested under UTC) — all correct, including that today's real date genuinely falls on a configured weekly-off day (Saturday) and the service correctly reports weekly_off rather than not_checked_in for it, matching real settings data.
    - Query count for a full state resolution: 5 queries (employee's own row already in memory, shift lazy-load, attendance lookup, holiday lookup, leave-request exists check) — direct point lookups, no N+1 loop.
    - Fresh live browser click-through end-to-end: open dropdown → Check In → "Checked in late." success message → chip updates to Late with the REAL Asia/Dhaka check-in time (11:04 PM, not a UTC-shifted time) → Check Out → "Checked out successfully." → chip updates to Checked Out, zero action buttons remain → outside-click correctly closes the dropdown. Zero console errors throughout.
    - Non-employee-linked admin regression re-confirmed absent in the prior round; code unchanged since, no re-check needed.
    Module 3 confirmed fully correct and stable as of this re-verification. All test fixtures cleaned up.

Module 4: My Attendance (Employee Self-Service Page) — FULLY COMPLETED 2026-08-29 (all sub-items done, live/functionally verified)
[*] Route + controller (attendance-portal.index/check-in/check-out)
[*] Proper "My Attendance" UI — full redesign: gradient hero card (modern check-in/out buttons, remix icons, live times) + 8 summary stat cards (Present/Absent/Late/On Leave/Worked Hours/Overtime/Half Day/Missing Checkout) + full monthly detail table, all built on a new, centralized App\Services\AttendanceReportService (one query for attendance + one for holidays across the whole range, not per-day — reused for the future admin Module 6 report too)
[*] Month/Date-range filters — Month (Y-m) OR explicit Date From/Date To (overrides month), capped at 92 days, defaults to current month; verified via real controller calls: full-month mode correctly classified all 31 August days (present/absent/late/on_leave/holiday/weekly_off/pending sum to exactly 31, worked-minutes math correct to the minute), range mode correctly scoped to exactly the 3 requested days
[*] Added to HRM sidebar menu — "My Attendance" under the existing "Attendance & Leave" group, permission=null (self-service, same ungated reasoning as the routes themselves), verified present in a live rendered sidebar
[*] Attendance adjustment request action from this page — COMPLETED 2026-08-29: reuses the EXISTING AttendanceAdjustment model/AttendanceAdjustmentService (no duplicate correction system) via two new ungated self-service routes (admin.attendance-portal.adjustment.form/store), a new resources/views/admin/attendance-portal/adjustment.blade.php form (pre-fills the day's actual recorded check-in/out as a starting point), and a "Request Adjustment" link + "Missing Checkout" indicator + "Adjustment: Pending/Approved/Rejected" badge added to every past-day row in the My Attendance table. Verified via 7 real functional tests: form pre-fill correct, future-date requests correctly rejected, a spoofed employee_id in the POST body is correctly ignored and forced to the real logged-in employee's own id (never trusted from the client, per PART 18/19), duplicate pending requests for the same date correctly rejected, the "already pending" notice correctly replaces the form once one exists, and all three table indicators (adjustment badge / request link / missing-checkout flag) render correctly.
[*] Modernized Check In/Check Out buttons with gradient + remix icons per user feedback (was plain Bootstrap buttons)

Module 5: Monthly Attendance Sheet / Calendar (Admin)
[*] Route + controller (admin.attendances.monthly) with holiday/weekend detection
[ ] Proper calendar/sheet UI (sticky columns, day-code badges P/A/L/H/WO/LV/HD, hover detail) — current view is a bare inline-HTML table
[ ] Add to HRM sidebar menu (currently direct-URL only)

Module 6: Admin Attendance Report (Advanced Filters + Summary Cards)
[ ] Date range / department / designation / shift / employee-type filters (currently only status + employee_id + search)
[ ] Centralized AttendanceReportService for calculations
[ ] Summary cards (Present/Absent/Late/Leave/Worked Hours/Overtime/Missing Checkouts)
[ ] Dedicated attendance report page (distinct from the generic HR dashboard tile)

Module 7: Attendance Adjustment Integration
[*] AttendanceAdjustment model/controller: full CRUD + approve()/reject(), Approvable/HasWorkflow
[ ] Visual indicator on Attendance list/sheet for pending/approved/rejected adjustment
[ ] "Request Adjustment" quick action wired from attendance record/day cell

Module 8: Leave Integration
[*] LeaveAttendanceService::syncApprovedLeave() / removeLeave() — approved leave writes on_leave into attendances with original-status snapshot/restore
[ ] Leave state/type/duration shown in monthly sheet + admin report (view-level)

Module 9: PDF Export
[ ] Install PDF package (e.g. barryvdh/laravel-dompdf) — not present in composer.json
[ ] Attendance PDF template (landscape, header/footer, summary, filter-aware)

Module 10: Excel Export
[ ] Install Excel package (e.g. maatwebsite/excel) — not present in composer.json
[ ] AttendanceExport class, filter-aware, using shared report service

Module 11: CSV Export
[ ] CSV export endpoint (filter-aware, stable column names)

Module 12: Permissions Mapping
[*] attendance.{view,create,edit,delete} and attendance-adjustment.{view,create,edit,delete,approve,reject} permissions exist and are enforced (route middleware + sidebar can() checks are ACTIVE — CLAUDE.md's "enforcement disabled" note is stale)
[ ] Dedicated permission for self-service check-in/out (currently just auth + linked-employee check)
[ ] Dedicated permission for monthly report route (currently reuses attendance.view)

---

You are working on my existing **CorpoSuites**, an enterprise-grade ERP system built primarily with **Laravel + jQuery + Bootstrap**.

Your task is to inspect the existing HRM architecture first, understand the current coding conventions, routes, controllers, models, services, repositories, Blade structure, permissions, AJAX patterns, DataTables usage, export patterns, and UI system, and then upgrade the existing **Attendance Management module** without unnecessarily breaking or replacing working functionality.

Do not assume the project structure. First inspect the relevant existing files and follow the same architecture and naming conventions already used in the application.

# Core User Type Logic

The system uses the `admins` table for backend authentication.

There is a field:

```php
admins.employee_id
```

Use the following rule:

```text
employee_id IS NULL
    = Normal Admin / Back-office User

employee_id IS NOT NULL
    = Logged-in Admin account is linked with an Employee
      and should also behave as an Employee/Self-Service User
```

This distinction is extremely important.

A logged-in employee may still have administrative permissions. Therefore, do not simply classify an employee-linked admin as a restricted employee.

The final authorization logic should effectively consider both:

```text
1. Is this account linked to an employee?
2. What permissions does this account have?
```

A user with sufficient attendance permissions can access records allowed by those permissions.

An employee-linked user without broader attendance permissions must only be able to access his/her own attendance records.

Never allow another employee's record to be exposed simply by manipulating request parameters.

---

# Existing HRM Attendance / Leave Area

The current system already contains the following modules:

* Attendance
* Attendance Adjustments
* Leave Balances
* Leave Requests
* Leave Calendar

The Attendance module currently mainly uses a normal DataTable/list-style view.

Do not remove existing stable functionality unless necessary.

Instead, extend/refactor it into a professional enterprise attendance management experience.

---

# PART 1 — Employee Attendance Self-Service

When a logged-in account has:

```php
auth()->user()->employee_id != null
```

the system should provide an employee self-service attendance experience.

## Header Attendance Widget

Create a modern compact attendance/status widget in the main header/topbar.

It should show the employee's attendance status for TODAY.

Example information:

```text
Today
Saturday, 22 August 2026

Status: Checked In

Check In: 09:03 AM
Check Out: --
Worked: 05h 42m
Shift: Morning Shift
```

Possible states should include:

```text
Not Checked In
Checked In
Checked Out
Late
On Leave
Holiday
Weekly Off
Absent
```

Do not blindly hardcode these statuses. Reuse existing attendance, shift, holiday and leave logic where applicable.

The widget should provide contextual action buttons.

Before check-in:

```text
[ Check In ]
```

After check-in:

```text
[ Check Out ]
```

After checkout:

```text
Completed
```

Buttons must not allow invalid actions such as:

```text
double check-in
checkout without check-in
multiple checkout
editing another employee's attendance
```

Use server-side validation even if frontend validation exists.

Use AJAX for check-in/check-out if that matches the existing project architecture.

Provide loading state, success state and clean validation/error feedback.

---

# PART 2 — Check-In / Check-Out Data

Inspect the existing Attendance database schema before implementation.

Reuse current columns wherever possible.

If the current schema cannot properly support employee punch activity, propose and implement the smallest safe migration required.

Potential information may include:

```text
employee_id
attendance_date
shift_id
check_in
check_out
status
late_minutes
early_leave_minutes
working_minutes
overtime_minutes
source
remarks
created_by
updated_by
```

Do NOT create duplicate fields if equivalent fields already exist.

If the system already supports attendance devices / punching machines, do not interfere with that architecture.

Design the check-in/check-out source so attendance can distinguish, when relevant:

```text
web
mobile
device
manual
import
adjustment
```

Only implement fields that make architectural sense after inspecting the existing project.

---

# PART 3 — Employee "My Attendance"

Create a dedicated employee attendance page such as:

```text
My Attendance
```

An employee without elevated Attendance permission must only see:

```text
their own employee_id
```

This restriction must be applied server-side.

The page should default to the current month.

Provide filters such as:

```text
Month
Year
Date Range
Attendance Status
Shift
```

Where applicable.

If the user only has self-service access, DO NOT show an employee selector.

If the employee-linked user also has broader attendance permission, allow the normal administrative filters according to the permission system.

---

# PART 4 — Monthly Attendance Calendar / Sheet

I need a professional monthly attendance view in addition to the current DataTable.

The visual concept is similar to a conventional HR attendance sheet:

```text
Employee | Department | 1 | 2 | 3 | 4 | ... | 31 | Summary
```

Each day should show an abbreviated attendance code.

For example:

```text
P   = Present
A   = Absent
L   = Late
H   = Holiday
WO  = Weekly Off
LV  = Leave
HD  = Half Day
WFH = Work From Home
OD  = Official Duty
```

Use only statuses actually supported by the project. Extend them cleanly if the existing architecture already allows such states.

The uploaded reference image demonstrates the general idea of a monthly attendance sheet, but the final UI must be significantly more modern and suitable for CorpoSuites.

The monthly sheet should have:

```text
Sticky employee columns
Sticky table header
Horizontally scrollable day columns
Compact cells
Clear weekend indicators
Holiday indicators
Attendance status badges/cells
Hover details
Responsive behavior
Professional enterprise styling
```

On hover or click of a day cell, display useful details such as:

```text
Employee
Date
Shift
Check In
Check Out
Worked Hours
Late Minutes
Overtime
Status
Remarks
```

Do not overload every table cell with all information.

---

# PART 5 — Administrative Attendance Report

Create a full monthly/reporting attendance page for authorized admin users.

It must support advanced filters.

At minimum:

```text
Employee
Department
Designation
Branch / Location, if supported
Shift
Attendance Status
Month
Year
Date From
Date To
```

Also inspect the HRM architecture for additional useful filters such as:

```text
Employee Type
Employment Status
Team
Company / Tenant
Late Attendance
Missing Checkout
Overtime
```

Only add filters that make sense with existing data.

Filters should work together.

Use AJAX/filtering if consistent with the current project.

---

# PART 6 — Summary Calculation

For the selected reporting period, calculate meaningful attendance totals.

Examples:

```text
Working Days
Present Days
Absent Days
Late Days
Leave Days
Holiday Days
Weekly Off Days
Half Days
Total Worked Hours
Total Late Minutes
Total Overtime
Missing Checkouts
```

Display summary cards above the attendance sheet.

Example:

```text
Present          22
Absent            2
Late              5
Leave             1
Worked Hours    176h 35m
Overtime         08h 20m
```

The calculation must be centralized and reusable.

Do not duplicate attendance-calculation logic separately inside Blade, export classes and controller methods.

Prefer a service such as:

```php
AttendanceReportService
```

or follow the service/repository convention already used by the project.

---

# PART 7 — Attendance Rules

When calculating attendance, consider existing HRM entities such as:

```text
Employee joining date
Employee status
Shift
Shift start/end
Grace period
Weekly off
Holiday
Approved leave
Attendance adjustment
Check-in
Check-out
```

Do not mark an employee absent on:

```text
approved leave
holiday
weekly off
before joining date
after employment termination/inactive date
```

if the current HR architecture contains the necessary information.

Late calculation should be based on the applicable shift and grace-period rule rather than an arbitrary hardcoded time.

For example:

```text
Shift starts: 09:00 AM
Grace period: 10 minutes
Employee checks in: 09:14 AM

Late Minutes = 4
```

Only use this rule if it is compatible with the current project schema/configuration.

---

# PART 8 — Detailed Attendance List

Keep or improve the current DataTable attendance view.

It should be useful for detailed punch-level records.

Possible columns:

```text
Employee
Date
Shift
Check In
Check Out
Worked
Late
Overtime
Status
Source
Action
```

Admin users can access actions according to existing permission rules.

Employee-only users should not be able to edit attendance records unless an explicit permission allows that workflow.

For self-service corrections, prefer the existing:

```text
Attendance Adjustment
```

workflow rather than directly editing attendance.

---

# PART 9 — Attendance Adjustment Integration

Integrate Attendance Adjustment with the attendance sheet.

If an attendance record has:

```text
missing punch
incorrect punch
adjustment pending
adjustment approved
adjustment rejected
```

show an appropriate indicator.

Example:

```text
09:10 AM → Missing Checkout
[Request Adjustment]
```

If the logged-in employee clicks an eligible attendance record, provide a convenient route/action to create an attendance adjustment request using the existing adjustment module.

Do not create a duplicate correction system.

---

# PART 10 — Leave Integration

Attendance reporting must integrate with the existing:

```text
Leave Requests
Leave Balance
Leave Calendar
```

If an approved leave exists for an employee on a date, the attendance sheet should display the appropriate leave state.

Where useful, hovering/clicking may display:

```text
Leave Type
Leave Duration
Approval Status
```

Do not expose sensitive leave remarks unnecessarily.

---

# PART 11 — Export System

The Attendance Report page must support one-click exports:

```text
PDF
Excel
CSV
```

Exports must respect all selected filters.

Do not export the unfiltered dataset when filters are active.

Use the same shared AttendanceReportService/query builder for:

```text
Browser View
PDF
Excel
CSV
```

so all outputs remain consistent.

---

# PDF Requirements

Generate a professional enterprise attendance PDF.

It should have:

```text
Company logo
Company name
Report title
Selected date/month range
Selected filter summary
Generated date/time
Attendance sheet
Summary totals
Page number
Professional footer
```

Example title:

```text
CorpoSuites
Monthly Attendance Report
August 2026
```

For wide monthly attendance sheets, use:

```text
Landscape orientation
Suitable paper size
Compact typography
Repeated table header on each page
```

Do not generate an unreadable PDF with tiny uncontrolled columns.

If necessary, intelligently split the report across pages.

---

# Excel Requirements

The Excel export should be useful for HR and payroll processing.

Suggested structure:

```text
Employee ID
Employee
Department
Designation
01
02
03
...
31
Present
Absent
Late
Leave
Worked Hours
Overtime
```

Use proper Excel date/time/number cells rather than converting everything to plain strings where avoidable.

Add a professional header row.

Freeze relevant panes if supported by the existing export library.

---

# CSV Requirements

Provide a clean machine-readable CSV.

Use stable column names.

Avoid decorative content that makes CSV processing difficult.

---

# PART 12 — UI / UX

The interface must match a modern enterprise ERP rather than a generic Bootstrap admin template.

Use the project's existing CSS variables/theme system where possible.

Required visual characteristics:

```text
Clean
Compact
Professional
High information density
Readable
Responsive
Light/Dark theme compatible if the existing system supports it
```

Avoid:

```text
huge cards
excessive gradients
large rounded SaaS-style blocks
random colors
excessive shadows
oversized whitespace
```

Attendance statuses should use restrained semantic styling.

Example:

```text
Present       green-ish state
Absent        red-ish state
Late          amber-ish state
Leave         blue-ish state
Holiday       neutral/purple-ish state
Weekly Off    muted state
```

Use existing design tokens whenever possible instead of introducing arbitrary colors.

---

# Suggested Attendance Page Structure

A professional page can follow this hierarchy:

```text
Attendance
--------------------------------------------------

[ Current Month ] [ Date Range ]
[ Employee ] [ Department ] [ Designation ]
[ Shift ] [ Status ]                [ Filter ]

--------------------------------------------------

Present      Late       Absent      Leave
   142         18           9          12

Worked Hours                    Overtime
1,246h 35m                      43h 20m

--------------------------------------------------

[ Monthly Sheet ] [ Detailed Records ]

--------------------------------------------------

Employee   Dept   01 02 03 04 05 ... 31 | P | A | L
--------------------------------------------------
EMP001     IT      P  P  L  P  H ...  P |22 | 1 | 2
EMP002     HR      P  A  P  P  H ...  P |21 | 2 | 0
```

The exact design should be improved based on CorpoSuites' existing visual system.

---

# PART 13 — Header Employee Widget Behavior

Do not perform large repeated database queries from every page request just to render the header attendance widget.

Build an efficient solution.

For example, a lightweight service/view composer/component may retrieve only:

```text
today's attendance
today's shift
today's leave/holiday state
```

for the currently authenticated employee.

Avoid N+1 queries.

Cache only if safe and useful because attendance status changes immediately after check-in/check-out.

---

# PART 14 — Permissions

Inspect the project's existing permission architecture.

Do NOT invent a parallel permission system.

Map attendance capabilities into the current permission approach.

Conceptually the system may require permissions equivalent to:

```text
attendance.view
attendance.view_all
attendance.create
attendance.update
attendance.delete
attendance.export
attendance.report
attendance.adjustment
```

But use existing permission naming conventions instead of blindly creating these exact names.

Critical rule:

```text
Employee-linked account
+
No attendance "view all" capability
=
Can only retrieve own employee attendance.
```

This rule must be enforced at query level/server level.

Never rely solely on hiding frontend controls.

---

# PART 15 — Multi-Tenant / ERP Safety

CorpoSuites is an enterprise ERP.

If the project uses:

```text
tenant_id
company_id
organization_id
branch_id
```

or another tenant isolation mechanism, all attendance queries and exports must respect it.

Never allow cross-company attendance data leakage.

Use the current tenancy architecture rather than inventing a new one.

---

# PART 16 — Backend Architecture

Do not build a massive AttendanceController.

Keep responsibilities separated.

A possible architecture could be:

```text
AttendanceController
AttendanceReportController
EmployeeAttendanceController

AttendanceService
AttendanceReportService
AttendanceCalculationService
AttendanceExportService
```

But first inspect the existing project architecture and adapt to it.

Prefer:

```text
Form Requests for validation
Services for business logic
Policies / existing permission helpers for authorization
Eloquent scopes/query builders for reusable filtering
API Resources only if relevant
Transactions for sensitive writes
```

Do not over-engineer if the existing application follows a simpler architecture.

---

# PART 17 — Query Performance

Attendance reporting can involve:

```text
hundreds/thousands of employees
x
31 days
```

Therefore avoid inefficient query patterns.

Do NOT execute:

```text
employees × days
```

individual database queries.

Use:

```text
eager loading
grouped range queries
indexed columns
collection mapping
SQL aggregation where useful
```

Review indexes for frequently filtered fields such as:

```text
employee_id
attendance_date
shift_id
status
department-related employee fields
tenant/company id
```

Only add indexes through safe migrations where necessary.

---

# PART 18 — Security

Validate and authorize all operations.

Pay special attention to:

```text
Check-in
Check-out
Attendance record retrieval
Adjustment creation
Exports
Employee filter manipulation
```

Employees must never be able to request something like:

```text
?employee_id=OTHER_EMPLOYEE_ID
```

and retrieve another employee's records without permission.

Avoid mass assignment vulnerabilities.

Do not trust employee_id coming from the browser for employee self-service check-in/check-out.

Resolve it from the authenticated admin account:

```php
auth()->user()->employee_id
```

or the project's equivalent guard.

---

# PART 19 — Check-In / Check-Out Edge Cases

Handle edge cases explicitly.

Examples:

```text
Employee has no shift
Employee already checked in
Employee already checked out
Employee tries to checkout without check-in
Employee is on approved leave
Today is a holiday
Today is weekly off
Overnight shift
Shift crosses midnight
Missing previous checkout
Timezone handling
Duplicate AJAX request
```

Follow existing company HR configuration where available.

Do not silently make dangerous assumptions.

---

# PART 20 — Timezone

Use the company's configured timezone if the application provides one.

Avoid blindly using:

```php
now()
```

in inconsistent server timezone contexts.

Attendance date/time calculations must remain consistent across:

```text
Check-in
Check-out
Reports
PDF
Excel
Attendance sheet
```

---

# PART 21 — Monthly Sheet Data Structure

Design the backend response/data preparation efficiently.

A useful conceptual structure is:

```php
[
    'employees' => [
        [
            'employee' => ...,
            'days' => [
                '2026-08-01' => [
                    'code' => 'P',
                    'status' => 'present',
                    'check_in' => '09:02',
                    'check_out' => '18:03',
                    'worked_minutes' => 541,
                    'late_minutes' => 0,
                ],
                ...
            ],
            'summary' => [
                'present' => 22,
                'absent' => 2,
                'late' => 3,
                'leave' => 1,
                'worked_minutes' => 10560,
            ]
        ]
    ]
]
```

This is only a conceptual example.

Follow the architecture that best fits the current codebase.

---

# PART 22 — Tabs / Views

The Attendance module should preferably offer:

```text
Overview
Monthly Sheet
Detailed Attendance
```

Or a similarly clean structure.

For employee self-service:

```text
My Attendance
```

can be simplified while still showing:

```text
monthly attendance
summary
attendance details
adjustment actions
```

Avoid duplicating separate pages unnecessarily when the same components can be reused securely.

---

# PART 23 — Employee Monthly Summary

For a logged-in employee, show a useful personal monthly summary.

Example:

```text
August 2026

Present        21
Late            3
Absent          1
Leave           2

Worked       172h 35m
Overtime       6h 20m
Avg Check In   9:04 AM
```

If an average metric cannot be calculated accurately from the current architecture, omit it instead of approximating incorrectly.

---

# PART 24 — Responsive Design

Monthly attendance sheets are inherently wide.

On desktop:

```text
sticky employee information
horizontal day scrolling
```

On mobile/tablet:

Do not attempt to squeeze 31 day columns into the viewport.

Provide a responsive experience such as:

```text
horizontal scrolling
compact employee selector
day detail drawer/modal
summary cards
```

Maintain usability.

---

# PART 25 — Accessibility

Use:

```text
proper table semantics
button labels
tooltips where needed
keyboard-accessible actions
sufficient contrast
```

Attendance state must not rely only on color.

Always display a text/code such as:

```text
P
A
L
LV
```

along with semantic styling.

---

# PART 26 — Implementation Workflow

Work in phases.

## Phase 1 — Audit

Before editing anything, inspect and report:

```text
Current Attendance model/schema
AttendanceController(s)
Routes
Blade views
AttendanceAdjustment architecture
Leave integration
Shift architecture
Employee relationships
Admin authentication model
Permission system
Tenant/company scoping
Export packages already installed
Existing UI conventions
```

Then provide a concise implementation plan.

Do NOT start random modifications before understanding these dependencies.

## Phase 2 — Backend Foundation

Implement/refactor:

```text
attendance calculations
filters/query service
self-service authorization
check-in/check-out service
monthly report preparation
```

## Phase 3 — UI

Implement:

```text
header status widget
employee My Attendance
admin attendance report
monthly attendance sheet
summary cards
filters
```

## Phase 4 — Export

Implement:

```text
PDF
Excel
CSV
```

## Phase 5 — Validation

Test:

```text
normal admin
employee-linked admin
employee with attendance permissions
employee without attendance permissions
employee with no attendance
employee on leave
holiday
late attendance
missing checkout
multiple shifts if supported
tenant isolation
exports with filters
```

---

# PART 27 — Important Coding Rules

Follow these rules:

1. Reuse existing architecture wherever possible.
2. Do not rewrite unrelated modules.
3. Do not introduce React/Vue/Angular.
4. Continue using the project's current Laravel + jQuery + Bootstrap stack.
5. Follow existing Blade component conventions.
6. Follow existing AJAX response conventions.
7. Follow existing permission helpers/middleware.
8. Follow existing tenant scopes.
9. Do not hardcode company-specific IDs.
10. Do not hardcode employee IDs.
11. Do not hardcode year/month values.
12. Do not calculate attendance business logic inside Blade.
13. Do not run queries inside loops.
14. Avoid duplicated report/export queries.
15. Keep controllers reasonably thin.
16. Add comments only where they explain non-obvious business logic.
17. Preserve backward compatibility with current attendance data wherever possible.

---

# PART 28 — Deliverables

After the initial audit, implement and show me all important changes.

For every created or modified file, provide:

```text
File path
Purpose
Important architectural decision
Complete relevant code
```

Possible files may include:

```text
routes
controllers
services
requests
models/scopes
policies/permission logic
migrations
Blade views
JS
CSS
PDF template
Excel export
CSV export
```

Do not provide pseudocode where working Laravel code can be implemented.

---

# Final Goal

The final Attendance system should support two complementary experiences.

## Admin / HR Experience

```text
Complete attendance management
Advanced filtering
Monthly attendance sheet
Detailed attendance
Department/designation filtering
Attendance summaries
PDF/Excel/CSV reporting
Attendance adjustments
Leave-aware attendance
Permission-controlled management
```

## Employee Experience

```text
Today's attendance status in header
Check In / Check Out
Own monthly attendance
Own attendance summary
Advanced date/month filtering
Own attendance detail
Attendance adjustment request
No access to another employee's data unless explicitly permitted
```

The finished module should feel like a proper enterprise HRMS attendance system, not merely a CRUD table.

Before implementation, inspect the existing project and tell me:

1. What attendance architecture currently exists.
2. What can be reused.
3. What needs modification.
4. What new files/components are actually necessary.
5. Any schema limitations you discover.
6. Your proposed implementation order.

Then begin implementation phase-by-phase.
