env<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoXss implements ValidationRule
{
    protected bool $allowBasicHtml;
    protected array $allowedTags;

    public function __construct(bool $allowBasicHtml = false, array $allowedTags = [])
    {
        $this->allowBasicHtml = $allowBasicHtml;
        $this->allowedTags = $allowBasicHtml ? 
            array_merge(['<p>', '<br>', '<strong>', '<em>', '<u>'], $allowedTags) : 
            $allowedTags;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // Check for XSS patterns
        if ($this->containsXss($value)) {
            $fail('El campo contiene contenido no permitido por seguridad.');
            return;
        }

        // Check for dangerous HTML if basic HTML is not allowed
        if (!$this->allowBasicHtml && $this->containsHtml($value)) {
            $fail('El campo no puede contener código HTML.');
            return;
        }

        // If basic HTML is allowed, check for dangerous tags
        if ($this->allowBasicHtml && $this->containsDangerousHtml($value)) {
            $fail('El campo contiene etiquetas HTML no permitidas.');
            return;
        }
    }

    /**
     * Check if the value contains XSS patterns.
     */
    protected function containsXss(string $value): bool
    {
        $xssPatterns = [
            // Script tags
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            
            // JavaScript protocols
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            
            // Event handlers
            '/\bon\w+\s*=/i',
            
            // Dangerous tags
            '/<(iframe|object|embed|applet|meta|link|style|base|form|input|button|select|textarea|option)\b/i',
            
            // Expression and import
            '/expression\s*\(/i',
            '/@import/i',
            
            // Encoded scripts
            '/&#x?[0-9a-f]+;?/i',
            
            // CSS expressions
            '/style\s*=.*expression\s*\(/i',
            
            // SVG scripts
            '/<svg\b[^>]*>.*<script\b/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the value contains HTML.
     */
    protected function containsHtml(string $value): bool
    {
        return $value !== strip_tags($value);
    }

    /**
     * Check if the value contains dangerous HTML tags.
     */
    protected function containsDangerousHtml(string $value): bool
    {
        // Remove allowed tags
        $allowedTagsString = implode('', $this->allowedTags);
        $stripped = strip_tags($value, $allowedTagsString);
        
        // If there's still HTML after removing allowed tags, it's dangerous
        return $stripped !== strip_tags($stripped);
    }

    /**
     * Create a rule that allows basic HTML formatting.
     */
    public static function allowBasicHtml(array $additionalTags = []): self
    {
        return new self(true, $additionalTags);
    }

    /**
     * Create a rule that allows no HTML at all.
     */
    public static function strict(): self
    {
        return new self(false);
    }

    /**
     * Create a rule with custom allowed tags.
     */
    public static function withAllowedTags(array $allowedTags): self
    {
        return new self(true, $allowedTags);
    }
}