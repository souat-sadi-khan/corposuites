<?php

namespace App\Models;

use App\Contracts\GlobalSearchable;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'module',
        'action',
        'model',
        'model_id',
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'meta'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'meta' => 'array'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'actor_id');
    }

    protected static function booted()
    {
        static::created(function ($activityLog) {
            Notification::create([
                'activity_log_id' => $activityLog->id,
                'title' => "New Action in " . ucfirst($activityLog->module),
                'message' => $activityLog->description ?: "Action: " . $activityLog->action,
            ]);
        });
    }
}
