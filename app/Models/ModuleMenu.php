<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id', 'parent_id', 'label', 'name', 'icon',
        'route', 'url', 'permission', 'order', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ModuleMenu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ModuleMenu::class, 'parent_id')->orderBy('order');
    }

    // Scope for active menus
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
