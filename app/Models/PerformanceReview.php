<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'reviewer_id', 'review_period_start', 'review_period_end',
        'rating', 'strengths', 'areas_for_improvement', 'goals', 'status'
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'rating' => 'decimal:1',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
