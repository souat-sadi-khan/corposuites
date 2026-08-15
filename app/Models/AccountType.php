<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use HasFactory;

    public const NATURES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    protected $fillable = [
        'name', 'nature', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'account_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
