<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
