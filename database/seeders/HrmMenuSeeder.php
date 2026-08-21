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
        $this->menu($hrmModule->id, 'hrm.employees', null, 'Employees', 'ri-user-line', 'admin.employees.index', 0);

        // Group: Masters
        $masters = $this->group($hrmModule->id, 'hrm.group.masters', 'Masters', 'ri-database-2-line', 1);
        $this->menu($hrmModule->id, 'hrm.employee-types', $masters->id, 'Employee Types', 'ri-team-line', 'admin.employee-types.index', 1);
        $this->menu($hrmModule->id, 'hrm.employment-statuses', $masters->id, 'Employment Statuses', 'ri-file-list-3-line', 'admin.employment-statuses.index', 2);
        $this->menu($hrmModule->id, 'hrm.departments', $masters->id, 'Departments', 'ri-building-line', 'admin.departments.index', 3);
        $this->menu($hrmModule->id, 'hrm.designations', $masters->id, 'Designations', 'ri-award-line', 'admin.designations.index', 4);
        $this->menu($hrmModule->id, 'hrm.shifts', $masters->id, 'Shifts', 'ri-time-line', 'admin.shifts.index', 5);
        $this->menu($hrmModule->id, 'hrm.holidays', $masters->id, 'Holidays', 'ri-calendar-event-line', 'admin.holidays.index', 6);
        $this->menu($hrmModule->id, 'hrm.leave-types', $masters->id, 'Leave Types', 'ri-calendar-todo-line', 'admin.leave-types.index', 7);
        $this->menu($hrmModule->id, 'hrm.salary-components', $masters->id, 'Salary Components', 'ri-money-dollar-circle-line', 'admin.salary-components.index', 8);
        $this->menu($hrmModule->id, 'hrm.skills', $masters->id, 'Skills', 'ri-star-line', 'admin.skills.index', 9);

        // Group: Employee Records
        $records = $this->group($hrmModule->id, 'hrm.group.records', 'Employee Records', 'ri-folder-user-line', 2);
        $this->menu($hrmModule->id, 'hrm.employee-documents', $records->id, 'Documents', 'ri-file-user-line', 'admin.employee-documents.index', 1);
        $this->menu($hrmModule->id, 'hrm.emergency-contacts', $records->id, 'Emergency Contacts', 'ri-contacts-line', 'admin.emergency-contacts.index', 2);
        $this->menu($hrmModule->id, 'hrm.bank-accounts', $records->id, 'Bank Accounts', 'ri-bank-line', 'admin.bank-accounts.index', 3);
        $this->menu($hrmModule->id, 'hrm.educations', $records->id, 'Education', 'ri-graduation-cap-line', 'admin.educations.index', 4);
        $this->menu($hrmModule->id, 'hrm.experiences', $records->id, 'Experience', 'ri-briefcase-line', 'admin.experiences.index', 5);

        // Group: Career Events
        $career = $this->group($hrmModule->id, 'hrm.group.career', 'Career Events', 'ri-route-line', 3);
        $this->menu($hrmModule->id, 'hrm.transfers', $career->id, 'Transfers', 'ri-shuffle-line', 'admin.transfers.index', 1);
        $this->menu($hrmModule->id, 'hrm.promotions', $career->id, 'Promotions', 'ri-arrow-up-circle-line', 'admin.promotions.index', 2);
        $this->menu($hrmModule->id, 'hrm.resignations', $career->id, 'Resignations', 'ri-logout-box-line', 'admin.resignations.index', 3);
        $this->menu($hrmModule->id, 'hrm.terminations', $career->id, 'Terminations', 'ri-user-unfollow-line', 'admin.terminations.index', 4);

        // Group: Attendance & Leave
        $attendance = $this->group($hrmModule->id, 'hrm.group.attendance', 'Attendance & Leave', 'ri-calendar-check-line', 4);
        $this->menu($hrmModule->id, 'hrm.attendances', $attendance->id, 'Attendance', 'ri-fingerprint-line', 'admin.attendances.index', 1);
        $this->menu($hrmModule->id, 'hrm.attendance-adjustments', $attendance->id, 'Attendance Adjustments', 'ri-time-zone-line', 'admin.attendance-adjustments.index', 2);
        $this->menu($hrmModule->id, 'hrm.leave-balances', $attendance->id, 'Leave Balances', 'ri-donut-chart-line', 'admin.leave-balances.index', 3);
        $this->menu($hrmModule->id, 'hrm.leave-requests', $attendance->id, 'Leave Requests', 'ri-mail-send-line', 'admin.leave-requests.index', 4);
        $this->menu($hrmModule->id, 'hrm.leave-calendar', $attendance->id, 'Leave Calendar', 'ri-calendar-2-line', 'admin.leave-requests.calendar', 5);

        // Group: Payroll & Finance
        $payroll = $this->group($hrmModule->id, 'hrm.group.payroll', 'Payroll & Finance', 'ri-hand-coin-line', 5);
        $this->menu($hrmModule->id, 'hrm.salary-structures', $payroll->id, 'Salary Structures', 'ri-file-list-line', 'admin.salary-structures.index', 1);
        $this->menu($hrmModule->id, 'hrm.salary-templates', $payroll->id, 'Salary Templates', 'ri-file-copy-2-line', 'admin.salary-templates.index', 2);
        $this->menu($hrmModule->id, 'hrm.payrolls', $payroll->id, 'Payroll', 'ri-hand-coin-line', 'admin.payrolls.index', 3);
        $this->menu($hrmModule->id, 'hrm.expense-claims', $payroll->id, 'Expense Claims', 'ri-receipt-line', 'admin.expense-claims.index', 4);
        $this->menu($hrmModule->id, 'hrm.employee-loans', $payroll->id, 'Employee Loans', 'ri-safe-2-line', 'admin.employee-loans.index', 5);
        $this->menu($hrmModule->id, 'hrm.minimum-wage-rules', $payroll->id, 'Minimum Wage Rules', 'ri-scales-3-line', 'admin.minimum-wage-rules.index', 6);

        // Group: Performance
        $performance = $this->group($hrmModule->id, 'hrm.group.performance', 'Performance', 'ri-line-chart-line', 6);
        $this->menu($hrmModule->id, 'hrm.performance-reviews', $performance->id, 'Performance Reviews', 'ri-line-chart-line', 'admin.performance-reviews.index', 1);

        // Group: Reports
        $reports = $this->group($hrmModule->id, 'hrm.group.reports', 'Reports', 'ri-bar-chart-box-line', 7);
        $this->menu($hrmModule->id, 'hrm.hr-reports', $reports->id, 'HR Reports', 'ri-bar-chart-box-line', 'admin.hr-reports.index', 1);
        $this->menu($hrmModule->id, 'hrm.leave-reports', $reports->id, 'Leave Reports', 'ri-calendar-line', 'admin.leave-reports.index', 2);
        $this->menu($hrmModule->id, 'hrm.payroll-compliance-report', $reports->id, 'Payroll Compliance Report', 'ri-shield-check-line', 'admin.payroll-compliance-report.index', 3);

        // Bottom-level configuration entry for all HRM policies and integrations.
        $this->menu($hrmModule->id, 'hrm.settings', null, 'HRM Settings', 'ri-settings-3-line', 'admin.hrm-settings.index', 8);

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
                'permission' => null,
                'order' => $order,
                'status' => 1,
            ]
        );
    }

    protected function menu(int $moduleId, string $name, ?int $parentId, string $label, string $icon, string $route, int $order): ModuleMenu
    {
        return ModuleMenu::updateOrCreate(
            ['module_id' => $moduleId, 'name' => $name],
            [
                'parent_id' => $parentId,
                'label' => $label,
                'icon' => $icon,
                'route' => $route,
                'permission' => null,
                'order' => $order,
                'status' => 1,
            ]
        );
    }
}
