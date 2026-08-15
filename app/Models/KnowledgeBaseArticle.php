<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticle extends Model
{
    protected $table = 'knowledge_base_articles';

    public const VISIBILITIES = ['internal', 'public'];

    public const STATUSES = ['draft', 'published', 'archived'];

    protected $fillable = [
        'title',
        'slug',
        'knowledge_base_category_id',
        'ticket_category_id',
        'authored_by',
        'excerpt',
        'content',
        'visibility',
        'article_status',
        'published_at',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function getVisibilityLabelAttribute(): string
    {
        return ucfirst($this->visibility);
    }

    public function getArticleStatusLabelAttribute(): string
    {
        return ucfirst($this->article_status);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'knowledge_base_category_id');
    }

    /**
     * Optional cross-reference to a Support ticket category — never
     * required, purely for linking related articles to a ticket topic.
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'authored_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
