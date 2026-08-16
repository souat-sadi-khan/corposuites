<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
