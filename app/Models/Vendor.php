<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_code', 'vendor_group_id', 'name', 'email', 'phone', 'company_name', 'address', 'tax_number', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function vendorGroup(): BelongsTo
    {
        return $this->belongsTo(VendorGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
