<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers for XSS and clickjacking protection
        if (config('security.headers.enabled', true)) {
            $response->headers->set('X-Frame-Options', config('security.headers.x_frame_options', 'DENY'));
            $response->headers->set('X-Content-Type-Options', config('security.headers.x_content_type_options', 'nosniff'));
            $response->headers->set('X-XSS-Protection', config('security.headers.x_xss_protection', '1; mode=block'));
            $response->headers->set('Referrer-Policy', config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));
        }

        // HSTS headers for HTTPS enforcement in production
        if (config('security.hsts_enabled', false) && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy for XSS protection
        if (config('security.csp.enabled', true)) {
            $csp = $this->buildContentSecurityPolicy();
            $response->headers->set('Content-Security-Policy', $csp);

            // CSP Reporting (only in production and if enabled)
            if (config('security.csp.reporting_enabled', true) && app()->environment('production')) {
                $reportOnlyCsp = $this->buildContentSecurityPolicy(true);
                $response->headers->set('Content-Security-Policy-Report-Only', $reportOnlyCsp);
            }
        }

        return $response;
    }

    /**
     * Build Content Security Policy header value
     *
     * @param bool $reportOnly Whether this is for report-only mode
     * @return string
     */
    private function buildContentSecurityPolicy(bool $reportOnly = false): string
    {
        $csp = [
            "default-src 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "media-src 'self'",
        ];

        // Development-specific CSP rules for Vite
        if (app()->environment('local', 'development')) {
            $csp[] = "style-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173";
            $csp[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";
            $csp[] = "connect-src 'self' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173";
        } else {
            // Production CSP - more restrictive
            $csp[] = "style-src 'self' 'unsafe-inline'";
            $csp[] = "script-src 'self' 'unsafe-inline'";
            $csp[] = "connect-src 'self'";
            
            // Add CSP reporting endpoint in production
            if ($reportOnly && config('security.csp.reporting_enabled', true)) {
                $reportUri = config('security.csp.report_uri', '/csp-report');
                $csp[] = "report-uri {$reportUri}";
                $csp[] = "report-to csp-endpoint";
            }
        }

        return implode('; ', $csp) . ';';
    }
}