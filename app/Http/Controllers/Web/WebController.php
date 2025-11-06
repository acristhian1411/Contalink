<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentTypes;
use App\Models\IvaType;
use App\Models\MeasurementUnit;
use App\Models\Categories;
use App\Models\Brand;
use App\Models\Tills;
use App\Models\Persons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

abstract class WebController extends Controller
{
    /**
     * Get static reference data for forms
     * This method pre-loads commonly used reference data to avoid AJAX calls
     */
    protected function getStaticData(): array
    {
        return [
            'paymentTypes' => PaymentTypes::with('proofPayments')->get(),
            'ivaTypes' => IvaType::all(),
            'measurementUnits' => MeasurementUnit::active()->get(),
            'categories' => Categories::all(),
            'brands' => Brand::all(),
        ];
    }

    /**
     * Get user context information including permissions and assigned resources
     */
    protected function getUserContext(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Usuario no autenticado');
        }

        return [
            'user' => $user->load('person'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames()->pluck('name'),
            'userTills' => $this->getUserTills($user),
        ];
    }

    /**
     * Get tills assigned to the current user
     */
    protected function getUserTills($user): array
    {
        // If user has person relationship, get their assigned tills
        if ($user->person_id) {
            return Tills::where('person_id', $user->person_id)
                ->with('type')
                ->get()
                ->toArray();
        }

        // If user has admin permissions, return all active tills
        if ($user->can('tills.manage_all')) {
            return Tills::where('till_status', true)
                ->with('type')
                ->get()
                ->toArray();
        }

        return [];
    }

    /**
     * Get form configuration data like default values and validation rules
     */
    protected function getFormConfiguration(): array
    {
        return [
            'defaultDate' => now()->format('Y-m-d'),
            'currentDateTime' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Check if user has required permission
     */
    protected function checkPermission(string $permission): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        return $user->can($permission);
    }

    /**
     * Ensure user has required permission or throw exception
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->checkPermission($permission)) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }
    }

    /**
     * Sanitize input data to prevent XSS and other attacks
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially dangerous HTML tags and scripts
                $sanitized[$key] = strip_tags($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Handle validation errors with consistent formatting
     */
    protected function handleValidationError(ValidationException $e): \Illuminate\Http\JsonResponse
    {
        Log::warning('Validation error', [
            'errors' => $e->errors(),
            'user_id' => Auth::id(),
            'request_data' => request()->except(['password', 'password_confirmation'])
        ]);

        return response()->json([
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Handle general exceptions with secure error messages
     */
    protected function handleException(\Exception $e, string $userMessage = 'Ocurrió un error inesperado'): \Illuminate\Http\JsonResponse
    {
        Log::error('Controller exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => Auth::id(),
            'request_data' => request()->except(['password', 'password_confirmation'])
        ]);

        // Don't expose system details in production
        if (app()->environment('production')) {
            return response()->json([
                'message' => $userMessage,
            ], 500);
        }

        return response()->json([
            'message' => $userMessage,
            'debug' => $e->getMessage(),
        ], 500);
    }

    /**
     * Generate success response for form operations
     */
    protected function successResponse(string $message, $data = null, int $code = 200): \Illuminate\Http\JsonResponse
    {
        $response = ['message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Validate till access for current user
     */
    protected function validateTillAccess(int $tillId): bool
    {
        $user = Auth::user();
        
        // Admin users can access all tills
        if ($user->can('tills.manage_all')) {
            return true;
        }

        // Check if till is assigned to user's person
        if ($user->person_id) {
            return Tills::where('id', $tillId)
                ->where('person_id', $user->person_id)
                ->exists();
        }

        return false;
    }

    /**
     * Get clients/persons for selection (with permission check)
     */
    protected function getClientsForSelection(): array
    {
        $this->requirePermission('persons.index');
        
        return Persons::select('id', 'person_fname', 'person_lastname', 'person_corpname', 'person_idnumber')
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->person_corpname ?: ($person->person_fname . ' ' . $person->person_lastname),
                    'document' => $person->person_idnumber,
                ];
            })
            ->toArray();
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, array_merge([
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ], $context));
    }

    /**
     * Validate business rules for operations
     */
    protected function validateBusinessRules(array $data, string $operation): array
    {
        $errors = [];

        // Common business rule validations can be added here
        // This method can be overridden in child controllers for specific rules

        return $errors;
    }

    /**
     * Generate unique number for transactions
     */
    protected function generateTransactionNumber(string $prefix = ''): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . $random;
    }
}