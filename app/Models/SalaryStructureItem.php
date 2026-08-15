<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_structure_id', 'salary_component_id', 'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
