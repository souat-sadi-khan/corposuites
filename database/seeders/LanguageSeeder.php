<?php

namespace Database\Seeders;

use App\Models\Lang\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'name'         => 'English',
                'native_name'  => 'English',
                'code'         => 'en',
                'flag'         => 'gb',
                'direction'    => 'ltr',
                'is_default'   => true,
                'is_active'    => true,
                'sort_order'   => 1,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
