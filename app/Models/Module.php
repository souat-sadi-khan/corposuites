<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'version', 'description', 'icon',
        'status', 'is_core', 'installed_at'
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'installed_at' => 'datetime',
    ];

    public function menus(): HasMany
    {
        return $this->hasMany(ModuleMenu::class);
    }

    // Scope for active modules
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
