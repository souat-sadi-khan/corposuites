<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectExpense extends Model
{
    protected $table = 'project_expenses';

    public const CATEGORIES = ['labour', 'materials', 'equipment', 'subcontract', 'travel', 'software', 'other'];

    public const APPROVAL_STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'project_id',
        'vendor_id',
        'employee_id',
        'title',
        'expense_category',
        'expense_date',
        'amount',
        'is_billable',
        'receipt_path',
        'approval_status',
        'approved_by',
        'approved_at',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'is_billable' => 'boolean',
        'approved_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function getExpenseCategoryLabelAttribute(): string
    {
        return ucfirst($this->expense_category);
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return ucfirst($this->approval_status);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * The Project Billing line this expense was billed on, if any — the
     * inverse of ProjectInvoiceItem::expense().
     */
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(ProjectInvoiceItem::class, 'project_expense_id');
    }

    /**
     * Billed on a Project Invoice that hasn't been cancelled — editing the
     * amount afterward would let it drift from what the client was actually
     * billed. Cancelling the invoice is what frees it back up.
     */
    public function getIsInvoicedAttribute(): bool
    {
        return (bool) ($this->invoiceItem
            && $this->invoiceItem->projectInvoice
            && $this->invoiceItem->projectInvoice->invoice_status !== 'cancelled');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Billable, approved spend — what Project Billing (the next module) and
     * Project Profitability Reports will presumably sum against a project.
     */
    public function scopeBillableApproved($query)
    {
        return $query->where('is_billable', true)->where('approval_status', 'approved');
    }
}
