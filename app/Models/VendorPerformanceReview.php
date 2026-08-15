<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'reviewed_by', 'review_period_start', 'review_period_end',
        'quality_rating', 'delivery_rating', 'pricing_rating', 'communication_rating',
        'overall_rating', 'remarks', 'status'
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'quality_rating' => 'decimal:1',
        'delivery_rating' => 'decimal:1',
        'pricing_rating' => 'decimal:1',
        'communication_rating' => 'decimal:1',
        'overall_rating' => 'decimal:1',
        'status' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
