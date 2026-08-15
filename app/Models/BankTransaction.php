<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    public const TYPES = ['deposit', 'withdrawal'];

    protected $fillable = [
        'finance_bank_account_id', 'transaction_date', 'transaction_type', 'amount', 'reference',
        'description', 'journal_entry_id', 'reconciled', 'reconciled_date', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'reconciled' => 'boolean',
        'transaction_date' => 'date',
        'reconciled_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function financeBankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceBankAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
