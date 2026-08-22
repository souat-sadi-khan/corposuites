<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'max_amount_per_claim', 'receipt_required_above',
        'chart_of_account_id', 'status',
    ];

    protected $casts = [
        'max_amount_per_claim' => 'decimal:2',
        'receipt_required_above' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
