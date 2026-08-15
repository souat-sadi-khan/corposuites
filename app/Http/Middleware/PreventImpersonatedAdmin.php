<?php

namespace App\Http\Middleware;

use Closure;

class PreventImpersonatedAdmin
{

    public function handle($request, Closure $next)
    {
        if(session('impersonating') && $request->is('admin/settings/*')) {
            abort(403, 'Not allowed during impersonation');
        }

        return $next($request);
    }
}
