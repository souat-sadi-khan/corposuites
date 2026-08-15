<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'from_designation', 'to_designation',
        'from_salary', 'to_salary', 'promotion_date', 'remarks', 'status'
    ];

    protected $casts = [
        'from_salary' => 'decimal:2',
        'to_salary' => 'decimal:2',
        'promotion_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
