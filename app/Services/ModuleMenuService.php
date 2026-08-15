<?php

namespace App\Services;

use App\Models\ModuleMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleMenuService
{
    // Get all menus with hierarchical structure, cached
    public function getCachedMenus()
    {
        return Cache::remember('admin.module.menus', 3600, function () {
            return $this->buildMenuTree();
        });
    }

    // Build nested tree from all active modules & menus
    protected function buildMenuTree()
    {
        $modules = \App\Models\Module::with(['menus' => function ($query) {
            $query->active()->whereNull('parent_id')->orderBy('order');
        }])->active()->get();

        $tree = [];
        foreach ($modules as $module) {
            $moduleNode = [
                'id' => $module->id,
                'label' => $module->name,
                'icon' => $module->icon ?? 'bi-box',
                'children' => $this->buildMenuChildren($module->menus),
                'is_module' => true,
                'module_slug' => $module->slug,
            ];
            $tree[] = $moduleNode;
        }

        return $tree;
    }

    protected function buildMenuChildren($menus)
    {
        $result = [];
        foreach ($menus as $menu) {
            $node = [
                'id' => $menu->id,
                'label' => $menu->label,
                'icon' => $menu->icon,
                'route' => $menu->route,
                'url' => $menu->url,
                'permission' => $menu->permission,
                'children' => $this->buildMenuChildren($menu->children),
            ];
            $result[] = $node;
        }
        return $result;
    }

    // Clear cache on any menu change
    public function clearCache()
    {
        Cache::forget('admin.module.menus');
    }

    // CRUD operations with cache clearing
    public function create(array $data): ModuleMenu
    {
        $menu = ModuleMenu::create($data);
        $this->clearCache();
        return $menu;
    }

    public function update(ModuleMenu $menu, array $data): ModuleMenu
    {
        $menu->update($data);
        $this->clearCache();
        return $menu;
    }

    public function delete(ModuleMenu $menu): bool
    {
        $result = $menu->delete();
        $this->clearCache();
        return $result;
    }

    public function toggleStatus(ModuleMenu $menu): ModuleMenu
    {
        $menu->update(['status' => !$menu->status]);
        $this->clearCache();
        return $menu;
    }

    // Reorder menus (for drag/drop or batch update)
    public function reorder(array $orderData)
    {
        DB::transaction(function () use ($orderData) {
            foreach ($orderData as $id => $order) {
                ModuleMenu::where('id', $id)->update(['order' => $order]);
            }
        });
        $this->clearCache();
    }

    // Get menus for a module (for admin UI)
    public function getMenusByModule($moduleId)
    {
        return ModuleMenu::where('module_id', $moduleId)->orderBy('order')->get();
    }

    // Get flat list with parent names (for DataTable)
    public function getFlatList($moduleId = null)
    {
        $query = ModuleMenu::with(['module', 'parent']);
        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }
        return $query->orderBy('module_id')->orderBy('order')->get();
    }
}
