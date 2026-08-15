<?php

namespace App\Http\Middleware;

use App\Models\Lang\Language;
use Closure;
use Illuminate\Http\Request;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $language = session('language');

        if (!$language) {

            $language = Language::where(
                'is_default',
                true
            )->value('code');

            session([
                'language' => $language
            ]);

        }

        app()->setLocale($language);

        return $next($request);
    }
}
