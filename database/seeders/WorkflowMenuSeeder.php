<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class WorkflowMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workflowModule = Module::updateOrCreate(
            ['slug' => 'workflow-engine'],
            [
                'name' => 'Workflow Engine',
                'icon' => 'ri-flow-chart',
                'description' => 'Approval workflows and process automation',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Group: Workflow Engine
        $engine = $this->group($workflowModule->id, 'workflow.group.engine', 'Workflow Engine', 'ri-flow-chart', 1);
        $this->menu($workflowModule->id, 'workflow.workflow-templates', $engine->id, 'Workflow Templates', 'ri-git-branch-line', 'admin.workflow-templates.index', 1);
        $this->menu($workflowModule->id, 'workflow.workflow-definitions', $engine->id, 'Workflow Definitions', 'ri-node-tree', 'admin.workflow-definitions.index', 2);
        $this->menu($workflowModule->id, 'workflow.approval-delegations', $engine->id, 'Approval Delegations', 'ri-user-shared-line', 'admin.approval-delegations.index', 3);

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
