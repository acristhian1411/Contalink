<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\SecurityException;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize all input data
        $sanitized = $this->sanitizeData($request->all());
        
        // Replace the request input with sanitized data
        $request->replace($sanitized);
        
        // Validate for potential security threats
        $this->validateForSecurityThreats($request);
        
        return $next($request);
    }

    /**
     * Recursively sanitize input data.
     */
    protected function sanitizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value, $key);
            }
        }

        return $data;
    }

    /**
     * Sanitize a string value based on its context.
     */
    protected function sanitizeString(string $value, string $key): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Handle different field types
        if ($this->isEmailField($key)) {
            return $this->sanitizeEmail($value);
        }
        
        if ($this->isUrlField($key)) {
            return $this->sanitizeUrl($value);
        }
        
        if ($this->isNumericField($key)) {
            return $this->sanitizeNumeric($value);
        }
        
        if ($this->isTextContentField($key)) {
            return $this->sanitizeTextContent($value);
        }
        
        if ($this->isCodeField($key)) {
            return $this->sanitizeCode($value);
        }
        
        // Default sanitization for regular text fields
        return $this->sanitizeGeneralText($value);
    }

    /**
     * Check if field is an email field.
     */
    protected function isEmailField(string $key): bool
    {
        return str_contains(strtolower($key), 'email') || 
               str_contains(strtolower($key), 'mail');
    }

    /**
     * Check if field is a URL field.
     */
    protected function isUrlField(string $key): bool
    {
        return str_contains(strtolower($key), 'url') || 
               str_contains(strtolower($key), 'link') ||
               str_contains(strtolower($key), 'website');
    }

    /**
     * Check if field is numeric.
     */
    protected function isNumericField(string $key): bool
    {
        $numericPatterns = ['price', 'amount', 'qty', 'quantity', 'id', 'number', 'count'];
        
        foreach ($numericPatterns as $pattern) {
            if (str_contains(strtolower($key), $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if field contains text content that might have HTML.
     */
    protected function isTextContentField(string $key): bool
    {
        $contentFields = ['desc', 'description', 'content', 'body', 'message', 'notes', 'comment'];
        
        foreach ($contentFields as $field) {
            if (str_contains(strtolower($key), $field)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if field contains code or identifiers.
     */
    protected function isCodeField(string $key): bool
    {
        $codeFields = ['code', 'barcode', 'sku', 'reference', 'token'];
        
        foreach ($codeFields as $field) {
            if (str_contains(strtolower($key), $field)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sanitize email addresses.
     */
    protected function sanitizeEmail(string $value): string
    {
        // Remove any HTML tags
        $value = strip_tags($value);
        
        // Convert to lowercase
        $value = strtolower($value);
        
        // Remove any characters that aren't valid in email addresses
        $value = preg_replace('/[^a-z0-9@._+-]/', '', $value);
        
        return $value;
    }

    /**
     * Sanitize URLs.
     */
    protected function sanitizeUrl(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);
        
        // Only allow http and https protocols
        if (!empty($value) && !preg_match('/^https?:\/\//', $value)) {
            $value = 'http://' . $value;
        }
        
        // Validate URL format
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        return $value;
    }

    /**
     * Sanitize numeric values.
     */
    protected function sanitizeNumeric(string $value): string
    {
        // Remove any non-numeric characters except decimal point and minus sign
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        
        // Ensure only one decimal point
        $parts = explode('.', $value);
        if (count($parts) > 2) {
            $value = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        return $value;
    }

    /**
     * Sanitize text content that might contain HTML.
     */
    protected function sanitizeTextContent(string $value): string
    {
        // Remove potentially dangerous HTML tags
        $allowedTags = '<p><br><strong><em><u><ol><ul><li>';
        $value = strip_tags($value, $allowedTags);
        
        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any remaining script content
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        return $value;
    }

    /**
     * Sanitize code fields (alphanumeric only).
     */
    protected function sanitizeCode(string $value): string
    {
        // Allow only alphanumeric characters, hyphens, and underscores
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
    }

    /**
     * General text sanitization.
     */
    protected function sanitizeGeneralText(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);
        
        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $value;
    }

    /**
     * Validate input for potential security threats.
     */
    protected function validateForSecurityThreats(Request $request): void
    {
        $input = $request->all();
        
        // Check for SQL injection patterns
        $this->checkForSqlInjection($input);
        
        // Check for XSS patterns
        $this->checkForXss($input);
        
        // Check for path traversal
        $this->checkForPathTraversal($input);
        
        // Check for command injection
        $this->checkForCommandInjection($input);
        
        // Check for excessive input size
        $this->checkInputSize($input);
    }

    /**
     * Check for SQL injection patterns.
     */
    protected function checkForSqlInjection(array $input): void
    {
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\'|\")(\s*)(OR|AND)(\s*)(\'|\")/i',
        ];

        $this->checkPatterns($input, $sqlPatterns, 'SQL injection attempt detected');
    }

    /**
     * Check for XSS patterns.
     */
    protected function checkForXss(array $input): void
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
        ];

        $this->checkPatterns($input, $xssPatterns, 'XSS attempt detected');
    }

    /**
     * Check for path traversal patterns.
     */
    protected function checkForPathTraversal(array $input): void
    {
        $pathPatterns = [
            '/\.\.\//i',
            '/\.\.\\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/\.\.%2f/i',
            '/\.\.%5c/i',
        ];

        $this->checkPatterns($input, $pathPatterns, 'Path traversal attempt detected');
    }

    /**
     * Check for command injection patterns.
     */
    protected function checkForCommandInjection(array $input): void
    {
        $commandPatterns = [
            '/;\s*(rm|del|format|shutdown)/i',
            '/\|\s*(nc|netcat|wget|curl)/i',
            '/`[^`]*`/',
            '/\$\([^)]*\)/',
            '/&&\s*(rm|del|format)/i',
        ];

        $this->checkPatterns($input, $commandPatterns, 'Command injection attempt detected');
    }

    /**
     * Check patterns against input data.
     */
    protected function checkPatterns(array $input, array $patterns, string $message): void
    {
        $inputString = json_encode($input);
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                throw SecurityException::inputValidation('multiple_fields', $message);
            }
        }
    }

    /**
     * Check input size to prevent DoS attacks.
     */
    protected function checkInputSize(array $input): void
    {
        $inputSize = strlen(json_encode($input));
        $maxSize = 1024 * 1024; // 1MB limit
        
        if ($inputSize > $maxSize) {
            throw SecurityException::inputValidation('input_size', 'Input size exceeds maximum allowed limit');
        }
    }
}