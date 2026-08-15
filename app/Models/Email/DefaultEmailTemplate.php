<?php

namespace App\Models\Email;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefaultEmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'default_email_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'key',
        'category',
        'subject',
        'body',
        'variables',
        'description',
        'is_system',
        'status',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'variables' => 'array',      // Automatically cast JSON to array
        'is_system' => 'boolean',
        'status' => 'integer',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // Optional: You can add helper methods or scopes here
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
