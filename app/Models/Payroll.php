<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'salary_structure_id', 'month', 'year', 'commission_sales_amount', 'basic_salary',
        'total_earnings', 'overtime_hours', 'overtime_amount', 'attendance_deduction', 'total_deductions', 'net_salary', 'payment_status', 'payment_date', 'status'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'commission_sales_amount' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'attendance_deduction' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
