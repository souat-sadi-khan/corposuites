<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ProjectMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projectModule = Module::updateOrCreate(
            ['slug' => 'project-management'],
            [
                'name' => 'Project Management',
                'icon' => 'ri-briefcase-4-line',
                'description' => 'Manage clients, projects, teams, tasks, time and billing',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Projects (direct link, no group) - the module's primary entity
        $this->menu($projectModule->id, 'project.projects', null, 'Projects', 'ri-folder-chart-2-line', 'admin.projects.index', 0);

        // Top-level: Project Budgets (direct link, no group)
        $this->menu($projectModule->id, 'project.project-budgets', null, 'Project Budgets', 'ri-wallet-3-line', 'admin.project-budgets.index', 1);

        // Top-level: Project Teams (direct link, no group)
        $this->menu($projectModule->id, 'project.project-team-members', null, 'Project Teams', 'ri-team-line', 'admin.project-team-members.index', 2);

        // Top-level: Milestones (direct link, no group)
        $this->menu($projectModule->id, 'project.project-milestones', null, 'Milestones', 'ri-flag-2-line', 'admin.project-milestones.index', 3);

        // Top-level: Tasks (direct link, no group)
        $this->menu($projectModule->id, 'project.project-tasks', null, 'Tasks', 'ri-list-check-3', 'admin.project-tasks.index', 4);

        // Top-level: Task Board (direct link, no group)
        $this->menu($projectModule->id, 'project.task-board', null, 'Task Board', 'ri-kanban-view', 'admin.task-board.index', 5);

        // Top-level: Gantt Chart (direct link, no group)
        $this->menu($projectModule->id, 'project.gantt-chart', null, 'Gantt Chart', 'ri-calendar-schedule-line', 'admin.gantt-chart.index', 6);

        // Top-level: Task Dependencies (direct link, no group)
        $this->menu($projectModule->id, 'project.project-task-dependencies', null, 'Task Dependencies', 'ri-git-branch-line', 'admin.project-task-dependencies.index', 7);

        // Top-level: Time Tracking (direct link, no group)
        $this->menu($projectModule->id, 'project.project-time-entries', null, 'Time Tracking', 'ri-time-line', 'admin.project-time-entries.index', 8);

        // Top-level: Timesheets (direct link, no group)
        $this->menu($projectModule->id, 'project.project-timesheets', null, 'Timesheets', 'ri-file-list-3-line', 'admin.project-timesheets.index', 9);

        // Top-level: Project Expenses (direct link, no group)
        $this->menu($projectModule->id, 'project.project-expenses', null, 'Project Expenses', 'ri-money-dollar-circle-line', 'admin.project-expenses.index', 10);

        // Top-level: Project Billing (direct link, no group)
        $this->menu($projectModule->id, 'project.project-invoices', null, 'Project Billing', 'ri-bill-line', 'admin.project-invoices.index', 11);

        // Group: Project Masters (Clients is configuration/master data set up
        // occasionally, so it stays grouped rather than taking a top-level link)
        $masters = $this->group($projectModule->id, 'project.group.masters', 'Project Masters', 'ri-database-2-line', 12);
        $this->menu($projectModule->id, 'project.clients', $masters->id, 'Clients', 'ri-user-star-line', 'admin.clients.index', 1);

        // Group: Reports (last, same placement precedent as every other
        // module's own "Reports" group)
        $reports = $this->group($projectModule->id, 'project.group.reports', 'Reports', 'ri-line-chart-line', 13);
        $this->menu($projectModule->id, 'project.project-reports', $reports->id, 'Profitability Reports', 'ri-line-chart-line', 'admin.project-reports.index', 1);

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
