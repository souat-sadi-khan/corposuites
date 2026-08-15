<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInvoiceItem extends Model
{
    protected $table = 'project_invoice_items';

    public const SOURCE_TYPES = ['time_entry', 'expense', 'manual'];

    protected $fillable = [
        'project_invoice_id',
        'source_type',
        'project_time_entry_id',
        'project_expense_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function projectInvoice(): BelongsTo
    {
        return $this->belongsTo(ProjectInvoice::class);
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(ProjectTimeEntry::class, 'project_time_entry_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(ProjectExpense::class, 'project_expense_id');
    }
}
