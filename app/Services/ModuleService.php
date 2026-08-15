<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleService
{
    public function getAll(array $filters = [])
    {
        return Module::when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Module
    {
        $data['slug'] = Str::slug($data['name']);
        $data['installed_at'] = now();
        return Module::create($data);
    }

    public function update(Module $module, array $data): Module
    {
        $module->update($data);
        return $module;
    }

    public function delete(Module $module): bool
    {
        // Optionally delete menus first (cascade)
        return $module->delete();
    }

    public function activate(Module $module): Module
    {
        $module->update(['status' => 1]);
        $this->clearMenuCache();
        return $module;
    }

    public function deactivate(Module $module): Module
    {
        $module->update(['status' => 0]);
        $this->clearMenuCache();
        return $module;
    }

    // Install = activate + set installed_at
    public function install(Module $module): Module
    {
        $module->update([
            'status' => 1,
            'installed_at' => now(),
        ]);
        $this->clearMenuCache();
        return $module;
    }

    // Uninstall = deactivate + maybe soft delete? We'll just deactivate
    public function uninstall(Module $module): Module
    {
        $module->update(['status' => 0]);
        $this->clearMenuCache();
        return $module;
    }

    protected function clearMenuCache()
    {
        Cache::forget('admin.module.menus');
    }
}
