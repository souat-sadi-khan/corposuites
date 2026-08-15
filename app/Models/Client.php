<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    public const TYPES = ['individual', 'company'];

    protected $fillable = [
        'client_code',
        'name',
        'client_type',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'website',
        'tax_number',
        'city',
        'country',
        'address',
        'notes',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Human-readable client type (the enum stores lowercase).
     */
    public function getClientTypeLabelAttribute(): string
    {
        return ucfirst($this->client_type);
    }

    /**
     * City / country folded into one readable line, omitting whichever
     * part is not recorded. Computed rather than stored, in keeping with
     * the project's preference for deriving anything derivable.
     */
    public function getLocationAttribute(): ?string
    {
        $parts = array_filter([$this->city, $this->country]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
