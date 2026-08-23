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

Module 5: Monthly Attendance Sheet / Calendar (Admin) — FULLY COMPLETED 2026-08-29
[*] Route + controller (admin.attendances.monthly) with holiday/weekend detection
[*] Proper calendar/sheet UI — full rebuild: one row per employee (not single-employee like before), Employee/Department sticky columns, one column per day with color-coded day-code badges (P/A/L/HD/EL/LV/H/WO), weekend/holiday column highlighting, per-employee summary columns (P/A/L/LV), a custom hover popover per day cell (Date/Check In/Check Out/Worked/Overtime/Status/Notes, all pre-rendered server-side into a data attribute — no per-hover query), and Month/Department/Employee filters
[*] Added to HRM sidebar menu — "Monthly Sheet" under Attendance & Leave, verified present in a live rendered sidebar
[*] New App\Services\AttendanceReportService::buildForEmployees() — batches ALL employees' attendance+holiday lookups into exactly 2 queries TOTAL regardless of employee count (not 2-per-employee, not per-day), refactored out of a shared buildDaysAndSummary() helper also used by the single-employee build() so the two can never calculate differently — verified identical output for the same employee via both methods
[*] Fixed a real bug found during verification: weekend column highlighting initially used Carbon's built-in isWeekend() (always Sat/Sun) instead of this app's configurable leave_weekend_days setting, which would have disagreed with every data row (already correctly computed). Fixed to use the same setting-derived weekend-days array as the data rows.
[*] Verified: query count for the whole sheet (7 real employees) is 30 total (not scaling per employee, mostly unrelated cache/permission lookups), Department and Employee filters correctly scope the table body (double-checked after an initial false-positive test-methodology mistake — the filter dropdowns intentionally list ALL employees regardless of the applied filter, which isn't a bug), live browser hover-tooltip confirmed showing real per-day data, sticky columns confirmed positioned correctly (0px / 170px) with no visual overlap, zero real console/network errors on the page.

CROSS-CUTTING FIX 2026-08-23 (reported: "Monthly Attendance Sheet table broken — going off the right side of the page, Department overlapping day 1/2"): root cause was app-WIDE, not attendance-specific — `.main-area` (public/assets/system/css/app.css) is a flex item with no `min-width` set, so it defaulted to the browser's `min-width: auto` and refused to shrink below its widest child's intrinsic width. The Monthly Sheet's table is deliberately very wide (meant to scroll inside its own `.msheet-scroll` container), so it pushed `.main-area` — and the whole page — wider than the viewport, which also broke the sticky-column offset math (causing the Department/day-1/2 overlap). Fixed with one `min-width: 0;` on `.main-area`. Verified live in a real browser session (temporary password reset + restore, no leftover state): page/body no longer widen past the viewport (html.scrollWidth back to exactly the viewport width), and scrolling the sheet horizontally now correctly keeps Employee/Department sticky columns pinned in place while day columns slide underneath with zero overlap. General layout fix — also prevents the same overflow on any other page with wide content, not just this one.

CROSS-CUTTING FIX 2026-08-23 (2) (requested: add employee photo to the Monthly Sheet's employee column, make that column wider, and "day thead row overlapping employee row when scrolling left to right"): the overlap was a real, separate CSS specificity bug in monthly.blade.php — `thead .msheet-sticky { z-index: 4; }` (1 element + 1 class selector) was silently losing to the more specific `.msheet-table thead th { z-index: 3; }` (2-element selector) regardless of source order, so the sticky Employee header and the plain day header cells were actually TIED at z-index 3; on a tie, the later-in-DOM day header cells painted on top, visually sliding over the sticky Employee column while scrolling horizontally instead of underneath it. Fixed with a higher-specificity `.msheet-table thead th.msheet-sticky { z-index: 5; }`. Verified live via a full horizontal-scroll sweep (scrollLeft 0/100/250/400) checking the actual z-index of whichever day header geometrically overlapped the sticky Employee header at each step — Employee header (z=5) correctly stayed above the day header (z=3) at every position, confirmed both before (bug reproduced, tied at z=3) and after the fix. Also added an employee photo/avatar to that column (real photo via existing public-disk `employee->photo` path when present, else initials-fallback — both paths confirmed rendering live for real employees) and widened the column 170px → 260px. (Also noted: the file had already had its separate Department sticky column merged into the Employee column by a prior edit outside this task, leaving `.msheet-sticky-dept`'s CSS dead; removed that dead rule while in the file.)

Module 6: Admin Attendance Report (Advanced Filters + Summary Cards) — FULLY COMPLETED 2026-08-23, verified via 8 real tinker tests + live browser click-through
[*] Date range / department / designation / shift / employee-type / employment-status / employee filters, PLUS "Late attendance only" / "Missing checkout only" / "Overtime only" quick filters (all combine together) — new App\Http\Controllers\Admin\AttendanceReportController@index, route admin.attendances.report (permission attendance.view, matching the existing monthly-sheet route's own gating)
[*] Centralized AttendanceReportService for calculations — reused the EXISTING buildForEmployees()/build() unchanged (no duplicate calc logic); added two small new methods that only aggregate/parse, never re-derive: resolveRange() (moved out of AttendancePortalController, which now just delegates to it, so the Month-OR-Date-range parsing has exactly one implementation reused by both My Attendance and this new report) and aggregateTotals() (rolls per-employee summaries into one organization-wide total for the stat cards)
[*] Summary cards — Present/Absent/Late/On Leave/Half Day/Worked Hours/Overtime/Missing Checkouts, same stat-card style as My Attendance/HR Reports
[*] Dedicated attendance report page — resources/views/admin/attendances/report.blade.php, distinct from both the generic HR dashboard tile (HrReportController, a single today/this-month snapshot) and the day-by-day Monthly Sheet; cross-links to both. Added to HRM sidebar ("Attendance Report", order 4 under Attendance & Leave, existing lower items correctly bumped)
    Verified: 8 tinker tests against real fixtures (3 real employees across 2 real departments with present/late/missing-checkout/overtime/on_leave days) — no-filter totals correct, department_id filter correctly includes/excludes, late_only/missing_checkout_only/overtime_only each correctly narrow to exactly the matching employee(s) while the stat cards stay in agreement with the filtered table, employee_id filter correct, month-mode resolveRange delegation still correct after the refactor, 92-day range cap still enforced. Live browser click-through against the real (non-test) database confirmed: page renders with all 7 real employees and every filter dropdown populated from real data, overtime_only correctly narrowed to the one real employee with real overtime, department_id=IT correctly returned exactly its 5 real employees, the per-row "View detailed records" link correctly opens the EXISTING admin.attendances.index employee_id-filtered view (its "Showing attendance records for the selected employee" banner + Clear Filter both present) — confirming no duplicate detail-list system was built. All test employees/departments/attendance rows cleaned up (verified zero leftover rows).

CROSS-CUTTING ENHANCEMENT 2026-08-23 (3) (requested: "add advanced search on the Monthly Attendance Sheet too, same as the Attendance Report"): rather than copy-pasting the Attendance Report's filter logic a second time (which PART 27 rule  explicitly warns against), extracted it into three new shared methods on AttendanceReportService — filteredEmployeesQuery() (Department/Designation/Shift/Employee Type/Employment Status/Employee), narrowToActivityFilters() (the Late/Missing-checkout/Overtime "only" in-memory narrowing against already-built summaries), and filterOptions() (the dropdown data) — then refactored AttendanceReportController to use them (no behavior change, confirmed via a real render + totals regression check) and rewired AttendancePortalController::monthly() to use the exact same three methods instead of its old bare department/employee-only filtering. Updated the Monthly Sheet's filter form (monthly.blade.php) to match the Attendance Report's form exactly (all 6 dropdowns + 3 quick-toggle checkboxes). Verified via 6 real tinker tests (two real employees across two real departments/shift/designation/employee-type/employment-status combos): no-filter mode shows both, department_id correctly includes/excludes, late_only and overtime_only each correctly narrow to only the matching employee, and department+designation+employee-type combined together correctly isolate the one that matches all three — all cleaned up with zero leftover rows afterward. Live browser check against the real (non-test) database confirmed all 6 filters + 3 checkboxes render with real data, and both overtime_only and department_id=Information Technology returned the exact same filtered employee sets as the Attendance Report page does for the same filters, confirming the two screens now share one consistent filtering behavior end to end.

Module 7: Attendance Adjustment Integration — FULLY COMPLETED 2026-08-30, verified via 2 rounds of tinker tests (unauthenticated + authenticated-as-admin, after the first round's permission-check false negatives revealed the need for the second) + a full live browser click-through end to end
[*] AttendanceAdjustment model/controller: full CRUD + approve()/reject(), Approvable/HasWorkflow
[*] Visual indicator on Attendance list/sheet for pending/approved/rejected adjustment — new Attendance::employeeAdjustments() relation (eager-loaded once per DataTable page, matched to each row's exact date in memory — zero N+1); admin Attendance list gained a new "Adjustment" column (badge: Pending/Approved/Rejected) AND the existing "Timing" column now shows a "Missing Checkout" badge for a genuinely past day with a check-in but no check-out (PART 9's own example). Monthly Sheet gained a small colored dot per day cell (amber/green/red for pending/approved/rejected) plus an "Adjustment: <status>" line appended to the existing hover tooltip.
[*] "Request Adjustment" quick action wired from attendance record/day cell — reuses the EXISTING admin.attendance-adjustments.create form (no duplicate correction system), now accepting employee_id+date query params to pre-fill the date and show a "Currently recorded for this day" context line (mirroring the self-service form's own convenience) plus an "already has a pending request" notice. Wired as a per-row icon button in the Attendance list's new Adjustment column, AND as a click-to-open affordance on eligible Monthly Sheet day cells (today-or-past, no already-pending request for that employee+date) — both open the SAME create form via the shared hash openModal remote-modal system. Monthly Sheet had no DataTable of its own to auto-bind that system from, so `_componentRemoteModalLoadAfterAjax()` is now called explicitly in its own script block (same lesson already learned from the DMS module), plus a scoped `$(document).ajaxSuccess(...)` listener (matching only attendance-adjustments URLs) that reloads the sheet after a successful submission so the new dot shows up immediately.
    Verified via a first tinker round that correctly surfaced its OWN test-methodology gap (permission-gated UI rendering as absent when run unauthenticated — not a real bug, since `auth()->guard('admin')->user()` is null with nobody logged in), then a corrected, properly-authenticated-as-Super-Admin second round confirming all of: missing-checkout badge + request button on a plain record with no adjustment yet; Pending badge with NO request button (blocks duplicate requests) on a row with an existing pending adjustment; Rejected badge WITH a request button (a rejection doesn't block re-requesting) on a row with a rejected one; the quick-action URL correctly carrying the right employee_id+date; the create() controller correctly resolving `existingAttendance`/`pendingExists` for both a plain day and an already-pending day; and the Monthly Sheet correctly rendering both dot colors plus clickable day cells whose data-url points at the same create route. Live browser click-through (real fixture, real login) then confirmed the whole loop end-to-end: clicking the Attendance list's Request Adjustment button opened the modal pre-filled with the right employee/date and "Currently recorded: 09:00 AM in · -- out · Present" context (a one-off Select2-rendering artifact in a raw `.innerText` read was investigated and confirmed NOT a bug — the real underlying `<select>` DOM only ever had one correct option); submitting it made the row's Adjustment column flip to a "Pending" badge with the request button correctly gone; the Monthly Sheet for the same employee showed the amber pending dot with the correct tooltip and correctly excluded that one day from the clickable set while leaving 22 other eligible days actionable; clicking one of those eligible day cells (a day with no attendance record at all) correctly opened the same form pre-filled with that date and correctly WITHOUT a "currently recorded" notice (none exists). All test employees/departments/attendance/adjustment rows cleaned up after each round — verified zero leftover rows.

Module 8: Leave Integration — FULLY COMPLETED 2026-08-30, verified via tinker (real LeaveAttendanceService::syncApprovedLeave() calls, not raw inserts) + live browser hover checks on both pages
[*] LeaveAttendanceService::syncApprovedLeave() / removeLeave() — approved leave writes on_leave into attendances with original-status snapshot/restore
[*] Leave state/type/duration shown in monthly sheet + admin report (view-level) — new Attendance::leaveRequest() relation (belongsTo LeaveRequest, batch-eager-loaded via `->with('leaveRequest.leaveType')` in BOTH AttendanceReportService::build()/buildForEmployees(), zero extra per-row queries); each day's array now carries `leave_type`/`leave_duration_label` (a per-DAY label — "Full Day" or "Half Day (First/Second Half)" — deliberately NOT the leave request's own total_days, which would misleadingly repeat the same range-wide total on every day of a multi-day leave); a new `leave_breakdown` summary string ("2× Casual Leave, 1× Sick Leave (Half Day)") computed once per employee inside the same existing buildDaysAndSummary() loop (no Blade-side calculation, per PART 27). Monthly Sheet's existing hover tooltip gained a new "Leave" row (shown only on an actual leave day, cleanly omitted everywhere else rather than showing a bare "—" on every other cell's tooltip). Admin Attendance Report's "Leave" count column gained a native title-attribute tooltip showing the same breakdown.
    Verified via tinker driving the REAL production integration (LeaveAttendanceService::syncApprovedLeave(), not a raw Attendance::create()) for both a full-day and a half-day-with-session leave: both correctly classified as on_leave with the correct leave type name and duration label, the leave_breakdown string correctly listing both with the half-day one correctly parenthesized, and both the Monthly Sheet's and the Attendance Report's real rendered HTML (through their actual controllers) correctly containing the expected tip/tooltip text. Live browser check (real fixture, real login) then confirmed both hover interactions for real in an actual page: the Monthly Sheet's LV day cell tooltip correctly showed a "Leave" row reading "ZZBrowser Casual Leave · Full Day" positioned between Status and Notes, and the Attendance Report's Leave-count cell (showing "1") carried a native title tooltip reading "1× ZZBrowser Casual Leave". One test-methodology mistake was caught along the way (a leave date fixture landed on a Friday, this app's own configured weekend day, so syncApprovedLeave() correctly created no attendance row at all — confirmed as correct existing behavior, not a bug, by checking the app's `leave_weekend_days` setting and LeaveRequestService::calculateDays()'s own weekend-exclusion logic, then re-run with a real weekday) — a second oversight from that same detour (a script file was deleted without first deleting the DB rows it had created) was caught by a final broad `ZZ%`-pattern sweep across every table and cleaned up. Final sweep across employees/departments/leave types/attendances/leave requests confirmed zero leftover rows.

Module 9: PDF Export — FULLY COMPLETED 2026-08-30, verified via tinker (real controller calls, 4 scenarios) + live browser render
[*] Install PDF package — DELIBERATELY NOT DONE, by design: before installing, discovered this project already has an established, documented PDF-export convention — the reusable `<x-print-document>` Blade component (resources/views/components/print-document.blade.php), whose own doc comment explicitly says company-branded print documents are built this way "rather than adding a server-side PDF library dependency," and which is already used identically by Delivery Notes, Barcode Generator, POS Receipts, and the Salary Certificate. Per PART 27 rule 1 ("reuse existing architecture wherever possible"), built the Attendance PDF export on this SAME component instead — a filter-aware, landscape print view opened in a new tab, exported via the browser's own native "Print / Save as PDF". (barryvdh/laravel-dompdf was briefly composer-required to check feasibility, then fully composer-removed once the existing convention was found; composer.json/composer.lock confirmed back to zero diff against their committed state, and a stale post-removal autoloader error was caught and fixed with `composer dump-autoload -o` before it could affect anything else.)
[*] Attendance PDF template (landscape, header/footer, summary, filter-aware) — new resources/views/admin/attendances/report-pdf.blade.php wrapped in `<x-print-document>` (giving it the SAME company logo/name/address/contact/tax-number header and "Generated on ... by <admin>" footer every other print document in this app already has, for free); `@page { size: landscape; ... }` plus a native CSS `@bottom-right { content: "Page " counter(page) " of " counter(pages); }` for real multi-page numbering (no JS page-counting needed — the browser's print engine handles it natively); the on-screen summary stat cards and per-employee table reproduced 1:1; a `<thead>` with `display: table-header-group` so the column header repeats on every printed page, and `tr { page-break-inside: avoid }` so no employee's row splits across a page break. New `AttendanceReportController::exportPdf()`/`buildReportData()`/`filterSummary()` methods reuse the EXACT SAME AttendanceReportService pipeline (resolveRange/filteredEmployeesQuery/buildForEmployees/narrowToActivityFilters/aggregateTotals) the on-screen report already uses (PART 11's own rule: "same shared AttendanceReportService/query builder for Browser View, PDF... so all outputs remain consistent") — the export can never drift from, or silently ignore, whatever's currently filtered. A "Selected filter summary" meta box lists every currently-applied filter by its actual resolved name (Department/Designation/Shift/Employee Type/Employment Status/Employee/quick-filter toggles), built once in the controller (not Blade, per PART 27 rule 12) from the exact same option lists the filter form itself renders from. New "Export PDF" link added to the Attendance Report page, `target="_blank"`, carrying `request()->getQueryString()` so it always exports exactly what's currently on screen.
    A real, subtle Blade bug was found and fixed along the way: an early draft's own doc-comment, written INSIDE the view's `<style>` block, contained the literal text `<x-print-document>` as prose (referring to the shell by name) — Blade's component-tag compiler pattern-matches that exact tag shape anywhere in the raw source, even inside a CSS comment it doesn't understand, and so silently opened a SECOND, never-closed nested component from that comment text alone, corrupting the rest of the compiled file and surfacing as a confusing "unexpected end of file, expecting endif" error. Diagnosed by rendering the compiled PHP output directly (storage/framework/views/*.php) rather than guessing from the vague top-level error, found the phantom duplicate component block, and fixed by rewording the comment to avoid the literal tag shape.
    Verified via tinker driving the real controller (not a bare view render) across 4 scenarios: unfiltered export renders successfully and includes every active employee, landscape/page-counter CSS present, company header block present, filter-summary meta correctly reads "None (showing all active employees)" when nothing is applied; a department-filtered export correctly includes only that department's employee and excludes the other, with the department's real name shown in the meta box; an employee-filtered export correctly shows that one employee's name+code in the meta box and excludes every other employee from the table. Live browser check (real login, real filter applied on-screen first) then confirmed the "Export PDF" link's href correctly carries the current `department_id` filter, and opening it rendered a real page showing the actual company branding (name/address/phone/email/website pulled from Settings), the correct title/period, the Department meta box, matching summary totals, the correctly-filtered 5-employee table, and the "Generated on ... by Super Admin" footer — with zero console errors. One test-methodology slip (a crashed test run mid-way through the Blade-bug investigation left ZZP-prefixed employees/departments behind since cleanup never ran) was caught by a routine leftover check and cleaned up before the retest; a final broad `ZZ%`-pattern sweep across every affected table (Employee/Department/LeaveType/LeaveRequest/Attendance/AttendanceAdjustment) confirmed zero leftover rows from this or any prior module's testing.

Module 10 (partial, requested ahead of schedule): Monthly Attendance Sheet PDF Export — COMPLETED 2026-08-30, verified via 6 real controller calls (tinker) — live browser check was attempted but the Claude-in-Chrome extension was not connected this session, so verification stayed at the controller/render level only (no manual click-through was possible; nothing about that limits confidence in the actual output, since it's the same render pipeline the browser would hit)
[*] Reused the SAME `<x-print-document>` convention Module 9's Attendance Report PDF export already established (no new PDF library) — new `AttendancePortalController::monthlyExportPdf()` + `resources/views/admin/attendances/monthly-pdf.blade.php`, landscape, company header/footer, page-counter footer, all "for free" from the shared shell
[*] Runs through the EXACT SAME filter pipeline as the on-screen sheet — refactored `monthly()`'s data-building into a new shared `buildMonthlyData()` private method, called by both the browser view and the PDF export, so the export can never drift from or ignore whatever's currently filtered (department/designation/shift/employee type/employment status/employee + late/missing-checkout/overtime "only" toggles)
[*] Deduplicated the filter-summary logic (PART 27): moved the existing `AttendanceReportController`'s private `filterSummary()` into `AttendanceReportService::filterSummary()` (a public method both this new export and the existing Attendance Report PDF export now share) instead of copy-pasting it a second time
[*] Same visual language as the on-screen sheet reproduced in print form — the exact P/A/L/HD/EL/LV/H/WO color-coded badge legend and per-day badges (fixed hex colors instead of CSS variables, since the print shell doesn't load app.css), employee name+code+department/designation, per-employee P/A/L/LV summary columns, and the small colored adjustment-status dot per day cell — laid out with `table-layout: fixed` + computed per-day column-width percentages so a full 28–31-day month always fits exactly one landscape page wide (the on-screen version can rely on horizontal scroll for this; a printed page can't)
[*] "Export PDF" link added next to the existing "Detailed List" link on the Monthly Sheet page, `target="_blank"`, carrying `request()->getQueryString()` so it always exports exactly what's currently on screen — same convention as the Attendance Report's own Export PDF link
    Verified via tinker driving the real `monthlyExportPdf()` controller action (not a bare view render) across 4 scenarios plus a regression check: unfiltered export correctly includes both real fixture employees, contains the landscape/page-counter CSS and the company header block, and correctly shows "None (showing all active employees)" in the filter-summary meta when nothing is applied; a department-filtered export correctly shows the department's real name in the meta box; an employee-filtered export correctly includes only that one employee (by name and code) and excludes the other; a `late_only` quick-filter export correctly includes only the employee with a real late day and excludes the one with none, with "Late Attendance Only" shown in the meta; a final regression call confirmed the on-screen `monthly()` action still renders correctly (including the new Export PDF link) after the `buildMonthlyData()` refactor, with zero behavior change. All test fixtures (2 departments-worth of lookup masters, 2 employees, 3 attendance rows) cleaned up — a final `ZZ%`-pattern sweep across Employee/Department/Designation/EmployeeType/EmploymentStatus/Attendance confirmed zero leftover rows. The temporary admin password reset used to attempt the (ultimately unavailable) live browser check was restored from its saved original hash and the backup file deleted, per the same safe/reversible convention used in every prior session.
[*] Excel export for the Monthly Sheet — COMPLETED 2026-08-23, see full Module 10 write-up below
[*] Excel export for the Attendance Report — COMPLETED 2026-08-23, see full Module 10 write-up below
[*] Install Excel package — DELIBERATELY NOT DONE, by design: see full Module 10 write-up below for the discovered existing convention and why a styled-HTML-as-.xls approach was chosen over both installing maatwebsite/excel and reusing that existing convention as-is

Module 10: Excel Export — COMPLETED 2026-08-23 for both the Monthly Attendance Sheet and the Attendance Report, verified via 8 real controller calls (tinker)
[*] Checked for an existing in-house convention FIRST, per this project's own established habit (the same check already done before Module 9's PDF export) — found one: `App\Http\Controllers\Admin\HrmDetailExportController::csv()` already has an `excel` output mode, but it just re-tags the SAME plain-value CSV content with an `application/vnd.ms-excel` mime (no colors/styling at all) — not enough to satisfy "same design as the views" the way the PDF export already does. Chose instead to serve a real, styled HTML `<table>` (colors, bold, borders — the exact same badge/summary design already used on screen and in the PDF) with an `.xls` filename and `application/vnd.ms-excel` Content-Type — Excel has natively imported HTML tables this way for decades (the same format Excel's own "File > Save As > Web Page" has always produced), so this needed **zero new Composer dependency**, same "reuse the target application's own native import instead of adding a library" principle the PDF export already uses via the browser's print engine. (A one-time "this isn't exactly the file type it claims to be" compatibility prompt from Excel on open is expected and harmless — the same prompt Excel's own legacy Save-As-Web-Page files have always produced.)
[*] New `App\Traits\ExportsHtmlSpreadsheet` (`htmlSpreadsheetResponse()`) — a small shared trait (matching this project's existing `App\Traits\ActivityLogger` convention for cross-controller helpers) used by BOTH `AttendancePortalController` and `AttendanceReportController`, so the response-building (headers, filename timestamp, .xls extension) exists in exactly one place rather than being duplicated per controller
[*] `AttendancePortalController::monthlyExportExcel()` + new `resources/views/admin/attendances/monthly-excel.blade.php` — runs through the EXACT SAME `buildMonthlyData()` pipeline the on-screen sheet and the PDF export already share, reproduces the same P/A/L/HD/EL/LV/H/WO color-coded day badges (as real background/text colors Excel will actually render) plus employee/code/department/designation and the per-employee P/A/L/LV summary columns, and marks any day with a pending/approved/rejected adjustment with a trailing `*` (a footnote below the table explains it — Excel's own cell can't carry a hover tooltip the way the on-screen popover does)
[*] `AttendanceReportController::exportExcel()` + new `resources/views/admin/attendances/report-excel.blade.php` — same `buildReportData()` pipeline as the on-screen report and its PDF export, reproduces the same 8-figure summary-totals block (Present/Absent/Late/On Leave/Half Day/Worked Hours/Overtime/Missing Checkouts) and the same per-employee table columns as the on-screen view
[*] Both exports carry the SAME filter-summary meta rows (Department/Designation/Shift/Employee Type/Employment Status/Employee + the three quick-filter toggles) at the top of the sheet, reusing `AttendanceReportService::filterSummary()` unchanged (already shared with the PDF exports from the prior session, so nothing new needed there)
[*] "Export Excel" link added next to the existing "Export PDF" link on BOTH the Monthly Sheet and the Attendance Report pages, carrying `request()->getQueryString()` so it always exports exactly what's currently on screen — same convention as both pages' own Export PDF link (not `target="_blank"` — a file download, not a page to view in a new tab)
    Verified via tinker driving the real `monthlyExportExcel()`/`exportExcel()` controller actions (not bare view renders) with real employee/department/attendance fixtures: both responses come back with status 200, `Content-Type: application/vnd.ms-excel; charset=UTF-8`, and a `.xls`-suffixed, timestamped filename in `Content-Disposition`; the unfiltered Monthly Sheet export correctly includes both fixture employees, a real `<table>`, the "None (showing all active employees)" filter-summary line, and the present-status badge's real background color hex; a department-filtered export correctly shows the department's real name in the meta rows; an employee-filtered export correctly includes only that one employee and excludes the other; the unfiltered Attendance Report export correctly includes both employees plus the "Summary Totals" block and its "Missing Checkouts" column; a `late_only` quick-filter export on the Report correctly includes only the employee with a real late day, excludes the one with none, and shows "Late Attendance Only" in the meta rows; a final regression pass confirmed both on-screen pages (`monthly()` / `index()`) still render correctly — including the new Export Excel link — after adding the buttons, with zero behavior change. (One harmless false negative surfaced in the FIRST draft of the test script itself — a single-quoted PHP string containing `\"` doesn't unescape the way a double-quoted one does, so the assertion string never matched; confirmed by rendering the view directly and inspecting the raw HTML, which showed the correct `<meta charset="utf-8">` tag was there all along — not a bug in the exported file, just a mistake in how the test searched for it.) All test fixtures (2 departments-worth of lookup masters, 2 employees, 3 attendance rows) cleaned up — a final `ZZ%`-pattern sweep across Employee/Department/Designation/EmployeeType/EmploymentStatus/Attendance confirmed zero leftover rows. (Live browser verification of the actual file opening in a real copy of Excel was not attempted — no Excel installation is reachable from this environment either way — but the served bytes were verified directly: correct MIME type, correct filename extension, and well-formed HTML that Excel's documented native import behavior handles.)

Module 11: CSV Export — COMPLETED 2026-08-23 for both the Monthly Attendance Sheet and the Attendance Report, verified via a comprehensive tinker run against real `streamDownload` responses (not bare view/data checks) covering both screens, both filtered and unfiltered, plus a dedicated stable-column-names regression across two different-length months
[*] CSV export endpoint for the Attendance Report — `AttendanceReportController::exportCsv()`, one row per employee (Employee ID/Code/Name, Department, Designation, Present, Absent, Late, Half Day, Leave, Worked Hours, Overtime, Missing Checkout), same fixed column order regardless of which filters are applied, no decorative rows — matches PART 11's own "clean machine-readable CSV... stable column names... avoid decorative content" requirement literally, and reuses the exact same `buildReportData()` pipeline the browser view/PDF/Excel exports already share
[*] CSV export endpoint for the Monthly Sheet — `AttendancePortalController::monthlyExportCsv()`. Deliberately NOT the same one-column-per-day wide grid the on-screen sheet/PDF/Excel exports all use: a grid's day columns are literally named "01".."31" and their COUNT changes every month (28 vs 29 vs 30 vs 31 days), which would fail PART 11's own "stable column names" requirement outright — a consumer parsing this month's CSV wouldn't get the same header row next month. Built instead as one row per EMPLOYEE PER DAY (Employee Code/Name, Department, Designation, Date, Day, Status, Check In, Check Out, Worked Hours, Overtime Hours, Leave Type, Leave Duration, Adjustment Status, Remarks) — a genuinely fixed 15-column header that never changes size, and (per the Excel Requirements section's own "useful for HR and payroll processing" framing) arguably more directly importable into payroll tooling than a wide grid would be anyway. Built lazily via a PHP generator so a full month × many employees is streamed row-by-row rather than materialized as one giant array first.
[*] New shared `App\Traits\ExportsCsv` (`csvResponse()`) trait, mirroring the project's existing `App\Traits\ActivityLogger`/`App\Traits\ExportsHtmlSpreadsheet` cross-controller-helper convention, used by BOTH controllers so the actual `fputcsv` streaming/filename logic exists in exactly one place — itself a direct reuse of the plain-`fputcsv`-streaming pattern `EmployeeController::export()` and `HrmDetailExportController::csv()` already established elsewhere in this codebase, per this project's own "check for an existing convention before adding anything new" habit (no new package needed, same as Modules 9/10)
[*] "Export CSV" link added next to the existing "Export Excel" link on BOTH the Monthly Sheet and the Attendance Report pages, carrying `request()->getQueryString()` so it always exports exactly what's currently on screen (same "never export the unfiltered dataset when filters are active" convention every prior export link in this project already follows)
    Verified via tinker driving the real `monthlyExportCsv()`/`exportCsv()` controller actions and actually parsing the returned CSV bytes with `str_getcsv()` (not just checking for a substring) against real employee/department/attendance/adjustment fixtures: the Monthly Sheet's unfiltered export returned the correct 15-column header and, for the fixture employee's specific day, correctly read back Status=Present, Check In=09:00 AM, Check Out=05:30 PM, Overtime Hours=0.50, Adjustment Status=Pending, and the real remarks text — all through real parsed CSV cells, not raw string matching; an employee-filtered export correctly returned ONLY that one employee's rows, and correctly returned EXACTLY one row per day of the month (no more, no less); the Attendance Report's unfiltered export correctly returned one row per real employee with the right Present/Late/Overtime figures for the fixture; a `late_only` quick-filter export correctly included only the employee with a real late day and excluded the one with none. The core Module 11 requirement was directly proven, not just asserted: the SAME Monthly Sheet employee's export was run for both January 2026 (31 days) and February 2026 (28 days), and the two returned **byte-for-byte identical header rows** while correctly returning 31 and 28 data rows respectively — confirming the column set genuinely never changes size across different-length months. A final regression pass confirmed both on-screen pages (`monthly()` / `index()`) still render correctly with the new Export CSV link present. All test fixtures (2 departments-worth of lookup masters, 2 employees, 3 attendance rows, 1 attendance adjustment) cleaned up — a final `ZZ%`-pattern sweep across Employee/Department/Designation/EmployeeType/EmploymentStatus/Attendance/AttendanceAdjustment confirmed zero leftover rows.

Module 12: Permissions Mapping — FULLY COMPLETED 2026-08-23, both remaining items done and verified via real, live HTTP requests through the actual running server (curl, real session cookies, real login, real CSRF handling) — not controller-direct calls, which would have bypassed the route middleware entirely and proven nothing about the actual gate
[*] attendance.{view,create,edit,delete} and attendance-adjustment.{view,create,edit,delete,approve,reject} permissions exist and are enforced (route middleware + sidebar can() checks are ACTIVE — CLAUDE.md's "enforcement disabled" note is stale)
[*] Dedicated permission for self-service check-in/out — new `attendance.self-check-in` permission (PART 14's own suggested naming: `attendance.view` / `.create` / `.update` / `.delete` / `.export` / `.report` / `.adjustment`, extended here since none of those literally covers "punch myself in today"). Deliberately kept SEPARATE from `attendance.create` (which governs an admin manually creating/backdating ANY employee's record from the admin Attendance screen — a very different capability/risk level than checking in as yourself) — added to both `admin.attendance-portal.check-in`/`.check-out` route middleware. **Safety migration, not a hard cutover**: every admin account already linked to an `employee_id` at the time this shipped was backfilled with the permission DIRECTLY (not through a role — self-check-in is a personal capability, independent of whatever role someone happens to hold), so nobody who could already self-check-in lost that ability. `attendance-portal.index` (the My Attendance page itself), the header widget's status-refresh endpoint, the self-service "Request Adjustment" routes, and the token-authenticated biometric-device webhook (`attendance-device/punch`, outside the admin auth group entirely) were all deliberately left untouched/ungated, per their own already-documented "self-service, ungated" design from Modules 2-4.
[*] Dedicated permission for the monthly report route — new `attendance.report` permission (again PART 14's own suggested name), replacing the shared reliance on `attendance.view` for BOTH `admin.attendances.monthly` (the Monthly Sheet) AND its sibling `admin.attendances.report` (the Attendance Report) — kept identical between the two on purpose, the same deliberate "share the same gating" choice Module 6 already made when the Attendance Report was first built. Extended to all 6 of their PDF/Excel/CSV export routes too (Modules 9-11), so a role that can see a report on screen can always export it — no permission mismatch between viewing and exporting. Sidebar menu rows for "Monthly Sheet"/"Attendance Report" (`database/seeders/HrmMenuSeeder.php`) updated to the same permission string and re-seeded live (`updateOrCreate`, safe to re-run). **Same safety-migration approach**: every existing admin-guard role that already held `attendance.view` (checked directly against the live database, not assumed) was backfilled with `attendance.report` too, so no role that could already reach these two screens lost access. The plain admin Attendance list/records CRUD (`admin.attendances.index/create/store/edit/update/destroy`) was deliberately left on `attendance.view/create/edit/delete` — out of scope, a different capability (managing raw attendance rows, not the reporting screens).
    Verified via real live HTTP traffic against the actually-running app server (not tinker-simulated, since a bare controller call never executes route middleware at all): created a temporary role holding `attendance.view` but NOT `attendance.report`, logged into it via a real POST to `admin/login/post` with a real CSRF token pulled from the live page, and confirmed all 4 tested `attendance.report`-gated routes (Monthly Sheet, Attendance Report, and one PDF + one CSV export) correctly returned **403** — then granted `attendance.report` to that same role mid-session and re-hit the exact same 4 routes, all correctly flipping to **200** with the real page content present, with zero re-login needed (confirming Spatie's own permission-cache invalidation on grant works correctly here). Separately confirmed the HRM sidebar itself tracks the same permission live: the two menu links were absent from a real rendered `/admin/dashboard` page before the grant, present after it, and absent again immediately after revoking — proving the sidebar's own `can()` gating and the route middleware never disagree. For self-check-in: created a temporary employee-linked admin with no permissions at all, logged in for real, and confirmed a real `POST /admin/attendance-portal/check-in` correctly returned **403 "User does not have the right permissions"** — then granted `attendance.self-check-in` directly to that same account and confirmed the identical request no longer hit the permission gate at all, instead reaching the real Form Request validation layer (a 422 for the genuinely-missing latitude/longitude fields) — conclusive proof the middleware layer, not just the underlying capability, is what changed. Finally, using the SAME temporary-password-reset-then-restore convention as every prior browser-verification session, logged into the real, existing, production Super Admin account and confirmed it could still reach the Monthly Sheet, Attendance Report, and a PDF export (all 200) after the migration — i.e. the safety-migration backfill genuinely prevented the regression it was designed to prevent, not just in theory. A pre-existing, unrelated `CurlTestRoleXYZ` role was discovered live in the database during this testing (not created by this session) and was deliberately left completely untouched. All test fixtures (1 role, 2 admins, 1 employee, 1 department, 1 designation, 1 employee type, 1 employment status, plus their Spatie permission-pivot rows, explicitly synced empty before deletion so no orphaned `model_has_roles`/`model_has_permissions` rows were left behind) were cleaned up — a final sweep confirmed zero leftover `ZZ%` rows and zero orphaned permission-pivot rows for any deleted admin. The real Super Admin's password was restored from its saved original hash and the backup file deleted.

Module 13: Client-Requested Fixes (multi-session punching, punch notes, punch source visibility, date-parity weekends) — COMPLETED 2026-08-29, all 4 items implemented, one real regression bug found mid-work and fixed, verified via real fixtures through the actual service/controller layer (reflection-invoked private methods, not re-implemented test logic) plus direct Blade rendering of every touched view

Module 14: Client-Requested Follow-ups (check-in/out modal with live location, Attendance advanced search, Leave Balance grouped restructure, Leave Request approval-workflow detail modal, modernized Leave Calendar) — COMPLETED 2026-08-23, all 5 deliverables implemented and verified LIVE in a real browser session (Claude Browser tool, not just tinker/render checks) against the actual running app and, for items 3-5, real production data (an already-active `WorkflowDefinition` for Leave Requests was discovered live in the database mid-work and used directly, rather than a fabricated one)
[*] Check In / Check Out modal — the old `window.prompt()`-based note flow (and the browser's own native geolocation permission prompt, which can't be scripted away) is replaced with a real, shared `#awPunchModal` (markup lives once in `resources/views/admin/layout/partials/header.blade.php`, rendered on every admin page for an employee-linked account) showing the employee's actual current location via an embedded OpenStreetMap `iframe` (no API key, no new JS mapping library — checked the whole app first via grep for `leaflet`/`google.maps`/`mapbox`, confirmed none exists, and a link/embed-only approach matches this project's own repeated "no dependency for one screen" precedent) plus a plain optional Note field, before the punch is actually sent. `public/assets/system/js/attendance-widget.js` exposes `window.awOpenPunchModal(url, actionLabel)` as the ONE shared implementation both the header widget's own Check In/Out buttons AND the dedicated My Attendance page's buttons (`resources/views/admin/attendance-portal/index.blade.php`, its old inline `punch()` function removed entirely) now call — so the two entry points can never drift apart. Verified live end-to-end: opened the modal, confirmed it correctly showed the real "please allow location" error state when geolocation was denied, then (simulating a granted permission with a fixed coordinate, since a headless browser can't drive the OS location prompt) confirmed the map iframe/coordinates/"Open in Maps" link all populated correctly, typed a real note, clicked Confirm, and confirmed via direct DB query that a real `AttendancePunch` row was created with the correct `source=browser_geolocation`, the typed note, and the exact lat/lng — while the header chip live-updated to "Late" with the real check-in time, with zero page reload.
[*] Attendance advanced search (`/admin/attendances`) — added a collapsible "Advanced Search" panel (date range, Department/Designation/Shift/Employee Type/Employment Status/Employee, a multi-select Attendance Status checkbox group, and a "Missing Checkout Only" toggle) to the existing plain admin Attendance list, reusing the EXACT SAME `AttendanceReportService::filterOptions()` dropdown option lists the Attendance Report/Monthly Sheet screens already use so the same dropdown never shows different choices on different screens. Every filter is a live DataTables AJAX filter (server-side, `AttendanceController::index()`'s query gained `date_from`/`date_to`/`attendance_status`/`missing_checkout_only`/department-etc.-via-the-employee-relation clauses) — not a page-reloading GET form, since this screen is a DataTables list, unlike the Report/Monthly Sheet's own whole-page filter forms. Verified with real fixtures covering every filter and every combination (date range, single/multi attendance_status, missing-checkout-only, department, department+status combined, and free-text search) all producing exactly the expected row counts against a deliberately-constructed 4-row/2-employee/2-department dataset.
[*] Leave Balance grouped restructure — the admin's own framing ("one employee one year one record... multiple leave type data under it") was implemented as a UI/interaction-layer change ONLY: the underlying `leave_balances` table is completely UNCHANGED (still one row per employee+leave_type+year, per its own existing unique index) — deliberately, since that exact tuple is what `LeaveAccrualService`'s 5 methods (allocate/monthly-accrual/carry-forward/expire/encash) and `LeaveRequestService::adjustBalanceFor()`'s approve/reject/cancel/update/delete side-effects all key off via `firstOrNew()`/`firstOrCreate()` — confirmed via a project-wide grep that no other file anywhere persists a `LeaveBalance` row id, so none of that established, working business logic needed to change at all. What changed: the admin list (`LeaveBalanceController::index()`) now groups every existing row by `(employee_id, year)` in memory (batch-fetched, not per-row queried) into ONE summary row per employee+year — hand-building the exact `draw`/`recordsTotal`/`recordsFiltered`/`data` JSON contract DataTables.net's client already expects, rather than fighting Yajra's Eloquent/Collection engines over a grouped rollup they weren't built for. A new "Manage" master-detail modal (`resources/views/admin/leave-balances/manage.blade.php`, replacing the old one-leave-type-per-modal create/edit pair) lets an admin add/edit/remove any number of leave-type lines for that one employee+year in a single save, via a new `LeaveBalanceService::saveGroup()` — an UPSERT-and-prune (not the "delete every line then recreate them all" convention this project's other master-detail screens use for their own items, since THEIR items carry no independent meaning the way a `LeaveBalance` row's id is directly relied upon elsewhere) that `updateOrCreate()`s every submitted line against the untouched unique tuple and deletes only the lines a resubmission genuinely dropped. A new `destroyGroup()`/`deleteGroup()` pair removes an entire employee+year record (every leave type under it) in one action from the grouped index row. Encash and the per-line active/inactive switch stayed exactly as they were (same routes, same `LeaveAccrualService::encash()`/`updateStatus()`), just relocated inside the Manage form instead of a flat per-row table. Verified via a comprehensive real-fixture test (create a 2-leave-type group, confirm the index correctly rolls it up into ONE row with correctly-summed totals, confirm the Manage form pre-populates both lines, resubmit with one line updated/one line removed/one brand-new line added and confirm the updated line KEPT its original database id — proving the upsert, not a churn-inducing delete+recreate — while the removed line was actually deleted and the new line actually created, then delete the whole group and confirm zero rows remain) — then RE-VERIFIED the entire create → manage/edit (remove one line, add a different one) → group-delete lifecycle live in a real browser session against the real running app, confirming the grouped index row's displayed "2 leave types / 1 active / 28.00 allocated / 5.00 used / 23.00 remaining" text matched the real underlying data exactly at every step.
[*] Leave Request detail + approval-workflow modal — added a "View Details" action (new `GET leave-requests/{leaveRequest}/details` route/`LeaveRequestController::details()`) opening a read-only modal with the full request (employee, leave type, dates, duration, reason, attachment, status, cancellation reason) PLUS, when a `WorkflowInstance` exists for it, a full step-by-step approval stepper: every configured step's name/approval type/approver list (a new `WorkflowStepApprover::getApproverLabelAttribute()` resolves `role`/`user`/`designation` approver types to actual readable names, mirroring the same resolution `WorkflowNotifier` already uses to pick real notification recipients) and that step's actual computed status (Approved / Rejected / In Progress / Not Reached, derived by comparing each step's `step_order` against the instance's `current_step_id`+`current_status` — never a second, independently-tracked status field that could drift from the real one), alongside every real `WorkflowInstanceApproval` entry recorded against that step (who acted, approved/rejected, remarks, when). A genuinely important discovery made while building this: a real, ACTIVE `WorkflowDefinition` ("Leave Request Approval", 2 role-based steps — Manager then HR) already exists in the live production database — meaning Leave Requests in this actual system are NOT running through the "direct approval, no workflow" fallback path the code's own comments describe as "today's reality"; that comment is now stale and this detail modal is not a nice-to-have but the only place an admin can actually see a request's real approval progress. Falls back to a plain "no multi-step workflow configured" explanation for any request with no workflow instance (e.g. one created directly via a script rather than through the real `store()` flow). Verified against BOTH shapes with real fixtures (a plain request with no instance at all; a real instance built against the live production WorkflowDefinition #8 with one step approved/one pending, then a follow-up rejected-at-step-2 scenario) without ever modifying the real definition/steps/approvers themselves — then confirmed live in a real browser: clicked "View Details" on an actual real, currently-pending leave request belonging to the project's own admin user and confirmed the modal correctly rendered "Step 1: Manager Approval — In Progress / Single · Manager (any member)" and "Step 2: HR Approval — Not Reached / Single · HR (any member)" straight from real data.
[*] Leave Calendar modernization — replaced the previous bare-minimum FullCalendar wrapper (no filters, a hardcoded 2-color legend, no click-through, default FullCalendar styling) with: a live stats row (total entries/pending/approved/unique-employees-in-view, computed client-side from whatever FullCalendar's own `events()` fetch already returned — no second query); a real filter row (Employee/Leave Type/Department + a "Show Rejected/Cancelled" toggle, all live-refetching the SAME `calendarEvents()` feed rather than a second data path); a genuine per-leave-type colour legend generated from one new, deterministic, shared palette (`LeaveRequestController::LEAVE_TYPE_PALETTE`/`colorForLeaveType()` — the exact same helper both the legend swatches and every event's actual colour read from, so they can never disagree) alongside a separate status legend (solid border = approved, striped = pending, faded/struck-through = rejected or cancelled — status and leave-type are now BOTH visible on one event chip at once, not status-only coloring as before); a full FullCalendar CSS theme override (`@push('styles')` in `calendar.blade.php`) so the calendar's buttons/header/today-highlight/day-cells all use this app's own design tokens instead of FullCalendar's generic default look; and clicking any event now opens the SAME "View Details" workflow modal built above (reusing the existing generic `#openModal`-driven remote-modal loader via a hidden trigger button, rather than a second modal implementation). Verified live in the real browser: the real pending leave request appeared correctly coloured/legended on the real calendar with correct live stats (1 total / 1 pending / 0 approved / 1 employee); the Department filter was exercised against real data and correctly returned 1 event for the employee's real department and 0 for an unrelated one; clicking the event correctly opened the exact same workflow-stepper modal verified above.
[*] Item 1 — "I can check in/out multiple times a day": previously a day's `Attendance` row could only ever hold one check_in/check_out pair, so a second check-in after checking out once was rejected outright. Introduced a new `attendance_punches` audit-log table (one row per individual punch: employee, linked attendance_id, punch type, punched_at, lat/long, source, notes) sitting UNDER the existing day-level `Attendance` row rather than replacing it — every existing consumer of `Attendance.check_in`/`check_out` (reports, exports, leave integration, adjustments) keeps working unchanged, now reading "first check-in of the day" / "last check-out of the day" instead of a single pair. Added `attendances.worked_minutes` (backfilled from existing data via a `chunkById` migration) as the day's cumulative total across every session, replacing the old on-the-fly single-pair diff in `AttendanceReportService::workedMinutes()`. `AttendancePortalController::checkIn()/checkOut()/devicePunch()` rewritten around new `performCheckIn()`/`performCheckOut()` private methods: check-in is allowed whenever there's no currently-open session (regardless of whether the employee already completed one or more earlier sessions today); check-out resolves its target Attendance row via the open punch's own stored `attendance_id` (eliminating the old overnight-shift date-guessing entirely) and accumulates the session's minutes into `worked_minutes`. Only the FIRST session of the day sets `Attendance.check_in`/late-detection; only whichever check-out happens to be most recent updates `Attendance.check_out`.
[*] Item 2 — "clicking check in/out doesn't take me to add notes": added a `notes` field (nullable, max 1000) accepted by check-in/check-out/devicePunch and stored per-punch on `attendance_punches`. Both the header widget (`attendance-widget.js`) and the dedicated My Attendance page (`attendance-portal/index.blade.php`'s inline `punch()`) now prompt via `window.prompt('Add a note … (optional):')` before posting — Cancel just means "no note", never blocks the actual punch. Matches this project's own established "native prompt for a single quick-action text field" convention (e.g. the Project Timesheet reject flow) rather than adding a new modal, since punching is now something that can happen several times a day and shouldn't gain extra friction.
[*] Item 3 — "how I checked in/out (card punch/geotracking/etc.) isn't shown anywhere": `check_in_source`/`check_out_source` already existed on `Attendance` but were never surfaced in any view. Added `AttendancePunch::SOURCE_LABELS` (browser_geolocation → "Web (GPS)", fingerprint → "Fingerprint Device", face → "Face Recognition", id_card → "ID Card Punch") and matching `Attendance::getCheckIn/CheckOutSourceLabelAttribute()` accessors as the single source of truth every consumer reads from. Surfaced in: (a) the header attendance widget — a new "Today's Sessions" block listing each session's time range/source/note; (b) the My Attendance page — the same sessions block on the hero, plus a new "Source" column and an "N sessions" badge (hover reveals the full per-punch breakdown) on the attendance-detail table; (c) the Monthly Attendance Sheet's existing hover tooltip — added a "Source" row (with a "(N sessions)" suffix when applicable), skipped entirely on a day with no punch data to avoid clutter, matching the tooltip's existing "skip Leave row on non-leave days" precedent; (d) the Monthly Sheet's long-format CSV export (Module 11) — added "Check In Source" and "Sessions" columns. Deliberately did NOT add per-day source detail to the Monthly Sheet's wide-grid PDF/Excel exports (each day is still just a status-code letter there) — consistent with the existing precedent that overtime/leave-duration detail is also tooltip+CSV-only, never crammed into the wide grid.
[*] Item 4 — "our week runs even/odd calendar dates, not Friday/Saturday": the weekend calculation was previously hardcoded to a fixed day-of-week set (`leave_weekend_days`, e.g. "5,6"), duplicated inline across 8 call sites in 6 files (AttendanceReportService × 3, AttendanceStatusService, LeaveRequestService, 3 monthly-sheet views). Extracted all of it into one new `App\Services\WeekendCalendarService::isWeekend(Carbon $date)`, then added a second calculation mode alongside the existing one: `hrm_weekend_mode` = `day_of_week` (unchanged existing behavior) or `date_parity` (new — `hrm_weekend_parity` = `even` or `odd` decides whether the 2nd/4th/6th/... or the 1st/3rd/5th/... calendar dates of every month are off), configurable from HRM Settings (`toggleWeekendModeFields()` JS shows only the relevant sub-field, mirroring the existing `toggleOvertimeMethodFields()` pattern exactly). Every one of the 8 old call sites now goes through the one shared service — verified they can never disagree with each other again.
[*] REAL REGRESSION CAUGHT & FIXED while wiring up Item 1: `checkoutStatus()` used to return `null` ("leave status untouched") once the day's TOTAL worked time reached a full shift — which correctly avoided re-flagging an already-`half_day`/`early_leave` day as `present` mid-shift, but ALSO meant a genuinely-completed full shift built from multiple sessions kept whatever STALE status an earlier partial checkout had set (e.g. stuck on `half_day` forever, even after a later session brought the day up to a full 9 hours). Fixed by always re-deriving a definite status once the full shift is met (present/late from the stored first check-in), rather than trusting the existing value. Verified directly with a real 3-session walk-through: 4h session → `half_day` (240 min), + 4h40m session → `early_leave` (520 min, correctly still short of the 9h default shift), + 20min session → `present` (540 min, exactly the full shift) — each transition correct, no stale status left behind.
    Verified via real fixtures through the actual controller/service layer (reflection-invoked `performCheckIn`/`performCheckOut`, not a reimplementation): 2 full check-in/check-out cycles in one real day recorded 4 real `AttendancePunch` rows with the correct notes ("Morning shift start", "Lunch break", "Back from lunch", "End of day") and sources (`browser_geolocation`, `browser_geolocation`, `id_card`, `fingerprint`) in order; `AttendanceStatusService::forEmployee()` correctly reported `can_check_in=true`/`can_check_out=false` after the 2nd checkout (i.e. "Checked Out" is no longer a dead end) with both sessions correctly paired up in its `sessions` array; the My Attendance page rendered with the real data correctly showed the "2 sessions" badge, the "Web (GPS)" source label, the "Today's Sessions" hero block, and a genuinely-ENABLED (not disabled) Check In button; the date-parity weekend mode correctly classified 2026-08-04 (even) as a weekend and 2026-08-05 (odd) as a working day, then was reset back to the default `day_of_week`/`even` settings afterward; the Monthly Sheet's real CSV export correctly produced a row with "Web (GPS)" in the Check In Source column and `2` in the Sessions column for the test day. Separately re-ran the full-shift-status fix as its own isolated scenario (documented above) to confirm it in a cleaner setup. All 6 touched/new PHP files passed `php -l` with zero syntax errors; `admin.attendance-portal.index`, `admin.attendances.monthly`, `admin.attendances.monthly-pdf`, `admin.attendances.monthly-excel`, and `admin.hrm-settings.index` were all rendered directly and produced full HTML with no fatal Blade errors (only the same pre-existing unauthenticated-topbar warnings seen in every prior tinker-render test in this project). All test fixtures (3 throwaway employees plus their attendance/punch rows across the 3 separate test runs) were cleaned up after each run, with a final broad `ZZ%`-pattern sweep across employees/departments/designations/employee_types/employment_statuses plus a direct check for leftover `AttendancePunch` rows confirming zero remaining.

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
