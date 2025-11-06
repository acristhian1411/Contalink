<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

trait SecureLogging
{
    /**
     * Log a security event with standardized format.
     */
    protected function logSecurityEvent(
        string $event,
        string $level = 'warning',
        array $context = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = array_merge([
            'event' => $event,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('security')->{$level}($event, $logContext);
    }

    /**
     * Log an audit event for business operations.
     */
    protected function logAuditEvent(
        string $action,
        string $resource,
        array $context = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = array_merge([
            'action' => $action,
            'resource' => $resource,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('audit')->info("User performed {$action} on {$resource}", $logContext);
    }

    /**
     * Log authentication events.
     */
    protected function logAuthenticationEvent(
        string $event,
        bool $success = true,
        array $context = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = array_merge([
            'event' => $event,
            'success' => $success,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context);

        $level = $success ? 'info' : 'warning';
        Log::channel('authentication')->{$level}($event, $logContext);
    }

    /**
     * Log data access events for sensitive information.
     */
    protected function logDataAccess(
        string $dataType,
        string $action,
        array $identifiers = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = [
            'data_type' => $dataType,
            'action' => $action,
            'identifiers' => $identifiers,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('audit')->info("Data access: {$action} on {$dataType}", $logContext);
    }

    /**
     * Log file operations for security monitoring.
     */
    protected function logFileOperation(
        string $operation,
        string $filename,
        array $context = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = array_merge([
            'operation' => $operation,
            'filename' => $filename,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('security')->info("File operation: {$operation}", $logContext);
    }

    /**
     * Log business transaction events.
     */
    protected function logBusinessTransaction(
        string $transactionType,
        string $transactionId,
        array $details = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = array_merge([
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ], $details);

        Log::channel('audit')->info("Business transaction: {$transactionType}", $logContext);
    }

    /**
     * Log permission changes and role assignments.
     */
    protected function logPermissionChange(
        string $action,
        string $targetUserId,
        array $permissions = [],
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = [
            'action' => $action,
            'target_user_id' => $targetUserId,
            'permissions' => $permissions,
            'performed_by' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning("Permission change: {$action}", $logContext);
    }

    /**
     * Log configuration changes.
     */
    protected function logConfigurationChange(
        string $setting,
        mixed $oldValue,
        mixed $newValue,
        Request $request = null
    ): void {
        $request = $request ?: request();
        
        $logContext = [
            'setting' => $setting,
            'old_value' => $this->sanitizeLogValue($oldValue),
            'new_value' => $this->sanitizeLogValue($newValue),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('audit')->warning("Configuration change: {$setting}", $logContext);
    }

    /**
     * Sanitize values for logging (remove sensitive information).
     */
    protected function sanitizeLogValue(mixed $value): mixed
    {
        if (is_string($value)) {
            // Check if it looks like a password or token
            if (str_contains(strtolower($value), 'password') || 
                str_contains(strtolower($value), 'token') ||
                strlen($value) > 50) {
                return '[REDACTED]';
            }
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeLogValue($item);
            }
        }

        return $value;
    }
}