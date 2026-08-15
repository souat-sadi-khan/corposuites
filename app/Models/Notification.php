<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['activity_log_id', 'title', 'message', 'is_read'];
}
