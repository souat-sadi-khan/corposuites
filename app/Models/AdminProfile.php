<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    protected $guarded = [];

    // Relation
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
