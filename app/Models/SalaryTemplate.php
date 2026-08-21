<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryTemplate extends Model
{
    use HasFactory;

    public const PAY_TYPES = ['monthly', 'daily', 'commission'];

    protected $fillable = [
        'name', 'pay_type', 'basic_salary', 'gross_salary', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SalaryTemplateItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getPayTypeLabelAttribute(): string
    {
        return match ($this->pay_type) {
            'daily' => 'Daily',
            'commission' => 'Commission-based',
            default => 'Monthly',
        };
    }
}
