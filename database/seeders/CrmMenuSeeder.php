<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CrmMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $crmModule = Module::updateOrCreate(
            ['slug' => 'crm'],
            [
                'name' => 'CRM',
                'icon' => 'ri-team-line',
                'description' => 'Customer acquisition, sales pipeline and relationship management',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: CRM Dashboard (direct link, no group)
        $this->menu($crmModule->id, 'crm.dashboard', null, 'CRM Dashboard', 'ri-dashboard-3-line', 'admin.crm-dashboard.index', 0);

        // Top-level: Leads (direct link, no group)
        $this->menu($crmModule->id, 'crm.leads', null, 'Leads', 'ri-user-add-line', 'admin.leads.index', 1);

        // Group: CRM Masters
        $masters = $this->group($crmModule->id, 'crm.group.masters', 'CRM Masters', 'ri-team-line', 1);
        $this->menu($crmModule->id, 'crm.lead-sources', $masters->id, 'Lead Sources', 'ri-map-pin-line', 'admin.lead-sources.index', 1);
        $this->menu($crmModule->id, 'crm.lead-statuses', $masters->id, 'Lead Statuses', 'ri-flag-line', 'admin.lead-statuses.index', 2);

        // Group: CRM Records
        $records = $this->group($crmModule->id, 'crm.group.records', 'CRM Records', 'ri-folder-user-line', 2);
        $this->menu($crmModule->id, 'crm.contacts', $records->id, 'Contacts', 'ri-contacts-book-line', 'admin.contacts.index', 1);
        $this->menu($crmModule->id, 'crm.companies', $records->id, 'Companies', 'ri-building-2-line', 'admin.companies.index', 2);
        $this->menu($crmModule->id, 'crm.relationship-histories', $records->id, 'Relationship History', 'ri-history-line', 'admin.relationship-histories.index', 3);

        // Group: Sales Pipeline
        $pipeline = $this->group($crmModule->id, 'crm.group.pipeline', 'Sales Pipeline', 'ri-funds-line', 3);
        $this->menu($crmModule->id, 'crm.opportunities', $pipeline->id, 'Opportunities', 'ri-hand-coin-line', 'admin.opportunities.index', 1);
        $this->menu($crmModule->id, 'crm.opportunities-kanban', $pipeline->id, 'Pipeline Kanban', 'ri-layout-column-line', 'admin.opportunities.kanban', 2);
        $this->menu($crmModule->id, 'crm.quotations', $pipeline->id, 'Quotations', 'ri-file-list-3-line', 'admin.quotations.index', 3);
        $this->menu($crmModule->id, 'crm.sales-forecast', $pipeline->id, 'Sales Forecast', 'ri-line-chart-line', 'admin.sales-forecast.index', 4);

        // Group: Activities & Engagement
        $engagement = $this->group($crmModule->id, 'crm.group.engagement', 'Activities & Engagement', 'ri-calendar-check-line', 4);
        $this->menu($crmModule->id, 'crm.activities', $engagement->id, 'Activities', 'ri-phone-line', 'admin.activities.index', 1);
        $this->menu($crmModule->id, 'crm.follow-ups', $engagement->id, 'Follow Ups & Reminders', 'ri-alarm-line', 'admin.follow-ups.index', 2);
        $this->menu($crmModule->id, 'crm.email-communications', $engagement->id, 'Email Communication History', 'ri-mail-line', 'admin.email-communications.index', 3);

        // Group: Reports
        $reports = $this->group($crmModule->id, 'crm.group.reports', 'Reports', 'ri-bar-chart-box-line', 5);
        $this->menu($crmModule->id, 'crm.reports', $reports->id, 'CRM Reports', 'ri-bar-chart-box-line', 'admin.crm-reports.index', 1);

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
