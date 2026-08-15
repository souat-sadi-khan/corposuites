<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'name', 'description', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * All descendant ids (children, grandchildren, ...) of this category.
     */
    public function descendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /**
     * Build an indented [id => label] option list for a parent-category dropdown,
     * from a flat collection of categories (avoids N+1 queries in the view).
     */
    public static function indentedOptions($categories, ?int $parentId = null, int $depth = 0): array
    {
        $options = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $options[$category->id] = str_repeat('— ', $depth) . $category->name;
            $options += static::indentedOptions($categories, $category->id, $depth + 1);
        }

        return $options;
    }
}
