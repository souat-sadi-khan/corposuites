<?php

namespace App\Models\Lang;

use Illuminate\Database\Eloquent\Model;

class TranslationKey extends Model
{
    protected $fillable = [

        'group_id',
        'key',
        'description'
    ];

    public function group()
    {
        return $this->belongsTo(TranslationGroup::class);
    }

    public function values()
    {
        return $this->hasMany(
            TranslationValue::class
        );
    }
}
