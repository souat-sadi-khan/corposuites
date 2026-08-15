<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model implements Approvable
{
    use HasFactory, HasWorkflow;

    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'request_number', 'requested_by', 'department_id', 'required_date', 'reason',
        'request_status', 'notes', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'required_date' => 'date',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function approvalPayload(): array
    {
        return [
            'request_number' => $this->request_number,
            'requested_by' => $this->requestedBy->name ?? null,
            'department' => $this->department->name ?? null,
            'required_date' => $this->required_date,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'purchase_request';
    }
}
