<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $isInstalled = filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);

        // if (!app()->runningInConsole() && !$isInstalled) {
        //     if (!$request->routeIs('install') && !$request->is('install*')) {
        //         return redirect()->route('install');
        //     }
        // }

        return $next($request);
    }
}
