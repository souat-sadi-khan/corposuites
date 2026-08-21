<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegator_admin_id', 'delegate_admin_id', 'starts_on', 'ends_on', 'reason', 'status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'status' => 'boolean',
    ];

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'delegator_admin_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'delegate_admin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Active delegations covering the given date (defaults to today).
     */
    public function scopeCovering($query, $date = null)
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query->where('status', 1)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date);
    }
}
