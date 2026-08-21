<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'days_allowed', 'is_paid', 'description', 'status',
        // Accrual
        'accrual_method',
        // Carry-forward
        'allow_carry_forward', 'max_carry_forward', 'carry_forward_expiry_months',
        // Eligibility
        'min_service_days', 'applicable_gender',
        'applicable_employee_type_ids', 'applicable_designation_ids',
        // Request rules
        'min_notice_days', 'max_consecutive_days',
        'allow_half_day', 'requires_attachment', 'is_encashable',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'status' => 'boolean',
        'allow_carry_forward' => 'boolean',
        'allow_half_day' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_encashable' => 'boolean',
        'max_carry_forward' => 'decimal:2',
        'applicable_employee_type_ids' => 'array',
        'applicable_designation_ids' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Whether this leave type is restricted to specific employee types.
     */
    public function restrictsEmployeeType(): bool
    {
        return !empty($this->applicable_employee_type_ids);
    }

    /**
     * Whether this leave type is restricted to specific designations.
     */
    public function restrictsDesignation(): bool
    {
        return !empty($this->applicable_designation_ids);
    }
}
