<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentBudgetItem extends Model
{
    protected $table = 'department_budget_items';

    protected $fillable = [
        'department_budget_id',
        'chart_of_account_id',
        'planned_amount',
        'notes',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
    ];

    public function departmentBudget(): BelongsTo
    {
        return $this->belongsTo(DepartmentBudget::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
