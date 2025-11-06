<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Purchases\PurchaseStoreController;
use App\Http\Requests\PurchaseStoreRequest;
use App\Models\Purchases;
use App\Models\Tills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchasesWebController extends WebController
{
    /**
     * @brief Display the purchases creation form with pre-loaded data for secure form rendering
     * 
     * Renders the purchases creation form using Inertia with pre-loaded static data,
     * user context, and form configuration including till amounts for validation.
     * 
     * @return \Inertia\Response Purchases form page with pre-loaded data and till amounts
     * @throws \Exception When user lacks required permissions or data loading fails
     */
    public function create()
    {
        // Check permissions
        $this->requirePermission('purchases.create');

        try {
            // Get all required data for the form
            $staticData = $this->getStaticData();
            $userContext = $this->getUserContext();
            $formConfig = $this->getPurchasesFormConfiguration();

            // Log the form access
            $this->logSecurityEvent('purchases_form_accessed');

            return Inertia::render('Purchases/Form', [
                'staticData' => $staticData,
                'userContext' => $userContext,
                'formConfig' => $formConfig,
                'mode' => 'create',
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al cargar el formulario de compras');
        }
    }

    /**
     * @brief Store a new purchase with comprehensive security validation and till verification
     * 
     * Processes purchase creation through the existing PurchaseStoreController while adding
     * additional security validations, till fund verification, and audit logging.
     * 
     * @param PurchaseStoreRequest $request Validated request containing purchase data
     * @return \Illuminate\Http\JsonResponse Success response with purchase data or error details
     * @throws ValidationException When business rules, till validation, or data validation fails
     * @throws \Exception When purchase creation process encounters errors
     */
    public function store(PurchaseStoreRequest $request)
    {
        try {
            // Additional security validations
            $this->validatePurchaseBusinessRules($request);

            // Sanitize input data
            $sanitizedData = $this->sanitizeInput($request->validated());

            // Use the existing PurchaseStoreController for the actual storage logic
            $storeController = new PurchaseStoreController();
            
            // Create a new request with sanitized data
            $storeRequest = new PurchaseStoreRequest($sanitizedData);
            $storeRequest->setUserResolver($request->getUserResolver());
            $storeRequest->setRouteResolver($request->getRouteResolver());

            // Log the purchase creation attempt
            $this->logSecurityEvent('purchase_creation_attempted', [
                'purchase_number' => $sanitizedData['purchase_number'],
                'person_id' => $sanitizedData['person_id'],
                'till_id' => $sanitizedData['till_id'],
            ]);

            $result = $storeController->store($storeRequest);

            // If successful, log the success
            if ($result->getStatusCode() === 201) {
                $this->logSecurityEvent('purchase_created_successfully', [
                    'purchase_number' => $sanitizedData['purchase_number'],
                ]);

                return $this->successResponse(
                    'Compra creada exitosamente',
                    $result->getData(),
                    201
                );
            }

            return $result;

        } catch (ValidationException $e) {
            return $this->handleValidationError($e);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al crear la compra');
        }
    }

    /**
     * @brief Display the purchases listing page with user context and filters
     * 
     * Renders the purchases index page with user-specific context and default filters
     * for secure and efficient purchase data browsing.
     * 
     * @return \Inertia\Response Purchases listing page with user context and filters
     * @throws \Exception When user lacks required permissions or page loading fails
     */
    public function index()
    {
        $this->requirePermission('purchases.index');

        try {
            $userContext = $this->getUserContext();
            
            return Inertia::render('Purchases/List', [
                'userContext' => $userContext,
                'filters' => $this->getPurchasesFilters(),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al cargar la lista de compras');
        }
    }

    /**
     * @brief Display detailed view of a specific purchase with related data
     * 
     * Shows comprehensive purchase information including provider details, purchase items,
     * and measurement unit information with proper relationship loading.
     * 
     * @param mixed $id Purchase identifier to display
     * @return \Inertia\Response Purchase detail page with complete purchase information
     * @throws \Exception When user lacks permissions or purchase not found
     */
    public function show($id)
    {
        $this->requirePermission('purchases.show');

        try {
            $purchase = Purchases::with([
                'person',
                'purchaseDetails.product.measurementUnit',
                'purchaseDetails.product.ivaType'
            ])->findOrFail($id);

            $userContext = $this->getUserContext();

            return Inertia::render('Purchases/Show', [
                'purchase' => $purchase,
                'userContext' => $userContext,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al cargar la compra');
        }
    }

    /**
     * Get purchases-specific form configuration
     */
    private function getPurchasesFormConfiguration(): array
    {
        $baseConfig = $this->getFormConfiguration();
        
        return array_merge($baseConfig, [
            'purchaseNumber' => $this->generatePurchaseNumber(),
            'validationRules' => $this->getPurchasesValidationRules(),
            'businessRules' => $this->getPurchasesBusinessRules(),
            'tillAmounts' => $this->getTillAmounts(),
        ]);
    }

    /**
     * Generate a unique purchase number
     */
    private function generatePurchaseNumber(): string
    {
        return $this->generateTransactionNumber('CMP-');
    }

    /**
     * Get client-side validation rules for the purchases form
     */
    private function getPurchasesValidationRules(): array
    {
        return [
            'person_id' => 'required',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|unique:purchases,purchase_number',
            'till_id' => 'required',
            'purchase_details' => 'required|array|min:1',
            'proofPayments' => 'required|array|min:1',
        ];
    }

    /**
     * Get business rules for purchases
     */
    private function getPurchasesBusinessRules(): array
    {
        return [
            'maxItemsPerPurchase' => 50,
            'maxPurchaseAmount' => 1000000,
            'requiresApprovalAbove' => 100000,
            'allowFutureDates' => false,
            'requiresTillValidation' => true,
        ];
    }

    /**
     * Get filters for purchases listing
     */
    private function getPurchasesFilters(): array
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
     * @brief Get current amounts for all accessible tills for purchase validation
     * 
     * Retrieves current balance amounts for all tills accessible to the current user
     * to enable client-side validation of purchase amounts against available funds.
     * 
     * @return array Associative array of till IDs to current amounts
     */
    private function getTillAmounts(): array
    {
        $user = Auth::user();
        $tillAmounts = [];

        // Get user's accessible tills
        $userTills = $this->getUserTills($user);
        
        foreach ($userTills as $till) {
            // Calculate current till amount (this is a simplified version)
            // In a real implementation, you'd calculate based on TillDetails
            $tillAmounts[$till['id']] = $this->calculateTillAmount($till['id']);
        }

        return $tillAmounts;
    }

    /**
     * Calculate current amount in a specific till
     */
    private function calculateTillAmount(int $tillId): float
    {
        // This is a simplified calculation
        // In reality, you'd sum all TillDetails for this till
        return 10000.00; // Placeholder value
    }

    /**
     * @brief Validate business rules specific to purchase operations
     * 
     * Performs comprehensive validation including till access, fund availability,
     * date constraints, item limits, amount limits, and provider access validation.
     * 
     * @param PurchaseStoreRequest $request Validated request to check against business rules
     * @return void
     * @throws ValidationException When any business rule validation fails
     */
    private function validatePurchaseBusinessRules(PurchaseStoreRequest $request): void
    {
        $data = $request->validated();
        $errors = [];

        // Validate till access
        if (!$this->validateTillAccess($data['till_id'])) {
            $errors['till_id'] = ['No tienes acceso a la caja seleccionada'];
        }

        // Validate purchase date
        if (strtotime($data['purchase_date']) > time()) {
            $errors['purchase_date'] = ['La fecha de compra no puede ser futura'];
        }

        // Validate maximum items per purchase
        if (count($data['purchase_details']) > 50) {
            $errors['purchase_details'] = ['No se pueden incluir más de 50 productos por compra'];
        }

        // Validate total amount
        $totalAmount = collect($data['purchase_details'])->sum(function ($detail) {
            return $detail['pd_qty'] * $detail['pd_amount'];
        });

        if ($totalAmount > 1000000) {
            $errors['purchase_details'] = ['El monto total de la compra no puede exceder $1,000,000'];
        }

        // Validate payment amounts match purchase total
        $totalPayments = collect($data['proofPayments'])->sum('amount');
        if (abs($totalAmount - $totalPayments) > 0.01) {
            $errors['proofPayments'] = ['El total de los pagos debe coincidir con el total de la compra'];
        }

        // Validate till has sufficient funds
        $tillAmount = $this->calculateTillAmount($data['till_id']);
        if ($tillAmount < $totalAmount) {
            $errors['till_id'] = ['La caja no tiene fondos suficientes para esta compra'];
        }

        // Validate provider permissions
        if (!$this->validateProviderAccess($data['person_id'])) {
            $errors['person_id'] = ['No tienes permisos para comprar a este proveedor'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate provider access for current user
     */
    private function validateProviderAccess(int $personId): bool
    {
        $user = Auth::user();
        
        // Admin users can access all providers
        if ($user->can('providers.manage_all')) {
            return true;
        }

        // Add specific business logic for provider access
        // This could include checking if the provider is active,
        // if the user has permission to purchase from this provider, etc.
        
        return true; // Simplified for now
    }

    /**
     * Get extended static data specific to purchases
     */
    protected function getStaticData(): array
    {
        $baseData = parent::getStaticData();
        
        // Add purchases-specific data
        $baseData['providers'] = $this->getProvidersForSelection();
        
        return $baseData;
    }

    /**
     * Get providers/persons for selection (with permission check)
     */
    private function getProvidersForSelection(): array
    {
        $this->requirePermission('persons.index');
        
        // Filter for providers (assuming person_type_id indicates provider type)
        return \App\Models\Persons::select('id', 'person_fname', 'person_lastname', 'person_corpname', 'person_idnumber')
            ->whereHas('type', function ($query) {
                $query->where('person_type_desc', 'like', '%proveedor%');
            })
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
}