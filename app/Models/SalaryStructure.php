<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'effective_date', 'basic_salary', 'gross_salary', 'status'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalaryStructureItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
