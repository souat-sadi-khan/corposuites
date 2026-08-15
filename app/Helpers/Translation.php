<?php

use App\Services\TranslationService;

if (! function_exists('t')) {

    function t(string $key): string
    {
        return app(
            TranslationService::class
        )->get($key);
    }

}
