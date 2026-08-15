<?php

namespace Database\Seeders;

use App\Models\Lang\TranslationGroup;
use Illuminate\Database\Seeder;

class TranslationGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [

            ['name' => 'Common',      'slug' => 'common'],
            ['name' => 'Auth',        'slug' => 'auth'],
            ['name' => 'Dashboard',   'slug' => 'dashboard'],
            ['name' => 'Users',       'slug' => 'users'],
            ['name' => 'Roles',       'slug' => 'roles'],
            ['name' => 'Permissions', 'slug' => 'permissions'],
            ['name' => 'Settings',    'slug' => 'settings'],
            ['name' => 'Validation',  'slug' => 'validation'],
            ['name' => 'Messages',    'slug' => 'messages'],
            ['name' => 'Menu',        'slug' => 'menu'],

        ];

        foreach ($groups as $index => $group) {

            TranslationGroup::updateOrCreate(
                [
                    'slug' => $group['slug']
                ],
                [
                    'name'       => $group['name'],
                    'sort_order' => $index + 1,
                ]
            );

        }
    }
}
