<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class HrmMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hrmModule = Module::updateOrCreate(
            ['slug' => 'hrm'],
            [
                'name' => 'HRM',
                'icon' => 'ri-team-line',
                'description' => 'Human Resource Management',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Employees (direct link, no group)
        $this->menu($hrmModule->id, 'hrm.employees', null, 'Employees', 'ri-user-line', 'admin.employees.index', 0, 'employee.view');

        // Group: Masters
        $masters = $this->group($hrmModule->id, 'hrm.group.masters', 'Masters', 'ri-database-2-line', 1);
        $this->menu($hrmModule->id, 'hrm.employee-types', $masters->id, 'Employee Types', 'ri-team-line', 'admin.employee-types.index', 1, 'employee-type.view');
        $this->menu($hrmModule->id, 'hrm.employment-statuses', $masters->id, 'Employment Statuses', 'ri-file-list-3-line', 'admin.employment-statuses.index', 2, 'employment-status.view');
        $this->menu($hrmModule->id, 'hrm.departments', $masters->id, 'Departments', 'ri-building-line', 'admin.departments.index', 3, 'department.view');
        $this->menu($hrmModule->id, 'hrm.designations', $masters->id, 'Designations', 'ri-award-line', 'admin.designations.index', 4, 'designation.view');
        $this->menu($hrmModule->id, 'hrm.shifts', $masters->id, 'Shifts', 'ri-time-line', 'admin.shifts.index', 5, 'shift.view');
        $this->menu($hrmModule->id, 'hrm.holidays', $masters->id, 'Holidays', 'ri-calendar-event-line', 'admin.holidays.index', 6, 'holiday.view');
        $this->menu($hrmModule->id, 'hrm.leave-types', $masters->id, 'Leave Types', 'ri-calendar-todo-line', 'admin.leave-types.index', 7, 'leave-type.view');
        $this->menu($hrmModule->id, 'hrm.salary-components', $masters->id, 'Salary Components', 'ri-money-dollar-circle-line', 'admin.salary-components.index', 8, 'salary-component.view');
        $this->menu($hrmModule->id, 'hrm.skills', $masters->id, 'Skills', 'ri-star-line', 'admin.skills.index', 9, 'skill.view');

        // Group: Employee Records
        $records = $this->group($hrmModule->id, 'hrm.group.records', 'Employee Records', 'ri-folder-user-line', 2);
        $this->menu($hrmModule->id, 'hrm.employee-documents', $records->id, 'Documents', 'ri-file-user-line', 'admin.employee-documents.index', 1, 'employee-document.view');
        $this->menu($hrmModule->id, 'hrm.emergency-contacts', $records->id, 'Emergency Contacts', 'ri-contacts-line', 'admin.emergency-contacts.index', 2, 'emergency-contact.view');
        $this->menu($hrmModule->id, 'hrm.bank-accounts', $records->id, 'Bank Accounts', 'ri-bank-line', 'admin.bank-accounts.index', 3, 'bank-account.view');
        $this->menu($hrmModule->id, 'hrm.educations', $records->id, 'Education', 'ri-graduation-cap-line', 'admin.educations.index', 4, 'education.view');
        $this->menu($hrmModule->id, 'hrm.experiences', $records->id, 'Experience', 'ri-briefcase-line', 'admin.experiences.index', 5, 'experience.view');

        // Group: Career Events
        $career = $this->group($hrmModule->id, 'hrm.group.career', 'Career Events', 'ri-route-line', 3);
        $this->menu($hrmModule->id, 'hrm.transfers', $career->id, 'Transfers', 'ri-shuffle-line', 'admin.transfers.index', 1, 'transfer.view');
        $this->menu($hrmModule->id, 'hrm.promotions', $career->id, 'Promotions', 'ri-arrow-up-circle-line', 'admin.promotions.index', 2, 'promotion.view');
        $this->menu($hrmModule->id, 'hrm.resignations', $career->id, 'Resignations', 'ri-logout-box-line', 'admin.resignations.index', 3, 'resignation.view');
        $this->menu($hrmModule->id, 'hrm.terminations', $career->id, 'Terminations', 'ri-user-unfollow-line', 'admin.terminations.index', 4, 'termination.view');

        // Group: Attendance & Leave
        $attendance = $this->group($hrmModule->id, 'hrm.group.attendance', 'Attendance & Leave', 'ri-calendar-check-line', 4);
        // Self-service (no permission slug — same "always visible, resolved
        // from the logged-in admin's own linked employee" reasoning the
        // attendance-portal/* routes themselves are deliberately ungated
        // for). Placed first since it's what most linked-employee users
        // actually want day to day, ahead of the admin management screens.
        $this->menu($hrmModule->id, 'hrm.attendance-portal', $attendance->id, 'My Attendance', 'ri-user-follow-line', 'admin.attendance-portal.index', 1, null);
        $this->menu($hrmModule->id, 'hrm.attendances', $attendance->id, 'Attendance', 'ri-fingerprint-line', 'admin.attendances.index', 2, 'attendance.view');
        // Module 12: both now gated by their own dedicated 'attendance.report'
        // permission (was 'attendance.view') — matching PART 14's suggested
        // conceptual permission vocabulary and the route middleware below.
        $this->menu($hrmModule->id, 'hrm.attendances-monthly', $attendance->id, 'Monthly Sheet', 'ri-calendar-todo-line', 'admin.attendances.monthly', 3, 'attendance.report');
        $this->menu($hrmModule->id, 'hrm.attendances-report', $attendance->id, 'Attendance Report', 'ri-bar-chart-grouped-line', 'admin.attendances.report', 4, 'attendance.report');
        $this->menu($hrmModule->id, 'hrm.attendance-adjustments', $attendance->id, 'Attendance Adjustments', 'ri-time-zone-line', 'admin.attendance-adjustments.index', 5, 'attendance-adjustment.view');
        $this->menu($hrmModule->id, 'hrm.leave-balances', $attendance->id, 'Leave Balances', 'ri-donut-chart-line', 'admin.leave-balances.index', 6, 'leave-balance.view');
        $this->menu($hrmModule->id, 'hrm.leave-requests', $attendance->id, 'Leave Requests', 'ri-mail-send-line', 'admin.leave-requests.index', 7, 'leave-request.view');
        $this->menu($hrmModule->id, 'hrm.leave-calendar', $attendance->id, 'Leave Calendar', 'ri-calendar-2-line', 'admin.leave-requests.calendar', 7, 'leave-request.view');

        // Group: Payroll & Finance
        $payroll = $this->group($hrmModule->id, 'hrm.group.payroll', 'Payroll & Finance', 'ri-hand-coin-line', 5);
        $this->menu($hrmModule->id, 'hrm.salary-structures', $payroll->id, 'Salary Structures', 'ri-file-list-line', 'admin.salary-structures.index', 1, 'salary-structure.view');
        $this->menu($hrmModule->id, 'hrm.salary-templates', $payroll->id, 'Salary Templates', 'ri-file-copy-2-line', 'admin.salary-templates.index', 2, 'salary-template.view');
        $this->menu($hrmModule->id, 'hrm.payrolls', $payroll->id, 'Payroll', 'ri-hand-coin-line', 'admin.payrolls.index', 3, 'payroll.view');
        $this->menu($hrmModule->id, 'hrm.expense-claims', $payroll->id, 'Expense Claims', 'ri-receipt-line', 'admin.expense-claims.index', 4, 'expense-claim.view');
        $this->menu($hrmModule->id, 'hrm.expense-categories', $payroll->id, 'Expense Categories', 'ri-price-tag-3-line', 'admin.expense-categories.index', 5, 'expense-category.view');
        $this->menu($hrmModule->id, 'hrm.employee-loans', $payroll->id, 'Employee Loans', 'ri-safe-2-line', 'admin.employee-loans.index', 6, 'employee-loan.view');
        $this->menu($hrmModule->id, 'hrm.minimum-wage-rules', $payroll->id, 'Minimum Wage Rules', 'ri-scales-3-line', 'admin.minimum-wage-rules.index', 7, 'minimum-wage-rule.view');

        // Group: Performance
        $performance = $this->group($hrmModule->id, 'hrm.group.performance', 'Performance', 'ri-line-chart-line', 6);
        $this->menu($hrmModule->id, 'hrm.performance-reviews', $performance->id, 'Performance Reviews', 'ri-line-chart-line', 'admin.performance-reviews.index', 1, 'performance-review.view');

        // Group: Reports
        $reports = $this->group($hrmModule->id, 'hrm.group.reports', 'Reports', 'ri-bar-chart-box-line', 7);
        $this->menu($hrmModule->id, 'hrm.hr-reports', $reports->id, 'HR Reports', 'ri-bar-chart-box-line', 'admin.hr-reports.index', 1, 'hr-report.view');
        $this->menu($hrmModule->id, 'hrm.leave-reports', $reports->id, 'Leave Reports', 'ri-calendar-line', 'admin.leave-reports.index', 2, 'leave-report.view');
        $this->menu($hrmModule->id, 'hrm.payroll-compliance-report', $reports->id, 'Payroll Compliance Report', 'ri-shield-check-line', 'admin.payroll-compliance-report.index', 3, 'payroll-compliance-report.view');
        $this->menu($hrmModule->id, 'hrm.expense-claims-report', $reports->id, 'Expense Claims Report', 'ri-file-chart-line', 'admin.expense-claims-report.index', 4, 'expense-claim.view');

        // Bottom-level configuration entry for all HRM policies and integrations.
        $this->menu($hrmModule->id, 'hrm.settings', null, 'HRM Settings', 'ri-settings-3-line', 'admin.hrm-settings.index', 8, 'hrm-setting.view');

        Cache::forget('admin.module.menus');
    }

    protected function group(int $moduleId, string $name, string $label, string $icon, int $order): ModuleMenu
    {
        return ModuleMenu::updateOrCreate(
            ['module_id' => $moduleId, 'name' => $name],
            [
                'parent_id' => null,
                'label' => $label,
                'icon' => $icon,
                'route' => null,
                // Group headers carry no permission of their own — they are
                // hidden automatically once every item inside them is
                // individually inaccessible (see dynamic_submenu.blade.php).
                'permission' => null,
                'order' => $order,
                'status' => 1,
            ]
        );
    }

    /**
     * @param  string|null  $permission  The exact permission slug (matching
     *      database/seeders/RolePermissionSeeder.php) required to see this
     *      menu item. Pass null only for a menu item that should always be
     *      visible regardless of role.
     */
    protected function menu(int $moduleId, string $name, ?int $parentId, string $label, string $icon, string $route, int $order, ?string $permission = null): ModuleMenu
    {
        return ModuleMenu::updateOrCreate(
            ['module_id' => $moduleId, 'name' => $name],
            [
                'parent_id' => $parentId,
                'label' => $label,
                'icon' => $icon,
                'route' => $route,
                'permission' => $permission,
                'order' => $order,
                'status' => 1,
            ]
        );
    }
}
