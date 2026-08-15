<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class BudgetMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $budgetModule = Module::updateOrCreate(
            ['slug' => 'budget-finance'],
            [
                'name' => 'Budget & Finance',
                'icon' => 'ri-pie-chart-2-line',
                'description' => 'Plan and track company-wide budgets, allocations and forecasts',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Budget Planning (direct link, no group) - the module's
        // first, foundational entity: a period-scoped, versioned budget
        // broken down by Chart of Accounts line. Department Budget/Project
        // Budget/Cost Centers/Profit Centers (later roadmap items) will
        // presumably retrofit onto this the same "build the dependency,
        // then retrofit" way every other module in this project has.
        $this->menu($budgetModule->id, 'budget.budgets', null, 'Budget Planning', 'ri-pie-chart-2-line', 'admin.budgets.index', 0);

        // Top-level: Department Budget (direct link, no group) - a
        // department-scoped counterpart to the company-wide Budget
        // Planning above, both primary transactional entities in this
        // module, so both take a top-level link.
        $this->menu($budgetModule->id, 'budget.department-budgets', null, 'Department Budget', 'ri-building-4-line', 'admin.department-budgets.index', 1);

        // Top-level: Project Budget (direct link, no group) - the Budget &
        // Finance-owned reconciliation-layer budget, deliberately separate
        // from Project Management's own (category-enum-based) Project
        // Budgets — a third primary transactional entity alongside Budget
        // Planning and Department Budget above.
        $this->menu($budgetModule->id, 'budget.finance-project-budgets', null, 'Project Budget', 'ri-folder-chart-2-line', 'admin.finance-project-budgets.index', 2);

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
