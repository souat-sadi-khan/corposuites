<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_number', 'delivery_id', 'issued_date', 'received_by', 'received_date', 'remarks', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'issued_date' => 'date',
        'received_date' => 'date',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
