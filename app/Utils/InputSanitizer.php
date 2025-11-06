<?php

namespace App\Utils;

class InputSanitizer
{
    /**
     * Sanitize a string for safe database storage and display.
     */
    public static function sanitizeString(string $input, array $options = []): string
    {
        $options = array_merge([
            'trim' => true,
            'remove_null_bytes' => true,
            'html_entities' => true,
            'strip_tags' => true,
            'allowed_tags' => '',
            'max_length' => null,
        ], $options);

        // Remove null bytes
        if ($options['remove_null_bytes']) {
            $input = str_replace("\0", '', $input);
        }

        // Trim whitespace
        if ($options['trim']) {
            $input = trim($input);
        }

        // Strip HTML tags
        if ($options['strip_tags']) {
            $input = strip_tags($input, $options['allowed_tags']);
        }

        // Convert special characters to HTML entities
        if ($options['html_entities']) {
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Limit length
        if ($options['max_length'] && strlen($input) > $options['max_length']) {
            $input = substr($input, 0, $options['max_length']);
        }

        return $input;
    }

    /**
     * Sanitize an email address.
     */
    public static function sanitizeEmail(string $email): string
    {
        $email = trim(strtolower($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        return $email ?: '';
    }

    /**
     * Sanitize a URL.
     */
    public static function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        
        // Add protocol if missing
        if (!empty($url) && !preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        return $url;
    }

    /**
     * Sanitize numeric input.
     */
    public static function sanitizeNumeric(string $input, array $options = []): string
    {
        $options = array_merge([
            'allow_decimal' => true,
            'allow_negative' => true,
            'decimal_places' => null,
        ], $options);

        // Remove all non-numeric characters except decimal point and minus
        $pattern = '/[^0-9' . ($options['allow_decimal'] ? '.' : '') . ($options['allow_negative'] ? '\-' : '') . ']/';
        $input = preg_replace($pattern, '', $input);

        // Handle decimal places
        if ($options['allow_decimal'] && $options['decimal_places'] !== null) {
            $parts = explode('.', $input);
            if (count($parts) > 1) {
                $parts[1] = substr($parts[1], 0, $options['decimal_places']);
                $input = implode('.', $parts);
            }
        }

        return $input;
    }

    /**
     * Sanitize text content that may contain basic HTML.
     */
    public static function sanitizeTextContent(string $content, array $allowedTags = []): string
    {
        $defaultAllowedTags = ['<p>', '<br>', '<strong>', '<em>', '<u>', '<ol>', '<ul>', '<li>'];
        $allowedTags = array_merge($defaultAllowedTags, $allowedTags);
        $allowedTagsString = implode('', $allowedTags);

        // Strip dangerous tags but keep allowed ones
        $content = strip_tags($content, $allowedTagsString);

        // Remove dangerous attributes
        $content = preg_replace('/(<[^>]+)\s+(on\w+|style|class|id)\s*=\s*["\'][^"\']*["\']([^>]*>)/i', '$1$3', $content);

        // Remove script content
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);

        return trim($content);
    }

    /**
     * Sanitize filename for safe file operations.
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path separators and null bytes
        $filename = str_replace(["\0", '/', '\\', '..'], '', $filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[<>:"|?*]/', '', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = substr($name, 0, 255 - strlen($extension) - 1);
            $filename = $name . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Sanitize array of data recursively.
     */
    public static function sanitizeArray(array $data, array $options = []): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeArray($value, $options);
            } elseif (is_string($value)) {
                $data[$key] = self::sanitizeString($value, $options);
            }
        }

        return $data;
    }

    /**
     * Remove potentially dangerous characters from input.
     */
    public static function removeDangerousChars(string $input): string
    {
        // Remove control characters except tab, newline, and carriage return
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Remove zero-width characters
        $input = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $input);
        
        return $input;
    }

    /**
     * Validate and sanitize phone number.
     */
    public static function sanitizePhone(string $phone): string
    {
        // Remove all non-numeric characters except + and -
        $phone = preg_replace('/[^0-9+\-]/', '', $phone);
        
        // Limit length
        if (strlen($phone) > 20) {
            $phone = substr($phone, 0, 20);
        }
        
        return $phone;
    }

    /**
     * Sanitize alphanumeric code (like product codes, SKUs).
     */
    public static function sanitizeCode(string $code): string
    {
        // Allow only alphanumeric characters, hyphens, and underscores
        $code = preg_replace('/[^a-zA-Z0-9\-_]/', '', $code);
        
        // Convert to uppercase for consistency
        return strtoupper($code);
    }

    /**
     * Check if string contains potential XSS.
     */
    public static function containsXss(string $input): bool
    {
        $xssPatterns = [
            '/<script\b/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string contains potential SQL injection.
     */
    public static function containsSqlInjection(string $input): bool
    {
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}