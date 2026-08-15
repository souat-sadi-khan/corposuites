<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menus
    |--------------------------------------------------------------------------
    */

    'menus' => [

        [
            'title' => 'Dashboard',
            'url'   => 'admin.dashboard',
            'icon'  => 'ri-dashboard-line'
        ],

        [
            'title' => 'Settings',
            'url'   => 'admin.settings',
            'icon'  => 'ri-settings-3-line'
        ],

        [
            'title' => 'Profile',
            'url'   => 'admin.profile',
            'icon'  => 'ri-user-settings-line'
        ],
        [
            'title' => 'Role & Permission',
            'url'   => 'admin.roles.index',
            'icon'  => 'ri-shield-user-line'
        ],
        [
            'title' => 'Stuff',
            'url'   => 'admin.stuff.index',
            'icon'  => 'ri-group-line'
        ],
        [
            'title' => 'Notifications',
            'url'   => 'admin.notifications',
            'icon'  => 'ri-notification-3-line'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    */

    'models' => [
        App\Models\Admin::class,
        App\Models\Lang\Language::class,
    ],

];
