<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseCategory extends Model
{
    protected $table = 'knowledge_base_categories';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
