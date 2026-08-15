<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTeamMember extends Model
{
    protected $table = 'project_team_members';

    public const ROLES = ['lead', 'member', 'analyst', 'developer', 'designer', 'tester', 'consultant', 'support'];

    protected $fillable = [
        'project_id',
        'employee_id',
        'team_role',
        'allocation_percent',
        'joined_date',
        'left_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'allocation_percent' => 'decimal:2',
        'joined_date' => 'date',
        'left_date' => 'date',
        'status' => 'boolean',
    ];

    public function getTeamRoleLabelAttribute(): string
    {
        return ucfirst($this->team_role);
    }

    /**
     * Still on the team: no departure date recorded, or one that has not
     * arrived yet. Computed rather than stored — a stored flag would need a
     * scheduled job to flip as a future leaving date passes (same reasoning
     * as AssetAssignment::is_overdue / Project::is_overdue).
     */
    public function getIsCurrentAttribute(): bool
    {
        return ! $this->left_date || ! $this->left_date->isPast();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Members who have not left (or whose leaving date is still ahead).
     */
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('left_date')
                ->orWhereDate('left_date', '>=', now()->toDateString());
        });
    }
}
