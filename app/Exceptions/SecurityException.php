<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SecurityException extends Exception
{
    protected string $securityReason;
    protected array $context;

    public function __construct(
        string $message = 'Security violation detected',
        string $securityReason = 'unknown',
        array $context = [],
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->securityReason = $securityReason;
        $this->context = $context;
    }

    /**
     * Get the security reason for this exception.
     */
    public function getSecurityReason(): string
    {
        return $this->securityReason;
    }

    /**
     * Get additional context for this exception.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        // Log the security event
        $this->logSecurityEvent($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acceso denegado por razones de seguridad.',
                'error_code' => 'SECURITY_VIOLATION',
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'Acceso denegado por razones de seguridad.');
    }

    /**
     * Log the security event with context.
     */
    protected function logSecurityEvent(Request $request): void
    {
        $logContext = array_merge($this->context, [
            'security_reason' => $this->securityReason,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ]);

        \Log::channel('security')->warning('Security Exception: ' . $this->getMessage(), $logContext);
    }

    /**
     * Create a mass assignment security exception.
     */
    public static function massAssignment(string $model, array $attemptedFields): self
    {
        return new self(
            "Mass assignment attempt detected on model: {$model}",
            'mass_assignment',
            ['model' => $model, 'attempted_fields' => $attemptedFields]
        );
    }

    /**
     * Create a file upload security exception.
     */
    public static function fileUpload(string $reason, array $fileInfo = []): self
    {
        return new self(
            "Insecure file upload attempt: {$reason}",
            'file_upload',
            ['file_info' => $fileInfo]
        );
    }

    /**
     * Create an input validation security exception.
     */
    public static function inputValidation(string $field, string $reason): self
    {
        return new self(
            "Input validation security violation on field: {$field}",
            'input_validation',
            ['field' => $field, 'reason' => $reason]
        );
    }

    /**
     * Create an unauthorized access security exception.
     */
    public static function unauthorizedAccess(string $resource, string $action): self
    {
        return new self(
            "Unauthorized access attempt to {$resource} for action: {$action}",
            'unauthorized_access',
            ['resource' => $resource, 'action' => $action]
        );
    }

    /**
     * Create a rate limiting security exception.
     */
    public static function rateLimitExceeded(string $endpoint, int $attempts): self
    {
        return new self(
            "Rate limit exceeded for endpoint: {$endpoint}",
            'rate_limit',
            ['endpoint' => $endpoint, 'attempts' => $attempts]
        );
    }
}