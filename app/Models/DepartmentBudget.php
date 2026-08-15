<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentBudget extends Model
{
    protected $table = 'department_budgets';

    public const STATUSES = ['draft', 'approved', 'revised', 'closed'];

    public const PERIOD_TYPES = ['monthly', 'quarterly', 'yearly'];

    protected $fillable = [
        'budget_code',
        'department_id',
        'title',
        'period_type',
        'period_start',
        'period_end',
        'version',
        'total_amount',
        'budget_status',
        'approved_by',
        'approved_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'version' => 'integer',
        'approved_date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function getBudgetStatusLabelAttribute(): string
    {
        return ucfirst($this->budget_status);
    }

    public function getPeriodTypeLabelAttribute(): string
    {
        return ucfirst($this->period_type);
    }

    /**
     * "v2" style label — the version is only meaningful next to its
     * department + period.
     */
    public function getVersionLabelAttribute(): string
    {
        return 'v' . $this->version;
    }

    /**
     * A readable "01 Jan - 31 Dec 2026" range for the period this budget
     * covers.
     */
    public function getPeriodLabelAttribute(): string
    {
        if (! $this->period_start || ! $this->period_end) {
            return '-';
        }

        return $this->period_start->format('d M Y') . ' - ' . $this->period_end->format('d M Y');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DepartmentBudgetItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
