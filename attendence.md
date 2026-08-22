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
