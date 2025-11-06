<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Utils\InputSanitizer;

class SecureInput implements ValidationRule
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'check_xss' => true,
            'check_sql_injection' => true,
            'check_path_traversal' => true,
            'max_length' => 1000,
            'allow_html' => false,
            'allowed_tags' => [],
        ], $options);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // Check maximum length
        if (strlen($value) > $this->options['max_length']) {
            $fail("El campo no puede exceder {$this->options['max_length']} caracteres.");
            return;
        }

        // Check for XSS
        if ($this->options['check_xss'] && InputSanitizer::containsXss($value)) {
            $fail('El campo contiene contenido potencialmente peligroso (XSS).');
            return;
        }

        // Check for SQL injection
        if ($this->options['check_sql_injection'] && InputSanitizer::containsSqlInjection($value)) {
            $fail('El campo contiene contenido potencialmente peligroso (SQL).');
            return;
        }

        // Check for path traversal
        if ($this->options['check_path_traversal'] && $this->containsPathTraversal($value)) {
            $fail('El campo contiene caracteres no permitidos.');
            return;
        }

        // Check HTML if not allowed
        if (!$this->options['allow_html'] && $this->containsHtml($value)) {
            $fail('El campo no puede contener código HTML.');
            return;
        }

        // Check for null bytes
        if (str_contains($value, "\0")) {
            $fail('El campo contiene caracteres no válidos.');
            return;
        }

        // Check for control characters
        if ($this->containsControlCharacters($value)) {
            $fail('El campo contiene caracteres de control no permitidos.');
            return;
        }
    }

    /**
     * Check for path traversal patterns.
     */
    protected function containsPathTraversal(string $value): bool
    {
        $patterns = [
            '/\.\.\//i',
            '/\.\.\\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if value contains HTML.
     */
    protected function containsHtml(string $value): bool
    {
        return $value !== strip_tags($value);
    }

    /**
     * Check for dangerous control characters.
     */
    protected function containsControlCharacters(string $value): bool
    {
        // Allow tab, newline, and carriage return
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value);
    }

    /**
     * Create a rule for text input (no HTML allowed).
     */
    public static function text(int $maxLength = 255): self
    {
        return new self([
            'max_length' => $maxLength,
            'allow_html' => false,
        ]);
    }

    /**
     * Create a rule for content that allows basic HTML.
     */
    public static function content(int $maxLength = 1000, array $allowedTags = []): self
    {
        return new self([
            'max_length' => $maxLength,
            'allow_html' => true,
            'allowed_tags' => $allowedTags,
        ]);
    }

    /**
     * Create a rule for strict input (no HTML, extra security checks).
     */
    public static function strict(int $maxLength = 255): self
    {
        return new self([
            'max_length' => $maxLength,
            'allow_html' => false,
            'check_xss' => true,
            'check_sql_injection' => true,
            'check_path_traversal' => true,
        ]);
    }

    /**
     * Create a rule for code/identifier input.
     */
    public static function code(int $maxLength = 50): self
    {
        return new self([
            'max_length' => $maxLength,
            'allow_html' => false,
            'check_xss' => false, // Codes shouldn't contain these patterns normally
            'check_sql_injection' => false,
            'check_path_traversal' => true,
        ]);
    }
}