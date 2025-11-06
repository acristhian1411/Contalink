<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | application. These settings help protect against common web
    | vulnerabilities and ensure secure operation in production.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When enabled, this will force all requests to use HTTPS in production.
    | This is essential for protecting sensitive data in transit.
    |
    */

    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | HSTS (HTTP Strict Transport Security)
    |--------------------------------------------------------------------------
    |
    | When enabled, this will add HSTS headers to force browsers to use
    | HTTPS for all future requests to the domain.
    |
    */

    'hsts_enabled' => env('HSTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for various security headers that help protect against
    | common web vulnerabilities.
    |
    */

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | CSP configuration to help prevent XSS attacks and other code injection
    | vulnerabilities.
    |
    */

    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'reporting_enabled' => env('CSP_REPORTING_ENABLED', true),
        'report_uri' => env('CSP_REPORT_URI', '/csp-report'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for API and authentication rate limiting to prevent
    | abuse and brute force attacks.
    |
    */

    'rate_limiting' => [
        'api_requests' => env('THROTTLE_API_REQUESTS', 60),
        'auth_requests' => env('THROTTLE_AUTH_REQUESTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Security Checks
    |--------------------------------------------------------------------------
    |
    | These settings help ensure the application is properly configured
    | for production environments.
    |
    */

    'production_checks' => [
        'require_https' => env('PRODUCTION_REQUIRE_HTTPS', true),
        'disable_debug' => env('PRODUCTION_DISABLE_DEBUG', true),
        'require_ssl_db' => env('PRODUCTION_REQUIRE_SSL_DB', true),
    ],

];