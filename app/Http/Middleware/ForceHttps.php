<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only force HTTPS if configured and in production
        if (config('security.force_https', false) && 
            app()->environment('production') && 
            !$request->secure()) {
            
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}