<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    public const ACCOUNT_TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    /**
     * Fixed accounting rule: asset/expense accounts increase with a debit,
     * liability/equity/revenue accounts increase with a credit. This never
     * varies per-account, so it is derived from account_type via an accessor
     * rather than stored as a column that could drift out of sync.
     */
    public const NORMAL_BALANCE_MAP = [
        'asset' => 'debit',
        'expense' => 'debit',
        'liability' => 'credit',
        'equity' => 'credit',
        'revenue' => 'credit',
    ];

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'parent_id', 'code', 'name', 'account_type', 'account_type_id', 'is_group', 'description', 'status'
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'status' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getNormalBalanceAttribute(): string
    {
        return self::NORMAL_BALANCE_MAP[$this->account_type] ?? 'debit';
    }

    /**
     * All descendant ids (children, grandchildren, ...) of this account.
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
     * Build an indented [id => label] option list for a parent-account
     * dropdown, from a flat collection of accounts (avoids N+1 queries in
     * the view).
     */
    public static function indentedOptions($accounts, ?int $parentId = null, int $depth = 0): array
    {
        $options = [];

        foreach ($accounts->where('parent_id', $parentId) as $account) {
            $options[$account->id] = str_repeat('— ', $depth) . $account->code . ' - ' . $account->name;
            $options += static::indentedOptions($accounts, $account->id, $depth + 1);
        }

        return $options;
    }
}
