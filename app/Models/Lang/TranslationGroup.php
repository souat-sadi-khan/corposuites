<?php

namespace App\Models\Lang;

use Illuminate\Database\Eloquent\Model;

class TranslationGroup extends Model
{
    protected $fillable = [

        'name',
        'slug',
        'sort_order'
    ];

    public function keys()
    {
        return $this->hasMany(TranslationKey::class,'group_id');
    }
}
