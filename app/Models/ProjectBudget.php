<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends Model
{
    protected $table = 'project_budgets';

    public const STATUSES = ['draft', 'approved', 'revised', 'closed'];

    protected $fillable = [
        'budget_code',
        'project_id',
        'version',
        'title',
        'budget_date',
        'total_amount',
        'budget_status',
        'approved_by',
        'approved_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'version' => 'integer',
        'budget_date' => 'date',
        'approved_date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function getBudgetStatusLabelAttribute(): string
    {
        return ucfirst($this->budget_status);
    }

    /**
     * "v2" style label — the version is only meaningful next to its project.
     */
    public function getVersionLabelAttribute(): string
    {
        return 'v' . $this->version;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBudgetItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
