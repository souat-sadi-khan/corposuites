<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    protected $table = 'tax_rates';

    public const TAX_TYPES = ['exclusive', 'inclusive'];

    public const APPLIES_TO = ['sales', 'purchase', 'both'];

    protected $fillable = [
        'name',
        'code',
        'rate',
        'tax_type',
        'applies_to',
        'sales_account_id',
        'purchase_account_id',
        'effective_from',
        'effective_to',
        'is_compound',
        'description',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'status' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Whether the rate is in force today. Both date bounds are optional —
     * an open-ended rate (no dates at all) is always current. Computed
     * rather than stored, for the same reason every other derived figure
     * in this project is an accessor: a stored flag would need a scheduled
     * job to stay truthful as dates pass.
     */
    public function getIsCurrentAttribute(): bool
    {
        $today = now()->startOfDay();

        if ($this->effective_from && $today->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $today->gt($this->effective_to)) {
            return false;
        }

        return true;
    }

    /**
     * GL account that collected output tax is credited to (sales side).
     */
    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'sales_account_id');
    }

    /**
     * GL account that recoverable input tax is debited to (purchase side).
     */
    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
