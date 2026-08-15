<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'resignation_date', 'last_working_date', 'notice_period_days', 'reason', 'status'
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_date' => 'date',
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
