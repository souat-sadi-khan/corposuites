<?php

namespace App\Models\Email;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'sender_identity_id',
        'recipient_email',
        'subject',
        'status',
        'error_message',
        'response',
        'sent_at',
    ];

    protected $casts = [
        'response' => 'json',
        'sent_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function provider()
    {
        return $this->belongsTo(EmailProvider::class);
    }

    public function senderIdentity()
    {
        return $this->belongsTo(EmailSenderIdentity::class);
    }

    /**
     * Simple helper
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
