<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'posted', 'cancelled'];

    protected $fillable = [
        'entry_number', 'entry_date', 'reference', 'narration', 'total_debit', 'total_credit',
        'entry_status', 'created_by', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
