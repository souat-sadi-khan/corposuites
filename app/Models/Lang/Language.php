<?php

namespace App\Models\Lang;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'native_name',
        'code',
        'flag',
        'direction',
        'is_default',
        'is_active',
        'sort_order'
    ];

    public function translations()
    {
        return $this->hasMany(TranslationValue::class);
    }
}
