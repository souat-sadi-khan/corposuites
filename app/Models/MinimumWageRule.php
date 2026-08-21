<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MinimumWageRule extends Model
{
    use HasFactory;

    public const WAGE_TYPES = ['daily', 'monthly'];

    protected $fillable = [
        'country', 'state', 'wage_type', 'minimum_wage', 'effective_date', 'description', 'status',
    ];

    protected $casts = [
        'minimum_wage' => 'decimal:2',
        'effective_date' => 'date',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getWageTypeLabelAttribute(): string
    {
        return $this->wage_type === 'daily' ? 'Daily' : 'Monthly';
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->state ? $this->state . ', ' . $this->country : $this->country . ' (all states)';
    }

    /**
     * The rule currently in force for a given employee location + wage type,
     * as of a given date (defaults to today). A state-specific rule always
     * wins over a country-wide one for that same state; within either
     * scope, the latest effective_date not after $asOfDate wins — mirroring
     * exactly how SalaryStructure resolves its own "active" structure via
     * ->active()->orderByDesc('effective_date')->first().
     *
     * Matching is case-insensitive so "USA" and "usa" aren't treated as
     * different countries by accident.
     */
    public static function resolveApplicable(?string $country, ?string $state, string $wageType, ?string $asOfDate = null): ?self
    {
        if (! $country) {
            return null;
        }

        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();

        $base = static::query()
            ->active()
            ->where('wage_type', $wageType)
            ->whereRaw('LOWER(country) = ?', [mb_strtolower($country)])
            ->whereDate('effective_date', '<=', $asOfDate->toDateString());

        if ($state) {
            $stateSpecific = (clone $base)
                ->whereRaw('LOWER(state) = ?', [mb_strtolower($state)])
                ->orderByDesc('effective_date')
                ->first();

            if ($stateSpecific) {
                return $stateSpecific;
            }
        }

        return $base->whereNull('state')
            ->orderByDesc('effective_date')
            ->first();
    }
}
