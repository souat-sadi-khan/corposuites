<?php

namespace App\Models;

use App\Contracts\GlobalSearchable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard = 'admin';
    protected $guard_name = 'admin';

    protected $fillable = [
        'employee_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'last_login_at',
        'last_login_ip',
        'two_factor_secret',
        'two_factor_enabled'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(AdminProfile::class, 'id', 'admin_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
