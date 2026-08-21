# leave-task.md

# HRM — Enterprise-Grade Leave System

## Purpose

This file is the single source of truth for upgrading the HRM Leave subsystem from
functional CRUD to an enterprise-grade leave management system.

Work is done **one task at a time**, top to bottom. After each task is completed,
its checkbox is ticked and a dated entry is appended to the **Progress Log** at the
bottom of this file.

## Ground Rules (inherited from CLAUDE.md)

- Laravel 12 / PHP 8.4+ / MySQL 8+ / Bootstrap 5 / jQuery / AJAX / Yajra DataTables / Select2 / Spatie Permission / Activity Logger.
- Never redesign the architecture. Continue from the current implementation.
- Reuse existing Models, Services, Controllers, Form Requests, Traits, Blade layouts, DataTable + AJAX patterns, permission pattern, and Activity Logger.
- Follow the `status` boolean (active/inactive) + separate enum (`approval_status`) convention already used by Leave Requests.
- Every module change keeps: Permissions, Route Protection, Menu Protection, Activity Log, Validation.
- No placeholder code. Production-ready only.
- Reuse the existing `Holiday` model for non-working-day logic.
- Balance side-effects must stay identical whether approval comes from the direct path or the Workflow Engine (via `SyncLeaveRequestApproval`).

---

## Current Architecture (as-is baseline)

- **Models:** `LeaveType` (name, days_allowed, is_paid, status), `LeaveBalance` (employee+type+year, allocated/used, `remaining_days` accessor), `LeaveRequest` (dates, total_days, `approval_status` enum, `status` boolean; implements `Approvable`, uses `HasWorkflow`).
- **Services:** `LeaveTypeService` / `LeaveBalanceService` (thin CRUD); `LeaveRequestService` holds real logic — `calculateDays()` = `diffInDays+1`, `approve()` deducts balance, `reject()` status-only, `delete()` refunds if approved, `adjustBalance()` via `firstOrCreate`.
- **Controllers:** `LeaveTypeController`, `LeaveBalanceController`, `LeaveRequestController` (DataTables index, CRUD, `approve()`/`reject()` with fallback-safe workflow branch, `updateStatus()`).
- **Approval:** Dual-path. If an active `WorkflowDefinition` exists for `LeaveRequest` → routes through `WorkflowEngineService`; else → direct service call. **No definition is seeded today, so the direct path always runs.** Events (`WorkflowApproved/Rejected/Resubmitted`) → `SyncLeaveRequestApproval` → service, wired manually in `AppServiceProvider`.
- **Support:** routes in `routes/admin.php` (~L774–866), 3 Form Requests, Blade views under `resources/views/admin/leave-*`, JS under `public/assets/system/js/pages/`, menu in `HrmMenuSeeder`, permissions in `RolePermissionSeeder` (`leave-type.*`, `leave-balance.*`, `leave-request.*` incl. `.approve`).

### Known gaps (drive the phases below)

1. `calculateDays()` counts raw calendar days — ignores weekends and holidays.
2. `approve()` deducts unconditionally — no insufficient-balance guard.
3. `update()` recomputes `total_days` but never re-syncs an already-approved request's balance.
4. No overlap / duplicate-request detection.
5. `LeaveType` has no policy rules (accrual, carry-forward, eligibility, notice, half-day, attachment).
6. Balances are created manually — no auto-allocation, accrual, or year-end carry-forward.
7. No half-day / hourly leave, no employee self-service, no proper cancellation, no attachments.
8. Workflow Engine dormant — single-admin approval only.
9. Notifications target approvers only; no employee-facing notify, no team calendar, no reports.
10. `is_paid` unused by Payroll; approved leave not reflected in Attendance.

---

## Phased Plan

Status legend: [ ] not started · [~] in progress · [x] done

### Phase A — Correctness Fixes (bugs in current behavior)  `high`
Goal: make existing approve/reject/edit/delete math correct before adding features.

- [x] A1. Working-day duration: exclude weekends + active `Holiday` dates in `calculateDays()`; keep decimal support.
- [x] A2. Insufficient-balance guard on approval (block or explicit override) with clear message.
- [x] A3. Re-sync `LeaveBalance` when an already-approved request is edited (delta adjust) or its dates/type change.
- [x] A4. Overlap / duplicate detection for the same employee across pending/approved requests.
- [x] A5. Regression check: direct path + workflow path both keep balances correct.

**Phase A decisions (confirmed with client):** Weekend = configurable via `leave_weekend_days` system setting (default Fri+Sat `5,6` for Asia/Dhaka); insufficient balance = warn + allow override; overlap = warn only.

### Phase B — Leave Policy Engine  `high`
Goal: turn `LeaveType` into a configurable policy. Add columns via new migration(s), extend Form Request + views.

- [x] B1. Accrual method (none / annual / monthly) + default annual entitlement.
- [x] B2. Carry-forward toggle + max carry-forward cap + expiry months.
- [x] B3. Eligibility: min service days, applicable gender / employee-type / designation.
- [x] B4. Request rules: min notice days, max consecutive days, allow half-day, requires attachment, encashable.
- [x] B5. Update `LeaveType` create/edit UI + validation to expose all rules; enforce them in request validation.

### Phase C — Accrual & Balance Automation  `medium`
Goal: stop hand-creating balances.

- [x] C1. Auto-allocate balances on employee join (prorated for mid-year), per active policy.
- [x] C2. Scheduled accrual command (monthly/annual) registered in `routes/console.php`.
- [x] C3. Year-end closing job: carry forward within cap, expire remainder, open next year rows.
- [x] C4. Leave encashment for encashable types (record + optional payroll hand-off).

### Phase D — Request Lifecycle & Self-Service  `medium`
Goal: richer request types and employee-facing flow.

- [x] D1. Half-day / hourly leave (use decimal `total_days`; capture session/hours).
- [x] D2. Employee self-service: apply for + track own leave and balances (respect existing guard model).
- [x] D3. Cancellation flow: `cancelled` state + cancel-approved-leave with balance refund.
- [x] D4. Attachments: link supporting documents (e.g. medical certificate) to a request.

**Phase D decision (confirmed with client):** No new employee auth guard. The `admins.employee_id` column defines identity — an admin **with** `employee_id` is an employee (self-service: sees/files only their own leave, cannot approve/reject); an admin **without** it is a super/general admin. Half-day is modelled as a single-date `duration_type=half_day` counting 0.5 day (hourly deferred as out-of-scope for now).

### Phase E — Multi-Level Approval Workflow  `medium`
Goal: activate the dormant Workflow Engine.

- [x] E1. Seed a default `WorkflowDefinition` for `LeaveRequest` (e.g. Manager → HR).
- [x] E2. Workflow builder UI (or seeder-config) to define steps/approvers per policy.
- [x] E3. Approver delegation when an approver is themselves on leave.
- [x] E4. Verify `SyncLeaveRequestApproval` side-effects match direct path exactly.

### Phase F — Notifications, Calendar & Reporting  `medium`
Goal: visibility.

- [x] F1. Notify employee on submit / approve / reject (extend notifier beyond approvers).
- [x] F2. Team leave calendar / who's-out availability view.
- [x] F3. Reports: balance summary, utilization, department/period analytics.

### Phase G — Integrations  `low`
Goal: connect leave to Payroll & Attendance.

- [x] G1. Payroll: unpaid leave (`is_paid = false`) feeds salary deduction.
- [x] G2. Attendance: approved leave days reflected instead of counting as absent.

---

## Progress Log

Append a dated entry after each completed task. Newest at the bottom.

- 2026-08-18 — Created `leave-task.md`, documented as-is baseline, known gaps, and the A–G phased plan. No code changed yet.
- 2026-08-18 — **Phase A complete.** Rewrote `LeaveRequestService`: `calculateDays()` now counts working days only, excluding configurable weekend days (`leave_weekend_days` setting, default `5,6` = Fri+Sat) and active `Holiday` dates; added `remainingBalance()`, `hasSufficientBalance()`, `overlappingRequests()`, and a reusable `adjustBalanceFor()`. `update()` now reverses the original deduction and re-applies against the new bucket when editing an already-approved request (handles changed dates / leave type / year). `LeaveRequestController::approve()` guards the direct path against insufficient balance and returns `requires_override`; the admin can confirm to resend with `override=1` (warn + allow override). `store()`/`update()` return a non-blocking `warning` string when the request overlaps existing pending/approved leave (warn only). `leave-requests.js` updated: approval flow handles the override confirm loop and shows Lobibox toasts; a scoped `ajaxSuccess` hook surfaces overlap warnings. Verified: `php -l` clean on both files, no IDE diagnostics, all 9 `leave-requests` routes intact, and a runtime probe confirmed weekend+holiday-aware day counts (Mon–Thu span before a Fri weekend = 4 days; reversed range = 0). No DB/schema changes in this phase.
- 2026-08-18 — **Phase B complete.** New migration `2026_08_29_090000_add_policy_fields_to_leave_types_table` adds 13 policy columns: `accrual_method`, `allow_carry_forward`, `max_carry_forward`, `carry_forward_expiry_months`, `min_service_days`, `applicable_gender`, `applicable_employee_type_ids` (json), `applicable_designation_ids` (json), `min_notice_days`, `max_consecutive_days`, `allow_half_day`, `requires_attachment`, `is_encashable`. `LeaveType` model extended (fillable + casts incl. array/boolean/decimal + `restrictsEmployeeType()`/`restrictsDesignation()` helpers). New `LeavePolicyService` centralises `eligibilityErrors()`/`isEligible()` (gender, min service, employee-type, designation) and `requestRuleErrors()` (min notice, max consecutive, attachment). `LeaveRequestService::workingDays()` exposed publicly for reuse. `LeaveRequestController` now injects `LeavePolicyService` and enforces policy as a hard HTTP 422 (`errors.policy[]`) on store()/update(), reusing the existing main.js error renderer. `LeaveTypeRequest` validates all new fields (with `prepareForValidation()` boolean normalisation). `LeaveTypeController::create()/edit()` pass active employee types + designations; create/edit Blade forms expose every rule (Select2 multi-selects for the id lists). Migration applied cleanly; logic verified via runtime probe (male/new employee correctly blocked on gender + service; tomorrow/6-day/no-attachment request correctly flagged on notice + consecutive + attachment; compliant cases return no errors). All `php -l` + IDE diagnostics clean; leave-types routes intact.
- 2026-08-18 — **Phase C complete.** Two migrations: `...091000_add_carry_forward_tracking_to_leave_balances_table` (adds `carried_days`, `carry_expires_on`) and `...092000_create_leave_encashments_table` (+ `LeaveEncashment` model). New `LeaveAccrualService`: `allocateForEmployee()` (join-time auto-allocation, annual prorated by remaining months, monthly = accrued-to-date, skips `accrual_method=none` + ineligible), `runMonthlyAccrual()` (idempotent catch-up to accrued target — safe to re-run/backfill), `runYearEndCarryForward()` (carries `min(remaining, cap)` into next year atop the base entitlement, stamps expiry), `expireCarryForward()` (forfeits unused carried days past expiry), `encash()` (records payout + marks days used). Auto-allocation wired into `EmployeeService::create()` (non-blocking). Three commands — `leave:accrue`, `leave:year-end`, `leave:expire-carry-forward` — registered + scheduled in `routes/console.php` (monthly / yearly Jan 1 / daily). Admin UI on leave-balances: **Generate Balances** toolbar button (backfill existing employees via `generate` route) and per-row **Encash** action (encashable types with remaining > 0, via `encash` route); both wired in `leave-balances.js` with Lobibox toasts. Permissions `leave-balance.generate` + `leave-balance.encash` added to `RolePermissionSeeder`, created idempotently and granted to Super Admin in the live DB. Full transactional runtime probe passed: July joiner annual = 10.00; use 4 → remaining 6; year-end → next-year allocated 25.00 (20 base + 5 carry) / carried 5.00 / expiry 2027-04-01; post-expiry allocated back to 20.00 / carried 0.00; monthly accrual through March idempotent = 3.00; encash 20 → remaining 0. Migrations applied; all `php -l` + IDE diagnostics clean; 9 leave-balances routes intact.
- 2026-08-18 — **Phase D complete.** Migration `...093000_add_lifecycle_fields_to_leave_requests_table` extends the `approval_status` enum with `cancelled` and adds `duration_type`, `half_day_session`, `attachment`, `cancellation_reason`, `cancelled_at`. `LeaveRequest` fillable/casts updated. **D1 (half-day):** `LeaveRequestService::resolveTotalDays()` returns 0.5 for `duration_type=half_day` (single date; `LeaveRequestRequest::prepareForValidation()` forces end=start), else working-day count; policy enforces `allow_half_day`. **D3 (cancellation):** new `cancel()` service method + controller endpoint/route (`leave-requests/{}/cancel`) — refunds balance if the request was approved, stamps reason + `cancelled_at`; UI cancel button shown for pending/approved. **D4 (attachments):** file upload via `Images::upload()/update()` in store()/update(), validated (`pdf,jpg,jpeg,png,doc,docx`, max 4MB), wired into the `requires_attachment` policy check; view-attachment links in the list + edit form. **D2 (self-service):** reuses the admin guard per client's model — `selfEmployeeId()` returns `admins.employee_id`; such an admin's index is scoped to their own requests, create() locks the employee dropdown, store() forces `employee_id`, and approve()/reject() are blocked both server-side (403) and in the action view (buttons hidden). Half-day toggle + cancel handler added to `leave-requests.js`; create/edit Blade forms rebuilt with duration/session/attachment fields (multipart); list shows half-day/duration + `cancelled` badge. Permission `leave-request.cancel` seeded + granted to Super Admin. Migration applied; transactional runtime probe passed (half-day=0.50, approve→used 0.50, cancel→refund 0.00 + cancelled, full-day=3.00, pending cancel sets `cancelled_at`); all `php -l` + IDE diagnostics clean; 10 leave-requests routes intact. Note: hourly leave intentionally deferred (out of current scope).
- 2026-08-19 — **Phase E complete.** Activated the dormant Workflow Engine for leave. **E1:** new idempotent `LeaveWorkflowSeeder` creates `Manager` + `HR` admin-guard roles (granted `leave-request.view/approve` + `leave-balance.view`) and an **active** sequential `WorkflowDefinition` for `App\Models\LeaveRequest` (step 1 Manager → step 2 HR, role-based approvers) plus 4 notification triggers (`step_pending`→approver, `approved`/`rejected`/`completed`→initiator); registered in `DatabaseSeeder` and run against live DB (definition #8). **E2:** confirmed the pre-existing Workflow builder UI (`WorkflowDefinitionController` + create/edit views + service + `WorkflowDefinitionRequest`) already maps `leave_request → LeaveRequest::class` — no rebuild needed. **E3 (delegation):** new migration `...094000_create_approval_delegations_table` (`delegator_admin_id`, `delegate_admin_id`, `starts_on`, `ends_on`, `reason`, `status`; short index name `appr_deleg_lookup_idx` to stay under MySQL's 64-char limit) + `ApprovalDelegation` model (`scopeActive`/`scopeCovering`), `ApprovalDelegationService` (`effectiveApproverId()` with cycle-guarded chain follow, `mapApprovers()`), `ApprovalDelegationRequest`, full CRUD `ApprovalDelegationController` (DataTable + status toggle + activity log) with index/create/edit/action Blade views + `approval-delegations.js`; routes (status before resource), permissions `approval-delegation.view/create/edit/delete` (seeded + granted to Super Admin), and a Workflow-module menu entry added. `WorkflowNotifier` now constructor-injects `ApprovalDelegationService` and routes `approver` recipients through `mapApprovers()`, so an away approver's pending-step notifications go to their active delegate. `LeaveRequestController::approve()` message now distinguishes a mid-chain step advance ("Forwarded to the next approver") from full approval. **E4 + critical bug fix:** a transactional probe drove a leave request through the full Manager→HR chain and revealed a **pre-existing double-deduction bug** — balance was deducted **twice** on completion. Root cause: Laravel 11/12 event **auto-discovery** already registers all 5 workflow `Sync*Approval` listeners for `WorkflowApproved/Rejected/Resubmitted`, and `AppServiceProvider::registerWorkflowListeners()` was **manually registering the same 5 again** (10 total; every `handle*` fired twice). This latent bug affected **all 5 workflow modules** (leave, expense, attendance, loan, purchase) and was only exposed now because E1 created the first-ever *active* workflow. Fixed by removing the redundant manual `Event::listen` block from `AppServiceProvider` (auto-discovery is sufficient; verified 5 listeners per event, was 10). Re-ran probe → **PASS**: Manager approve advances the step (no deduction, status stays `pending`), HR approve completes it and deducts **exactly once** (`used_days`=1), matching the direct path; delegation resolver correctly substitutes the delegate in-window and resolves to self out-of-window. All `php -l` + IDE diagnostics clean; 7 approval-delegations routes registered; all probe files removed.
- 2026-08-19 — **Phase F complete.** Notifications, calendar & reporting. **F1 (notifications):** discovered the `ActivityLog::created` model hook already auto-creates an in-app `Notification` for *every* activity log, so the existing audit calls in `store/approve/reject/cancel` were already notifying — a bespoke notifier would have produced duplicates (a probe confirmed +2/+3). Correct fix per ground rules: no new notification plumbing; instead added a small `leaveSummary()` helper and rewrote the four lifecycle audit descriptions to be employee-friendly (e.g. "Leave request approved — {Name} ({Type}, {from} to {to})"), so the auto-generated notifications read well on both the workflow and direct paths. Verified direct-path approve raises **exactly one** notification with the friendly message. **F2 (team calendar):** added `calendar()` + `calendarEvents()` to `LeaveRequestController` (mirrors `HolidayController`), a `leave-requests/calendar` + `calendar-events` route pair (before the resource route), `resources/views/admin/leave-requests/calendar.blade.php` (FullCalendar 6.1.11 + Approved/Pending legend) and `public/assets/system/js/pages/leave-calendar.js`. Feed shows approved (green) + pending (amber) leave, all-day end date made exclusive (+1 day), and respects Phase D2 self-service scoping (`selfEmployeeId()` → own leave only). **F3 (reports):** new read-only `LeaveReportController@index` (no service/CRUD, mirrors `HrReportController`) with a year filter; aggregates headline stats (active employees, pending, approved-this-year, days-taken), utilization by leave type (allocated vs used vs remaining vs %), 12-month approved-days trend, request status breakdown, and top-10 leave takers. View `resources/views/admin/leave-reports/index.blade.php` uses stat cards + Chart.js 4.4.2 (bar/line/doughnut) + summary tables; route `leave-reports.index`; permission `leave-report.view` (seeded + granted to Super Admin). Two HRM menu entries added (Leave Calendar under Attendance & Leave, Leave Reports under Reports) and `HrmMenuSeeder` re-run (cache cleared). **Verification:** a transactional probe confirmed F1 (+1 notification, friendly text), F2 (event present, end-exclusive correct, green for approved), and F3 (used=3/remaining=7/30% util, 12-month trend) → **PASS**; both new Blade views render fully (~200KB each, canvas/calendar present) once an admin is authenticated. All `php -l` + IDE diagnostics clean; 3 new routes registered; all probe files removed.
