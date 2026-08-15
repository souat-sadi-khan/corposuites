<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait ActivityLogger
{

    public function logActivity(array $data)
    {
        ActivityLog::create([
            'actor_type'    => $data['actor_type'] ?? (auth()->guard('admin')->check() ? 'admin' : 'system'),
            'actor_id'      => $data['actor_id'] ?? null,
            'module'        => $data['module'] ?? null,
            'action'        => $data['action'],
            'model'         => $data['model'] ?? null,
            'model_id'      => $data['model_id'] ?? null,
            'description'   => $data['description'] ?? null,
            'old_data'      => $data['old_data'] ?? null,
            'new_data'      => $data['new_data'] ?? null,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'url'           => request()->fullUrl(),
            'method'        => request()->method(),
            'meta'          => $data['meta'] ?? null
        ]);
    }

}
