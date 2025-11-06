<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityAuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log the incoming request
        $this->logIncomingRequest($request);
        
        $response = $next($request);
        
        // Log the response
        $this->logResponse($request, $response, $startTime);
        
        return $response;
    }

    /**
     * Log incoming security-sensitive requests.
     */
    protected function logIncomingRequest(Request $request): void
    {
        // Only log security-sensitive endpoints
        if (!$this->isSecuritySensitive($request)) {
            return;
        }

        $context = [
            'type' => 'incoming_request',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'input_size' => strlen(json_encode($request->all())),
            'timestamp' => now()->toISOString(),
        ];

        // Add sanitized input for certain endpoints
        if ($this->shouldLogInput($request)) {
            $context['input'] = $this->sanitizeInput($request->all());
        }

        Log::channel('security')->info('Security-sensitive request', $context);
    }

    /**
     * Log response for security analysis.
     */
    protected function logResponse(Request $request, Response $response, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Log failed authentication attempts
        if ($response->getStatusCode() === 401) {
            $this->logFailedAuthentication($request, $executionTime);
        }
        
        // Log authorization failures
        if ($response->getStatusCode() === 403) {
            $this->logAuthorizationFailure($request, $executionTime);
        }
        
        // Log validation errors on security-sensitive endpoints
        if ($response->getStatusCode() === 422 && $this->isSecuritySensitive($request)) {
            $this->logValidationFailure($request, $executionTime);
        }
        
        // Log suspicious activity (multiple errors, long execution times)
        if ($this->isSuspiciousActivity($request, $response, $executionTime)) {
            $this->logSuspiciousActivity($request, $response, $executionTime);
        }
    }

    /**
     * Check if the request is security-sensitive.
     */
    protected function isSecuritySensitive(Request $request): bool
    {
        $sensitivePatterns = [
            '/api/auth/',
            '/api/users',
            '/api/sales',
            '/api/purchases',
            '/login',
            '/register',
            '/password/',
            '/admin/',
        ];

        $path = $request->path();
        
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($path, trim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if input should be logged for this request.
     */
    protected function shouldLogInput(Request $request): bool
    {
        // Log input for authentication and user management endpoints
        return str_contains($request->path(), 'auth') || 
               str_contains($request->path(), 'login') ||
               str_contains($request->path(), 'register');
    }

    /**
     * Sanitize input data for logging.
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_token', 'access_token', 'refresh_token',
            'credit_card', 'ssn', 'social_security', 'bank_account'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '[REDACTED]';
            }
        }

        return $input;
    }

    /**
     * Log failed authentication attempts.
     */
    protected function logFailedAuthentication(Request $request, float $executionTime): void
    {
        $context = [
            'type' => 'failed_authentication',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'execution_time_ms' => round($executionTime, 2),
            'attempted_email' => $request->input('email'),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('authentication')->warning('Failed authentication attempt', $context);
    }

    /**
     * Log authorization failures.
     */
    protected function logAuthorizationFailure(Request $request, float $executionTime): void
    {
        $context = [
            'type' => 'authorization_failure',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
            'execution_time_ms' => round($executionTime, 2),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning('Authorization failure', $context);
    }

    /**
     * Log validation failures on security-sensitive endpoints.
     */
    protected function logValidationFailure(Request $request, float $executionTime): void
    {
        $context = [
            'type' => 'validation_failure',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
            'execution_time_ms' => round($executionTime, 2),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->info('Validation failure on security-sensitive endpoint', $context);
    }

    /**
     * Check if the activity is suspicious.
     */
    protected function isSuspiciousActivity(Request $request, Response $response, float $executionTime): bool
    {
        // Long execution times might indicate attacks
        if ($executionTime > 5000) { // 5 seconds
            return true;
        }

        // Multiple error responses from same IP
        $statusCode = $response->getStatusCode();
        if (in_array($statusCode, [400, 401, 403, 422, 429, 500])) {
            // Check if this IP has had multiple errors recently
            return $this->hasRecentErrors($request->ip());
        }

        return false;
    }

    /**
     * Check if IP has recent error responses.
     */
    protected function hasRecentErrors(string $ip): bool
    {
        // This is a simplified check - in production, you might use Redis or database
        $cacheKey = "security_errors_{$ip}";
        $errors = cache()->get($cacheKey, 0);
        
        if ($errors >= 5) { // 5 errors in the time window
            return true;
        }
        
        // Increment error count with 5-minute expiry
        cache()->put($cacheKey, $errors + 1, now()->addMinutes(5));
        
        return false;
    }

    /**
     * Log suspicious activity.
     */
    protected function logSuspiciousActivity(Request $request, Response $response, float $executionTime): void
    {
        $context = [
            'type' => 'suspicious_activity',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => round($executionTime, 2),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning('Suspicious activity detected', $context);
    }
}