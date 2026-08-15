<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'from_department', 'to_department',
        'from_designation', 'to_designation', 'transfer_date', 'reason', 'status'
    ];

    protected $casts = [
        'transfer_date' => 'date',
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
