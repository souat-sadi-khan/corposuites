<?php

namespace App\Models\Email;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailSenderIdentity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'email',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function provider()
    {
        return $this->belongsTo(EmailProvider::class);
    }

    public function testLogs()
    {
        return $this->hasMany(EmailTestLog::class);
    }

    /**
     * Simple helper
     */
    public function getFullName(): string
    {
        return $this->name ? "{$this->name} <{$this->email}>" : $this->email;
    }
}
