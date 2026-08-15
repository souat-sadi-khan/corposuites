<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBankAccount extends Model
{
    use HasFactory;

    protected $table = 'finance_bank_accounts';

    protected $fillable = [
        'bank_name', 'account_name', 'account_number', 'branch', 'ifsc_swift_code',
        'currency', 'opening_balance', 'chart_of_account_id', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
