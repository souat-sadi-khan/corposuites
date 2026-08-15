<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'completed', 'cancelled'];

    protected $fillable = [
        'reconciliation_number', 'finance_bank_account_id', 'statement_date',
        'statement_opening_balance', 'statement_closing_balance', 'computed_closing_balance',
        'variance', 'reconciliation_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'statement_date' => 'date',
        'statement_opening_balance' => 'decimal:2',
        'statement_closing_balance' => 'decimal:2',
        'computed_closing_balance' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function financeBankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceBankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
