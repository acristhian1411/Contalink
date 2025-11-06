<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        SecurityException::class => 'critical',
        AuthenticationException::class => 'warning',
        AuthorizationException::class => 'warning',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        ThrottleRequestsException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log security-related exceptions with additional context
            if ($this->isSecurityException($e)) {
                $this->logSecurityEvent($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse
    {
        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        // Handle web requests
        return $this->handleWebException($request, $e);
    }

    /**
     * Handle API exceptions with secure error responses.
     */
    protected function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        // Validation errors
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $exception->errors(),
            ], 422);
        }

        // Authentication errors
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'No autenticado. Por favor, inicie sesión.',
            ], 401);
        }

        // Authorization errors
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        // Rate limiting errors
        if ($exception instanceof ThrottleRequestsException) {
            return response()->json([
                'message' => 'Demasiadas solicitudes. Intente nuevamente más tarde.',
                'retry_after' => $exception->getHeaders()['Retry-After'] ?? 60,
            ], 429);
        }

        // Model not found errors
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'El recurso solicitado no fue encontrado.',
            ], 404);
        }

        // Not found errors
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Endpoint no encontrado.',
            ], 404);
        }

        // Method not allowed errors
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Método HTTP no permitido.',
            ], 405);
        }

        // Security exceptions
        if ($exception instanceof SecurityException) {
            return response()->json([
                'message' => 'Acceso denegado por razones de seguridad.',
            ], 403);
        }

        // Generic server errors
        if (app()->environment('production')) {
            return response()->json([
                'message' => 'Error interno del servidor. Por favor, contacte al administrador.',
            ], 500);
        }

        // Development environment - show detailed error
        return response()->json([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], 500);
    }

    /**
     * Handle web exceptions with secure error responses.
     */
    protected function handleWebException(Request $request, Throwable $exception): Response|RedirectResponse
    {
        // Authentication errors
        if ($exception instanceof AuthenticationException) {
            return redirect()->route('login')
                ->with('error', 'Debe iniciar sesión para acceder a esta página.');
        }

        // Authorization errors
        if ($exception instanceof AuthorizationException) {
            return back()->with('error', 'No tiene permisos para realizar esta acción.');
        }

        // Security exceptions
        if ($exception instanceof SecurityException) {
            return redirect()->route('dashboard')
                ->with('error', 'Acceso denegado por razones de seguridad.');
        }

        // Rate limiting errors
        if ($exception instanceof ThrottleRequestsException) {
            return back()->with('error', 'Demasiadas solicitudes. Intente nuevamente más tarde.');
        }

        // Let parent handle other exceptions
        return parent::render($request, $exception);
    }

    /**
     * Check if an exception is security-related.
     */
    protected function isSecurityException(Throwable $exception): bool
    {
        return $exception instanceof SecurityException ||
               $exception instanceof AuthenticationException ||
               $exception instanceof AuthorizationException ||
               $exception instanceof ThrottleRequestsException ||
               $this->containsSecurityKeywords($exception->getMessage());
    }

    /**
     * Check if exception message contains security-related keywords.
     */
    protected function containsSecurityKeywords(string $message): bool
    {
        $securityKeywords = [
            'sql injection', 'xss', 'csrf', 'unauthorized', 'forbidden',
            'mass assignment', 'file upload', 'path traversal', 'malware'
        ];

        $lowerMessage = strtolower($message);
        
        foreach ($securityKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log security events with additional context.
     */
    protected function logSecurityEvent(Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'input' => $this->sanitizeLogInput(request()->all()),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->critical('Security Exception Detected', $context);
    }

    /**
     * Sanitize input data for logging (remove sensitive information).
     */
    protected function sanitizeLogInput(array $input): array
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

        // Recursively sanitize nested arrays
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->sanitizeLogInput($value);
            }
        }

        return $input;
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response|JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No autenticado. Por favor, inicie sesión.',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}