<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SupportMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supportModule = Module::updateOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Support',
                'icon' => 'ri-customer-service-2-line',
                'description' => 'Manage support tickets and the help desk',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Tickets (direct link, no group) - the module's primary
        // entity, claiming the order = 0 slot reserved for it when Ticket
        // Categories was built.
        $this->menu($supportModule->id, 'support.tickets', null, 'Tickets', 'ri-customer-service-line', 'admin.tickets.index', 0);

        // Top-level: Ticket Assignment (direct link, no group) - a
        // transactional record worked through regularly, same "primary
        // entity gets a top-level link" reasoning as Tickets itself.
        $this->menu($supportModule->id, 'support.ticket-assignments', null, 'Ticket Assignment', 'ri-user-follow-line', 'admin.ticket-assignments.index', 1);

        // Top-level: Escalation History (direct link, no group) - a
        // read-only log an admin checks regularly, same top-level
        // placement precedent as Employee Asset Tracking (Asset
        // Management) and every other read-only report page in this
        // project. Order bumped from 2 to keep sibling values unique.
        $this->menu($supportModule->id, 'support.ticket-escalations', null, 'Escalation History', 'ri-arrow-up-double-line', 'admin.ticket-escalations.index', 2);

        // Top-level: Knowledge Base articles (direct link, no group) - a
        // content screen support staff work through regularly, same
        // "primary entity gets a top-level link" reasoning as Tickets
        // itself. Order bumped from 3 to keep sibling top-level values
        // unique among this module's parent_id = null rows.
        $this->menu($supportModule->id, 'support.knowledge-base-articles', null, 'Knowledge Base', 'ri-book-open-line', 'admin.knowledge-base-articles.index', 3);

        // Group: Support Masters (Ticket Categories is configuration/master
        // data set up occasionally, so it stays grouped rather than taking
        // a top-level link, same precedent as Asset Categories/Account
        // Types sitting in their own module's Masters group). Order bumped
        // to 4 to keep sibling top-level order values unique.
        $masters = $this->group($supportModule->id, 'support.group.masters', 'Support Masters', 'ri-database-2-line', 4);
        $this->menu($supportModule->id, 'support.ticket-categories', $masters->id, 'Ticket Categories', 'ri-price-tag-3-line', 'admin.ticket-categories.index', 1);
        $this->menu($supportModule->id, 'support.ticket-statuses', $masters->id, 'Ticket Statuses', 'ri-flag-line', 'admin.ticket-statuses.index', 2);
        $this->menu($supportModule->id, 'support.ticket-priorities', $masters->id, 'Ticket Priorities', 'ri-alarm-warning-line', 'admin.ticket-priorities.index', 3);
        $this->menu($supportModule->id, 'support.sla-policies', $masters->id, 'SLA Policies', 'ri-time-line', 'admin.sla-policies.index', 4);
        $this->menu($supportModule->id, 'support.escalation-rules', $masters->id, 'Escalation Rules', 'ri-arrow-up-double-line', 'admin.escalation-rules.index', 5);
        $this->menu($supportModule->id, 'support.knowledge-base-categories', $masters->id, 'KB Categories', 'ri-folder-3-line', 'admin.knowledge-base-categories.index', 6);

        // Group: Reports - last among this module's menu groups, the same
        // placement precedent every other module's own "Reports" group
        // (HRM/CRM/Product Management/Sales/Purchase/Inventory/Accounting/
        // Asset Management/Project Management) already has.
        $reports = $this->group($supportModule->id, 'support.group.reports', 'Reports', 'ri-bar-chart-2-line', 5);
        $this->menu($supportModule->id, 'support.support-reports', $reports->id, 'Support Reports', 'ri-bar-chart-2-line', 'admin.support-reports.index', 1);

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
