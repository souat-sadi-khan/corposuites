<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceProjectBudgetItem extends Model
{
    protected $table = 'finance_project_budget_items';

    protected $fillable = [
        'finance_project_budget_id',
        'chart_of_account_id',
        'planned_amount',
        'notes',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
    ];

    public function financeProjectBudget(): BelongsTo
    {
        return $this->belongsTo(FinanceProjectBudget::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
