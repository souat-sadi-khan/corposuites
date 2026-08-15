<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'approval_mode', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function workflowDefinitions(): HasMany
    {
        return $this->hasMany(WorkflowDefinition::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
