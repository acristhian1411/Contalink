<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureFileUpload implements ValidationRule
{
    protected array $allowedMimes;
    protected int $maxSizeKb;
    protected array $allowedExtensions;
    protected bool $scanForMalware;

    public function __construct(
        array $allowedMimes = ['jpeg', 'png', 'jpg', 'gif', 'pdf'],
        int $maxSizeKb = 2048,
        array $allowedExtensions = [],
        bool $scanForMalware = true
    ) {
        $this->allowedMimes = $allowedMimes;
        $this->maxSizeKb = $maxSizeKb;
        $this->allowedExtensions = $allowedExtensions ?: $allowedMimes;
        $this->scanForMalware = $scanForMalware;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return; // Let other validation rules handle non-file values
        }

        // Check file size
        if ($value->getSize() > ($this->maxSizeKb * 1024)) {
            $fail("El archivo no puede exceder {$this->maxSizeKb}KB.");
            return;
        }

        // Check MIME type
        $mimeType = $value->getMimeType();
        if (!$this->isAllowedMimeType($mimeType)) {
            $fail('Tipo de archivo no permitido.');
            return;
        }

        // Check file extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $fail('Extensión de archivo no permitida.');
            return;
        }

        // Validate file content matches extension
        if (!$this->validateFileContent($value, $extension)) {
            $fail('El contenido del archivo no coincide con su extensión.');
            return;
        }

        // Check for executable content
        if ($this->containsExecutableContent($value)) {
            $fail('El archivo contiene contenido ejecutable no permitido.');
            return;
        }

        // Scan for malware signatures (basic check)
        if ($this->scanForMalware && $this->containsMalwareSignatures($value)) {
            $fail('El archivo contiene contenido sospechoso.');
            return;
        }

        // Validate filename
        if (!$this->isValidFilename($value->getClientOriginalName())) {
            $fail('El nombre del archivo contiene caracteres no válidos.');
            return;
        }
    }

    /**
     * Check if MIME type is allowed.
     */
    protected function isAllowedMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = [
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg', 'image/jpg'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        foreach ($this->allowedMimes as $mime) {
            if (isset($allowedMimeTypes[$mime]) && in_array($mimeType, $allowedMimeTypes[$mime])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate file content matches its extension.
     */
    protected function validateFileContent(UploadedFile $file, string $extension): bool
    {
        $content = file_get_contents($file->getPathname());
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return str_starts_with($content, "\xFF\xD8\xFF");
            case 'png':
                return str_starts_with($content, "\x89PNG\r\n\x1a\n");
            case 'gif':
                return str_starts_with($content, "GIF87a") || str_starts_with($content, "GIF89a");
            case 'pdf':
                return str_starts_with($content, "%PDF-");
            default:
                return true; // Allow other types for now
        }
    }

    /**
     * Check for executable content in the file.
     */
    protected function containsExecutableContent(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        // Check for common executable signatures
        $executableSignatures = [
            "\x4D\x5A", // PE executable (Windows)
            "\x7F\x45\x4C\x46", // ELF executable (Linux)
            "\xFE\xED\xFA\xCE", // Mach-O executable (macOS)
            "\xFE\xED\xFA\xCF", // Mach-O 64-bit executable
            "#!/bin/", // Shell script
            "<?php", // PHP script
            "<script", // JavaScript
        ];

        foreach ($executableSignatures as $signature) {
            if (str_contains($content, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Basic malware signature detection.
     */
    protected function containsMalwareSignatures(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        // Basic suspicious patterns
        $suspiciousPatterns = [
            'eval(',
            'exec(',
            'system(',
            'shell_exec(',
            'passthru(',
            'base64_decode(',
            'gzinflate(',
            'str_rot13(',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
        ];

        $lowerContent = strtolower($content);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($lowerContent, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate filename for security.
     */
    protected function isValidFilename(string $filename): bool
    {
        // Check for null bytes
        if (str_contains($filename, "\0")) {
            return false;
        }

        // Check for path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return false;
        }

        // Check for reserved names (Windows)
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        if (in_array(strtoupper($nameWithoutExt), $reservedNames)) {
            return false;
        }

        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9._\-\s]+$/', $filename)) {
            return false;
        }

        return true;
    }
}