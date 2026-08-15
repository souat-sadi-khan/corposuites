<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudgetItem extends Model
{
    protected $table = 'project_budget_items';

    public const CATEGORIES = ['labour', 'materials', 'equipment', 'subcontract', 'travel', 'software', 'other'];

    protected $fillable = [
        'project_budget_id',
        'category',
        'description',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return ucfirst($this->category);
    }

    public function projectBudget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class);
    }
}
