<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Sales\SaleStoreController;
use App\Http\Requests\StoreSalesRequest;
use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SalesWebController extends WebController
{
    /**
     * @brief Display the sales creation form with pre-loaded data for secure form rendering
     * 
     * Renders the sales creation form using Inertia with pre-loaded static data,
     * user context, and form configuration to eliminate AJAX calls and improve security.
     * 
     * @return \Inertia\Response Sales form page with pre-loaded data
     * @throws \Exception When user lacks required permissions or data loading fails
     */
    public function create()
    {
        // Check permissions
        $this->requirePermission('sales.create');

        try {
            // Get all required data for the form
            $staticData = $this->getStaticData();
            $userContext = $this->getUserContext();
            $formConfig = $this->getSalesFormConfiguration();

            // Log the form access
            $this->logSecurityEvent('sales_form_accessed');

            return Inertia::render('Sales/form', [
                'staticData' => $staticData,
                'userContext' => $userContext,
                'formConfig' => $formConfig,
                'mode' => 'create',
            ]);
        } catch (\Exception $e) {
            dd($e);
            return $this->handleException($e, 'Error al cargar el formulario de ventas');
        }
    }

    /**
     * @brief Store a new sale with comprehensive security validation and logging
     * 
     * Processes sale creation through the existing SaleStoreController while adding
     * additional security validations, input sanitization, and audit logging.
     * 
     * @param StoreSalesRequest $request Validated request containing sale data
     * @return \Illuminate\Http\JsonResponse Success response with sale data or error details
     * @throws ValidationException When business rules or validation fails
     * @throws \Exception When sale creation process encounters errors
     */
    public function store(StoreSalesRequest $request)
    {
        try {
            // Additional security validations
            $this->validateSaleBusinessRules($request);

            // Sanitize input data
            $sanitizedData = $this->sanitizeInput($request->validated());

            // Use the existing SaleStoreController for the actual storage logic
            $storeController = new SaleStoreController();
            
            // Create a new request with sanitized data
            $storeRequest = new StoreSalesRequest($sanitizedData);
            $storeRequest->setUserResolver($request->getUserResolver());
            $storeRequest->setRouteResolver($request->getRouteResolver());

            // Log the sale creation attempt
            $this->logSecurityEvent('sale_creation_attempted', [
                'sale_number' => $sanitizedData['sale_number'],
                'person_id' => $sanitizedData['person_id'],
                'till_id' => $sanitizedData['till_id'],
            ]);

            $result = $storeController->store($storeRequest);

            // If successful, log the success
            if ($result->getStatusCode() === 201) {
                $this->logSecurityEvent('sale_created_successfully', [
                    'sale_number' => $sanitizedData['sale_number'],
                ]);

                return $this->successResponse(
                    'Venta creada exitosamente',
                    $result->getData(),
                    201
                );
            }

            return $result;

        } catch (ValidationException $e) {
            return $this->handleValidationError($e);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al crear la venta');
        }
    }

    /**
     * @brief Display the sales listing page with user context and filters
     * 
     * Renders the sales index page with user-specific context and default filters
     * for secure and efficient data browsing.
     * 
     * @return \Inertia\Response Sales listing page with user context and filters
     * @throws \Exception When user lacks required permissions or page loading fails
     */
    public function index()
    {
        $this->requirePermission('sales.index');

        try {
            $userContext = $this->getUserContext();
            
            return Inertia::render('Sales/List', [
                'userContext' => $userContext,
                'filters' => $this->getSalesFilters(),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al cargar la lista de ventas');
        }
    }

    /**
     * @brief Display detailed view of a specific sale with related data
     * 
     * Shows comprehensive sale information including person details, sale items,
     * till information, and payment proofs with proper relationship loading.
     * 
     * @param mixed $id Sale identifier to display
     * @return \Inertia\Response Sale detail page with complete sale information
     * @throws \Exception When user lacks permissions or sale not found
     */
    public function show($id)
    {
        $this->requirePermission('sales.show');

        try {
            $sale = Sales::with([
                'person',
                'sales_details.product.measurementUnit',
                'sales_details.product.ivaType',
                'tills_details.till',
                'tills_details.tillproofPayments.proofPayments.paymentType'
            ])->findOrFail($id);

            $userContext = $this->getUserContext();

            return Inertia::render('Sales/Show', [
                'sale' => $sale,
                'userContext' => $userContext,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al cargar la venta');
        }
    }

    /**
     * @brief Get sales-specific form configuration with validation rules and business constraints
     * 
     * Combines base form configuration with sales-specific settings including
     * unique sale number generation, validation rules, and business constraints.
     * 
     * @return array Form configuration with sale number, validation rules, and business rules
     */
    private function getSalesFormConfiguration(): array
    {
        $baseConfig = $this->getFormConfiguration();
        
        return array_merge($baseConfig, [
            'saleNumber' => $this->generateSaleNumber(),
            'validationRules' => $this->getSalesValidationRules(),
            'businessRules' => $this->getSalesBusinessRules(),
        ]);
    }

    /**
     * @brief Generate a unique sale number with timestamp and random suffix
     * 
     * @return string Unique sale number with VTA- prefix, timestamp, and random suffix
     */
    private function generateSaleNumber(): string
    {
        return $this->generateTransactionNumber('VTA-');
    }

    /**
     * @brief Get client-side validation rules for the sales form
     * 
     * @return array Validation rules for form fields including person, date, number, till, and details
     */
    private function getSalesValidationRules(): array
    {
        return [
            'person_id' => 'required',
            'sale_date' => 'required|date',
            'sale_number' => 'required|unique:sales,sale_number',
            'till_id' => 'required',
            'sale_details' => 'required|array|min:1',
            'proofPayments' => 'required|array|min:1',
        ];
    }

    /**
     * @brief Get business rules and constraints for sales operations
     * 
     * @return array Business rules including item limits, amount limits, and approval thresholds
     */
    private function getSalesBusinessRules(): array
    {
        return [
            'maxItemsPerSale' => 50,
            'maxSaleAmount' => 1000000,
            'requiresApprovalAbove' => 100000,
            'allowFutureDates' => false,
        ];
    }

    /**
     * @brief Get default filters and options for sales listing page
     * 
     * @return array Filter configuration with date ranges, statuses, and payment types
     */
    private function getSalesFilters(): array
    {
        return [
            'dateRange' => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'statuses' => ['active', 'cancelled'],
            'paymentTypes' => $this->getStaticData()['paymentTypes'],
        ];
    }

    /**
     * @brief Validate business rules specific to sales operations
     * 
     * Performs comprehensive validation including till access, date constraints,
     * item limits, amount limits, and payment validation to ensure business rule compliance.
     * 
     * @param StoreSalesRequest $request Validated request to check against business rules
     * @return void
     * @throws ValidationException When any business rule validation fails
     */
    private function validateSaleBusinessRules(StoreSalesRequest $request): void
    {
        $data = $request->validated();
        $errors = [];

        // Validate till access
        if (!$this->validateTillAccess($data['till_id'])) {
            $errors['till_id'] = ['No tienes acceso a la caja seleccionada'];
        }

        // Validate sale date
        if (strtotime($data['sale_date']) > time()) {
            $errors['sale_date'] = ['La fecha de venta no puede ser futura'];
        }

        // Validate maximum items per sale
        if (count($data['sale_details']) > 50) {
            $errors['sale_details'] = ['No se pueden incluir más de 50 productos por venta'];
        }

        // Validate total amount
        $totalAmount = collect($data['sale_details'])->sum(function ($detail) {
            return $detail['sd_qty'] * $detail['sd_amount'];
        });

        if ($totalAmount > 1000000) {
            $errors['sale_details'] = ['El monto total de la venta no puede exceder $1,000,000'];
        }

        // Validate payment amounts match sale total
        $totalPayments = collect($data['proofPayments'])->sum('amount');
        if (abs($totalAmount - $totalPayments) > 0.01) {
            $errors['proofPayments'] = ['El total de los pagos debe coincidir con el total de la venta'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @brief Get extended static data specific to sales operations
     * 
     * @return array Static data including base reference data plus sales-specific clients list
     */
    protected function getStaticData(): array
    {
        $baseData = parent::getStaticData();
        
        // Add sales-specific data
        $baseData['clients'] = $this->getClientsForSelection();
        
        return $baseData;
    }
}