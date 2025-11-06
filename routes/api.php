<?php

use App\Http\Controllers\AccountPlans\AccountPlanController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\Categories\CategoriesController;
use App\Http\Controllers\Cities\CitiesController;
use App\Http\Controllers\ContactTypes\ContactTypesController;
use App\Http\Controllers\Countries\CountriesController;
use App\Http\Controllers\IvaTypes\IvaTypeController;
use App\Http\Controllers\MeasurementUnits\MeasurementUnitsController;
use App\Http\Controllers\PaymentTypes\PaymentTypesController;
use App\Http\Controllers\Permissions\PermissionsController;
use App\Http\Controllers\Persons\PersonsController;
use App\Http\Controllers\PersonTypes\PersonTypesController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\ProofPaypments\ProofPaymentsController;
use App\Http\Controllers\Purchases\PurchaseDeleteController;
use App\Http\Controllers\Purchases\PurchasesController;
use App\Http\Controllers\Purchases\PurchasesReportController;
use App\Http\Controllers\Purchases\PurchaseStoreController;
use App\Http\Controllers\PurchasesDetails\PurchasesDetailsController;
use App\Http\Controllers\RefundDetails\RefundDetailsController;
use App\Http\Controllers\Refunds\RefundsController;
use App\Http\Controllers\Roles\RolesController;
use App\Http\Controllers\Sales\SaleDeleteController;
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\Sales\SalesReportController;
use App\Http\Controllers\Sales\SaleStoreController;
use App\Http\Controllers\SalesDetails\SalesDetailsController;
use App\Http\Controllers\States\StatesController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Controllers\TillsProcess\TillsProcessController;
use App\Http\Controllers\TillsTransfers\TillsTransfersController;
use App\Http\Controllers\TillTypes\TillTypeController;
use App\Http\Controllers\Users\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Secured with Sanctum Authentication
|--------------------------------------------------------------------------
|
| All API routes are protected with Sanctum authentication and rate limiting.
| These routes are primarily for:
| 1. Dynamic data operations (search, real-time updates)
| 2. AJAX operations that cannot be handled via Inertia pre-loading
| 3. Mobile app or external API access
|
| Static data should be pre-loaded via Web controllers using Inertia.js
|
*/

// ========================================
// SANCTUM CSRF COOKIE ENDPOINT
// ========================================
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->middleware('web');

// ========================================
// AUTHENTICATION ENDPOINTS (Stricter Rate Limiting)
// ========================================
Route::middleware(['throttle:5,1'])->group(function () {
    // Registration endpoint - secured and requires admin permission
    Route::post('/register', [AuthController::class, 'create'])
        ->middleware(['auth:sanctum', 'permission:users.create'])
        ->name('api.register');
});

// ========================================
// PROTECTED API ROUTES (Standard Rate Limiting: 60 requests per minute)
// ========================================
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    // ========================================
    // USER CONTEXT AND AUTHENTICATION
    // ========================================
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['person', 'roles', 'permissions']);
    })->name('api.user');

    // ========================================
    // DYNAMIC SEARCH ENDPOINTS (For AJAX operations)
    // ========================================
    Route::prefix('search')->name('api.search.')->group(function () {
        Route::get('/clients', [PersonsController::class, 'search'])
            ->middleware('permission:persons.index')
            ->name('clients');
            
        Route::get('/products', [ProductsController::class, 'search'])
            ->middleware('permission:products.index')
            ->name('products');
            
        Route::get('/persons-by-type/{type}', [PersonsController::class, 'searchPerType'])
            ->middleware('permission:persons.index')
            ->name('persons-by-type');
    });

    // ========================================
    // REAL-TIME DATA ENDPOINTS
    // ========================================
    Route::prefix('real-time')->name('api.real-time.')->group(function () {
        // Till amounts for purchase validation
        Route::get('/tills/{id}/amount', [TillsController::class, 'showTillAmount'])
            ->middleware('permission:tills.show')
            ->name('till-amount');
            
        // Tills by user for form pre-population
        Route::get('/tills/by-person/{id}', [TillsController::class, 'showTillsByUser'])
            ->middleware('permission:tills.index')
            ->name('tills-by-person');
            
        // Cities by state for geographic forms
        Route::get('/cities/by-state/{id}', [CitiesController::class, 'cityByStateId'])
            ->name('cities-by-state');
            
        // Cities by country
        Route::get('/cities/by-country/{id}', [CitiesController::class, 'cityByCountryId'])
            ->name('cities-by-country');
            
        // States by country
        Route::get('/states/by-country/{id}', [StatesController::class, 'getStatesByCountry'])
            ->name('states-by-country');
    });

    // ========================================
    // SALES API OPERATIONS (Complex operations that need API access)
    // ========================================
    Route::prefix('sales')->name('api.sales.')->middleware('permission:sales.index')->group(function () {
        // Sales listing with pagination and filters
        Route::get('/', [SalesController::class, 'index'])->name('index');
        
        // Sales search by number
        Route::get('/search/{searchTerm}', [SalesController::class, 'searchByNumber'])
            ->name('search');
            
        // Individual sale details
        Route::get('/{id}', [SalesController::class, 'show'])
            ->middleware('permission:sales.show')
            ->name('show');
            
        // Sales validation (for complex business rules)
        Route::post('/validate', [SalesController::class, 'validateSale'])
            ->middleware('permission:sales.create')
            ->name('validate');
            
        // Sales creation via API (for mobile or external systems)
        Route::post('/', [SaleStoreController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('store');
            
        // Sales deletion with stock reversal
        Route::delete('/{id}', [SaleDeleteController::class, 'destroy'])
            ->middleware('permission:sales.delete')
            ->name('delete');
            
        // Sales reports
        Route::get('/reports/data', [SalesReportController::class, 'getSalesReport'])
            ->middleware('permission:reports.show')
            ->name('reports.data');
    });

    // ========================================
    // PURCHASES API OPERATIONS
    // ========================================
    Route::prefix('purchases')->name('api.purchases.')->middleware('permission:purchases.index')->group(function () {
        // Purchases listing with pagination and filters
        Route::get('/', [PurchasesController::class, 'index'])->name('index');
        
        // Individual purchase details
        Route::get('/{id}', [PurchasesController::class, 'show'])
            ->middleware('permission:purchases.show')
            ->name('show');
            
        // Purchase creation via API (for mobile or external systems)
        Route::post('/', [PurchaseStoreController::class, 'store'])
            ->middleware('permission:purchases.create')
            ->name('store');
            
        // Purchase deletion with stock reversal
        Route::delete('/{id}', [PurchaseDeleteController::class, 'destroy'])
            ->middleware('permission:purchases.delete')
            ->name('delete');
            
        // Purchase reports
        Route::get('/reports/data', [PurchasesReportController::class, 'getPurchasesReport'])
            ->middleware('permission:reports.show')
            ->name('reports.data');
    });

    // ========================================
    // TILLS API OPERATIONS (Real-time operations)
    // ========================================
    Route::prefix('tills')->name('api.tills.')->group(function () {
        // Till listing
        Route::get('/', [TillsController::class, 'index'])
            ->middleware('permission:tills.index')
            ->name('index');
            
        // Till details
        Route::get('/{id}', [TillsController::class, 'show'])
            ->middleware('permission:tills.show')
            ->name('show');
            
        // Till operations (these need to be API calls for real-time updates)
        Route::post('/{id}/open', [TillsProcessController::class, 'cashOpening'])
            ->middleware('permission:tills.update')
            ->name('open');
            
        Route::post('/{id}/close', [TillsProcessController::class, 'close'])
            ->middleware('permission:tills.update')
            ->name('close');
            
        Route::post('/{id}/deposit', [TillsProcessController::class, 'deposit'])
            ->middleware('permission:tills.update')
            ->name('deposit');
            
        Route::post('/{id}/transfer', [TillsProcessController::class, 'transfer'])
            ->middleware('permission:tills.update')
            ->name('transfer');
            
        // Till reports
        Route::get('/{id}/close-report-resume', [TillDetailsController::class, 'closeReportResume'])
            ->middleware('permission:tills.show')
            ->name('close-report-resume');
            
        Route::get('/{id}/close-report-detailed', [TillDetailsController::class, 'closeReportDetailed'])
            ->middleware('permission:tills.show')
            ->name('close-report-detailed');
            
        // Till history
        Route::get('/{id}/history', [TillDetailsController::class, 'showByTillIdAndDate'])
            ->middleware('permission:tills.show')
            ->name('history');
    });

    // ========================================
    // ROLES AND PERMISSIONS API (For admin interfaces)
    // ========================================
    Route::prefix('roles')->name('api.roles.')->middleware('permission:roles.index')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('index');
        Route::get('/{id}', [RolesController::class, 'show'])
            ->middleware('permission:roles.show')
            ->name('show');
            
        Route::post('/', [RolesController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('store');
            
        Route::put('/{id}', [RolesController::class, 'update'])
            ->middleware('permission:roles.update')
            ->name('update');
            
        Route::delete('/{id}', [RolesController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('delete');
            
        // Role permissions management
        Route::post('/{roleId}/permissions', [RolesController::class, 'assignPermissionsToRole'])
            ->middleware('permission:roles.update')
            ->name('assign-permissions');
            
        Route::delete('/{roleId}/permissions', [RolesController::class, 'removePermissionsFromRole'])
            ->middleware('permission:roles.update')
            ->name('remove-permissions');
    });

    Route::prefix('permissions')->name('api.permissions.')->middleware('permission:permissions.index')->group(function () {
        Route::get('/', [PermissionsController::class, 'index'])->name('index');
        Route::get('/{id}', [PermissionsController::class, 'show'])
            ->middleware('permission:permissions.show')
            ->name('show');
    });

    // ========================================
    // USERS API (For admin interfaces)
    // ========================================
    Route::prefix('users')->name('api.users.')->middleware('permission:users.index')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('/{id}', [UsersController::class, 'show'])
            ->middleware('permission:users.show')
            ->name('show');
            
        // User role management
        Route::get('/{id}/roles', [UsersController::class, 'showPermissionsByRole'])
            ->middleware('permission:users.show')
            ->name('roles');
            
        Route::post('/{id}/assign-role', [UsersController::class, 'assignRole'])
            ->middleware('permission:users.update')
            ->name('assign-role');
    });

    // ========================================
    // PERSONS API (For dynamic operations)
    // ========================================
    Route::prefix('persons')->name('api.persons.')->middleware('permission:persons.index')->group(function () {
        Route::get('/', [PersonsController::class, 'index'])->name('index');
        Route::get('/{id}', [PersonsController::class, 'show'])
            ->middleware('permission:persons.show')
            ->name('show');
            
        Route::get('/by-type/{id}', [PersonsController::class, 'personByType'])
            ->name('by-type');
    });

    // ========================================
    // PRODUCTS API (For dynamic operations)
    // ========================================
    Route::prefix('products')->name('api.products.')->middleware('permission:products.index')->group(function () {
        Route::get('/', [ProductsController::class, 'index'])->name('index');
        Route::get('/{id}', [ProductsController::class, 'show'])
            ->middleware('permission:products.show')
            ->name('show');
    });

    // ========================================
    // REFERENCE DATA APIs (For dynamic loading when needed)
    // ========================================
    
    // These endpoints are kept for cases where dynamic loading is absolutely necessary
    // Most reference data should be pre-loaded via Web controllers
    
    Route::prefix('reference')->name('api.reference.')->group(function () {
        // Payment types with proof payments
        Route::get('/payment-types', [PaymentTypesController::class, 'index'])->name('payment-types');
        Route::get('/payment-types/{id}/proof-payments', [ProofPaymentsController::class, 'showByType'])->name('proof-payments-by-type');
        
        // Active measurement units
        Route::get('/measurement-units/active', [MeasurementUnitsController::class, 'active'])->name('measurement-units-active');
        
        // Categories
        Route::get('/categories', [CategoriesController::class, 'index'])->name('categories');
        
        // Brands
        Route::get('/brands', [BrandController::class, 'index'])->name('brands');
        
        // IVA types
        Route::get('/iva-types', [IvaTypeController::class, 'index'])->name('iva-types');
    });

    // ========================================
    // REFUNDS API (For complex operations)
    // ========================================
    Route::prefix('refunds')->name('api.refunds.')->middleware('permission:refunds.index')->group(function () {
        Route::get('/', [RefundsController::class, 'index'])->name('index');
        Route::get('/{id}', [RefundsController::class, 'show'])
            ->middleware('permission:refunds.show')
            ->name('show');
            
        Route::post('/', [RefundsController::class, 'store'])
            ->middleware('permission:refunds.create')
            ->name('store');
            
        Route::delete('/{id}', [RefundsController::class, 'destroy'])
            ->middleware('permission:refunds.delete')
            ->name('delete');
    });

    // ========================================
    // PROOF PAYMENTS API (For complex payment operations)
    // ========================================
    Route::prefix('proof-payments')->name('api.proof-payments.')->group(function () {
        Route::get('/', [ProofPaymentsController::class, 'index'])->name('index');
        Route::get('/{id}', [ProofPaymentsController::class, 'show'])->name('show');
        
        // Multiple proof payments operations
        Route::post('/multiple', [ProofPaymentsController::class, 'storeMultiple'])->name('store-multiple');
        Route::put('/multiple', [ProofPaymentsController::class, 'updateMultiple'])->name('update-multiple');
    });

    // ========================================
    // SALES AND PURCHASE DETAILS API (For complex operations)
    // ========================================
    Route::prefix('sales-details')->name('api.sales-details.')->group(function () {
        Route::post('/multiple', [SalesDetailsController::class, 'storeMany'])->name('store-multiple');
    });

    Route::prefix('purchase-details')->name('api.purchase-details.')->group(function () {
        Route::post('/multiple', [PurchasesDetailsController::class, 'storeMany'])->name('store-multiple');
    });

    Route::prefix('refund-details')->name('api.refund-details.')->group(function () {
        Route::post('/multiple', [RefundDetailsController::class, 'storeMany'])->name('store-multiple');
    });
});

/*
|--------------------------------------------------------------------------
| REMOVED ENDPOINTS
|--------------------------------------------------------------------------
|
| The following endpoints have been removed as they are now handled
| via secure Web controllers with Inertia.js pre-loading:
|
| - Individual CRUD operations for reference data (categories, brands, etc.)
| - Form data endpoints that can be pre-loaded
| - Simple listing endpoints without complex filtering
| - Endpoints that duplicate Web controller functionality
|
| These operations should now use the Web routes defined in routes/web.php
| with proper Inertia.js data pre-loading for better security and performance.
|
*/