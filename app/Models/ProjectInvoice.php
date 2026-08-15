<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectInvoice extends Model
{
    protected $table = 'project_invoices';

    public const STATUSES = ['draft', 'sent', 'partially_paid', 'paid', 'cancelled'];

    /**
     * A bill that can no longer take a payment or be reconsidered — shared
     * by the accessors below and the service's guards so both can never
     * disagree about what "closed" means.
     */
    public const CLOSED_STATUSES = ['paid', 'cancelled'];

    protected $fillable = [
        'invoice_number',
        'project_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'amount_paid',
        'invoice_status',
        'notes',
        'status',
    ];

    protected $appends = ['balance_due'];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function getInvoiceStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->invoice_status));
    }

    /**
     * Computed, not stored — same reasoning as SalesInvoice/PurchaseInvoice's
     * own balance_due: a stored figure could drift from amount_paid/
     * grand_total the moment either changed independently.
     */
    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->grand_total - (float) $this->amount_paid, 2);
    }

    /**
     * Past its due date, still owed money, and not already closed out.
     * Computed rather than stored — same reasoning as every other
     * is_overdue accessor in this project (Project, ProjectMilestone,
     * ProjectTask): a stored flag would need a scheduled job to become
     * true as the date passes.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date || $this->balance_due <= 0 || in_array($this->invoice_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $this->due_date->isPast();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectInvoiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
