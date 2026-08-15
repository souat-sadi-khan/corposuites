<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\File;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // $isInstalled = filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);

        // if (!app()->runningInConsole() && $isInstalled) {
        //     return redirect()->to('/');
        // }

        return $next($request);
    }

}
