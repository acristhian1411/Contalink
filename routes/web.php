<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Countries\CountriesController;
use App\Http\Controllers\Cities\CitiesController;
use App\Http\Controllers\TillTypes\TillTypeController;
use App\Http\Controllers\IvaTypes\IvaTypeController;
use App\Http\Controllers\PersonTypes\PersonTypesController;
use App\Http\Controllers\PaymentTypes\PaymentTypesController;
use App\Http\Controllers\ContactTypes\ContactTypesController;
use App\Http\Controllers\Categories\CategoriesController;
use App\Http\Controllers\States\StatesController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\MeasurementUnits\MeasurementUnitsController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\Web\SalesWebController;
use App\Http\Controllers\Web\PurchasesWebController;
use App\Http\Controllers\Persons\PersonsController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Controllers\TillsProcess\TillsProcessController;
use App\Http\Controllers\Refunds\RefundsController;
use App\Http\Controllers\Roles\RolesController;

// ========================================
// PUBLIC ROUTES (No Authentication Required)
// ========================================

// Home page - redirect to dashboard if authenticated
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Home', ['name' => 'Usuario']);
})->name('home');

// Login page
Route::get('/login', function () {
    return Inertia::render('Login/index');
})->name('login');

// Public routes (no authentication required)
Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // CSP violation reporting endpoint (no auth required)
    Route::post('/csp-report', [CspReportController::class, 'report'])->name('csp.report');

    // Registration form (view only - actual registration requires admin permission via API)
    Route::get('/register', function () {
        return Inertia::render('Register/index');
    })->name('register');
});

// Dashboard - main authenticated landing page
Route::get('/dashboard', function () {
    return Inertia::render('Home', [
        'name' => auth()->user()->name ?? 'Usuario'
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// ========================================
// AUTHENTICATED ROUTES WITH PROPER MIDDLEWARE AND CONSISTENT NAMING
// ========================================
Route::middleware(['auth', 'verified'])->group(function () {

    // ========================================
    // SALES MANAGEMENT - Secure Web Routes
    // ========================================
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesWebController::class, 'index'])->middleware('permission:sales.index')->name('index');
        Route::get('/create', [SalesWebController::class, 'create'])->middleware('permission:sales.create')->name('create');
        Route::post('/', [SalesWebController::class, 'store'])->middleware('permission:sales.create')->name('store');
        Route::get('/{id}', [SalesWebController::class, 'show'])->middleware('permission:sales.show')->name('show');
    });

    // ========================================
    // PURCHASES MANAGEMENT - Secure Web Routes
    // ========================================
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchasesWebController::class, 'index'])->middleware('permission:purchases.index')->name('index');
        Route::get('/create', [PurchasesWebController::class, 'create'])->middleware('permission:purchases.create')->name('create');
        Route::post('/', [PurchasesWebController::class, 'store'])->middleware('permission:purchases.create')->name('store');
        Route::get('/{id}', [PurchasesWebController::class, 'show'])->middleware('permission:purchases.show')->name('show');
    });

    // ========================================
    // REFUNDS MANAGEMENT - Web Routes
    // ========================================
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Refunds/index');
        })->middleware('permission:refunds.index')->name('index');

        Route::get('/create', function () {
            return Inertia::render('Refunds/form');
        })->middleware('permission:refunds.create')->name('create');

        Route::post('/', [RefundsController::class, 'store'])->middleware('permission:refunds.create')->name('store');

        Route::get('/{id}', function ($id) {
            return Inertia::render('Refunds/show', ['id' => $id]);
        })->middleware('permission:refunds.show')->name('show');
    });

    // ========================================
    // PERSONS MANAGEMENT (Clients, Providers, Employees)
    // ========================================
    Route::prefix('persons')->name('persons.')->group(function () {
        // Clients
        Route::get('/clients', function () {
            return Inertia::render('Clients/index');
        })->middleware('permission:clients.index')->name('clients.index');

        Route::get('/clients/{id}', function ($id) {
            return Inertia::render('Clients/show', ['id' => $id]);
        })->middleware('permission:clients.show')->name('clients.show');

        Route::post('/clients', [PersonsController::class, 'store'])->middleware('permission:clients.create')->name('clients.store');
        Route::put('/clients/{id}', [PersonsController::class, 'update'])->middleware('permission:clients.update')->name('clients.update');
        Route::delete('/clients/{id}', [PersonsController::class, 'destroy'])->middleware('permission:clients.destroy')->name('clients.destroy');

        // Providers
        Route::get('/providers', function () {
            return Inertia::render('Providers/index');
        })->middleware('permission:providers.index')->name('providers.index');

        Route::get('/providers/{id}', function ($id) {
            return Inertia::render('Providers/show', ['id' => $id]);
        })->middleware('permission:providers.show')->name('providers.show');

        Route::post('/providers', [PersonsController::class, 'store'])->middleware('permission:providers.create')->name('providers.store');
        Route::put('/providers/{id}', [PersonsController::class, 'update'])->middleware('permission:providers.update')->name('providers.update');
        Route::delete('/providers/{id}', [PersonsController::class, 'destroy'])->middleware('permission:providers.destroy')->name('providers.destroy');

        // Employees
        Route::get('/employees', function () {
            return Inertia::render('Employees/index');
        })->middleware('permission:employees.index')->name('employees.index');

        Route::get('/employees/{id}', function ($id) {
            return Inertia::render('Employees/show', ['id' => $id]);
        })->middleware('permission:employees.show')->name('employees.show');

        Route::post('/employees', [PersonsController::class, 'store'])->middleware('permission:employees.create')->name('employees.store');
        Route::put('/employees/{id}', [PersonsController::class, 'update'])->middleware('permission:employees.update')->name('employees.update');
        Route::delete('/employees/{id}', [PersonsController::class, 'destroy'])->middleware('permission:employees.destroy')->name('employees.destroy');
    });

    // ========================================
    // TILLS MANAGEMENT - Web Routes
    // ========================================
    Route::prefix('tills')->name('tills.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Tills/index');
        })->middleware('permission:tills.index')->name('index');

        Route::get('/{id}', function ($id) {
            return Inertia::render('Tills/show', ['id' => $id]);
        })->middleware('permission:tills.show')->name('show');

        Route::get('/{id}/close-detailed', function ($id) {
            return Inertia::render('Tills/tillsCloseReportDetailed', ['id' => $id]);
        })->middleware('permission:tills.show')->name('close-detailed');

        Route::post('/', [TillsController::class, 'store'])->middleware('permission:tills.create')->name('store');
        Route::put('/{id}', [TillsController::class, 'update'])->middleware('permission:tills.update')->name('update');
        Route::delete('/{id}', [TillsController::class, 'destroy'])->middleware('permission:tills.destroy')->name('destroy');

        // Till operations
        Route::post('/{id}/open', [TillsProcessController::class, 'cashOpening'])->middleware('permission:tills.update')->name('open');
        Route::post('/{id}/close', [TillsProcessController::class, 'close'])->middleware('permission:tills.update')->name('close');
        Route::post('/{id}/deposit', [TillsProcessController::class, 'deposit'])->middleware('permission:tills.update')->name('deposit');
        Route::post('/{id}/transfer', [TillsProcessController::class, 'transfer'])->middleware('permission:tills.update')->name('transfer');
    });

    // ========================================
    // USER MANAGEMENT - Web Routes
    // ========================================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->middleware('permission:users.index')->name('index');
        Route::get('/{id}', [UsersController::class, 'show'])->middleware('permission:users.show')->name('show');
        Route::post('/', [UsersController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::put('/{id}', [UsersController::class, 'update'])->middleware('permission:users.update')->name('update');
        Route::delete('/{id}', [UsersController::class, 'destroy'])->middleware('permission:users.destroy')->name('destroy');
    });

    // ========================================
    // ROLES MANAGEMENT - Web Routes
    // ========================================
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->middleware('permission:roles.index')->name('index');

        Route::get('/{id}', [RolesController::class, 'show'])->middleware('permission:roles.show')->name('show');
    });

    // ========================================
    // PRODUCTS MANAGEMENT - Web Routes
    // ========================================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductsController::class, 'index'])->middleware('permission:products.index')->name('index');
        Route::get('/{id}', [ProductsController::class, 'show'])->middleware('permission:products.show')->name('show');
        Route::post('/', [ProductsController::class, 'store'])->middleware('permission:products.create')->name('store');
        Route::put('/{id}', [ProductsController::class, 'update'])->middleware('permission:products.update')->name('update');
        Route::delete('/{id}', [ProductsController::class, 'destroy'])->middleware('permission:products.destroy')->name('destroy');
    });

    // ========================================
    // CATALOG MANAGEMENT - Web Routes (Reference Data)
    // ========================================

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoriesController::class, 'index'])->middleware('permission:categories.index')->name('index');
        Route::get('/{id}', [CategoriesController::class, 'show'])->middleware('permission:categories.show')->name('show');
        Route::post('/', [CategoriesController::class, 'store'])->middleware('permission:categories.create')->name('store');
        Route::put('/{id}', [CategoriesController::class, 'update'])->middleware('permission:categories.update')->name('update');
        Route::delete('/{id}', [CategoriesController::class, 'destroy'])->middleware('permission:categories.destroy')->name('destroy');
    });

    // Brands
    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->middleware('permission:brands.index')->name('index');
        Route::get('/{id}', [BrandController::class, 'show'])->middleware('permission:brands.show')->name('show');
        Route::post('/', [BrandController::class, 'store'])->middleware('permission:brands.create')->name('store');
        Route::put('/{id}', [BrandController::class, 'update'])->middleware('permission:brands.update')->name('update');
        Route::delete('/{id}', [BrandController::class, 'destroy'])->middleware('permission:brands.destroy')->name('destroy');
    });

    // Measurement Units
    Route::prefix('measurement-units')->name('measurement-units.')->group(function () {
        Route::get('/', [MeasurementUnitsController::class, 'index'])->middleware('permission:measurement_units.index')->name('index');
        Route::get('/{id}', [MeasurementUnitsController::class, 'show'])->middleware('permission:measurement_units.show')->name('show');
        Route::post('/', [MeasurementUnitsController::class, 'store'])->middleware('permission:measurement_units.create')->name('store');
        Route::put('/{id}', [MeasurementUnitsController::class, 'update'])->middleware('permission:measurement_units.update')->name('update');
        Route::delete('/{id}', [MeasurementUnitsController::class, 'destroy'])->middleware('permission:measurement_units.destroy')->name('destroy');
    });

    // Person Types
    Route::prefix('person-types')->name('person-types.')->group(function () {
        Route::get('/', [PersonTypesController::class, 'index'])->middleware('permission:persontypes.index')->name('index');
        Route::get('/{id}', [PersonTypesController::class, 'show'])->middleware('permission:persontypes.show')->name('show');
        Route::post('/', [PersonTypesController::class, 'store'])->middleware('permission:persontypes.create')->name('store');
        Route::put('/{id}', [PersonTypesController::class, 'update'])->middleware('permission:persontypes.update')->name('update');
        Route::delete('/{id}', [PersonTypesController::class, 'destroy'])->middleware('permission:persontypes.destroy')->name('destroy');
    });

    // Till Types
    Route::prefix('till-types')->name('till-types.')->group(function () {
        Route::get('/', [TillTypeController::class, 'index'])->middleware('permission:tilltypes.index')->name('index');
        Route::get('/{id}', [TillTypeController::class, 'show'])->middleware('permission:tilltypes.show')->name('show');
        Route::post('/', [TillTypeController::class, 'store'])->middleware('permission:tilltypes.create')->name('store');
        Route::put('/{id}', [TillTypeController::class, 'update'])->middleware('permission:tilltypes.update')->name('update');
        Route::delete('/{id}', [TillTypeController::class, 'destroy'])->middleware('permission:tilltypes.destroy')->name('destroy');
    });

    // IVA Types
    Route::prefix('iva-types')->name('iva-types.')->group(function () {
        Route::get('/', [IvaTypeController::class, 'index'])->middleware('permission:ivatypes.index')->name('index');
        Route::get('/{id}', [IvaTypeController::class, 'show'])->middleware('permission:ivatypes.show')->name('show');
        Route::post('/', [IvaTypeController::class, 'store'])->middleware('permission:ivatypes.create')->name('store');
        Route::put('/{id}', [IvaTypeController::class, 'update'])->middleware('permission:ivatypes.update')->name('update');
        Route::delete('/{id}', [IvaTypeController::class, 'destroy'])->middleware('permission:ivatypes.destroy')->name('destroy');
    });

    // Payment Types
    Route::prefix('payment-types')->name('payment-types.')->group(function () {
        Route::get('/', [PaymentTypesController::class, 'index'])->middleware('permission:paymenttypes.index')->name('index');
        Route::get('/{id}', [PaymentTypesController::class, 'show'])->middleware('permission:paymenttypes.show')->name('show');
        Route::post('/', [PaymentTypesController::class, 'store'])->middleware('permission:paymenttypes.create')->name('store');
        Route::put('/{id}', [PaymentTypesController::class, 'update'])->middleware('permission:paymenttypes.update')->name('update');
        Route::delete('/{id}', [PaymentTypesController::class, 'destroy'])->middleware('permission:paymenttypes.destroy')->name('destroy');
    });

    // Contact Types
    Route::prefix('contact-types')->name('contact-types.')->group(function () {
        Route::get('/', [ContactTypesController::class, 'index'])->middleware('permission:contacttypes.index')->name('index');
        Route::get('/{id}', [ContactTypesController::class, 'show'])->middleware('permission:contacttypes.show')->name('show');
        Route::post('/', [ContactTypesController::class, 'store'])->middleware('permission:contacttypes.create')->name('store');
        Route::put('/{id}', [ContactTypesController::class, 'update'])->middleware('permission:contacttypes.update')->name('update');
        Route::delete('/{id}', [ContactTypesController::class, 'destroy'])->middleware('permission:contacttypes.destroy')->name('destroy');
    });

    // ========================================
    // GEOGRAPHIC DATA - Web Routes
    // ========================================

    // Countries
    Route::prefix('countries')->name('countries.')->group(function () {
        Route::get('/', [CountriesController::class, 'index'])->middleware('permission:countries.index')->name('index');
        Route::get('/{id}', [CountriesController::class, 'show'])->middleware('permission:countries.show')->name('show')->where('id', '[0-9]+');
        Route::get('/search', [CountriesController::class, 'search'])->middleware('permission:countries.index')->name('countries.search');
        Route::post('/', [CountriesController::class, 'store'])->middleware('permission:countries.create')->name('store');
        Route::put('/{id}', [CountriesController::class, 'update'])->middleware('permission:countries.update')->name('update');
        Route::delete('/{id}', [CountriesController::class, 'destroy'])->middleware('permission:countries.destroy')->name('destroy');
    });

    // States
    Route::prefix('states')->name('states.')->group(function () {
        Route::get('/', [StatesController::class, 'index'])->middleware('permission:states.index')->name('index');
        Route::get('/{id}', [StatesController::class, 'show'])->middleware('permission:states.show')->name('show')->where('id', '[0-9]+');
        Route::get('/search', [StatesController::class, 'search'])->middleware('permission:states.index')->name('state.search');
        Route::post('/', [StatesController::class, 'store'])->middleware('permission:states.create')->name('store');
        Route::put('/{id}', [StatesController::class, 'update'])->middleware('permission:states.update')->name('update');
        Route::delete('/{id}', [StatesController::class, 'destroy'])->middleware('permission:states.destroy')->name('destroy');
    });

    // Cities
    Route::prefix('cities')->name('cities.')->group(function () {
        Route::get('/', [CitiesController::class, 'index'])->middleware('permission:cities.index')->name('index');
        Route::get('/{id}', [CitiesController::class, 'show'])->middleware('permission:cities.show')->name('show');
        Route::post('/', [CitiesController::class, 'store'])->middleware('permission:cities.create')->name('store');
        Route::put('/{id}', [CitiesController::class, 'update'])->middleware('permission:cities.update')->name('update');
        Route::delete('/{id}', [CitiesController::class, 'destroy'])->middleware('permission:cities.destroy')->name('destroy');
    });

    // ========================================
    // REPORTS - Web Routes
    // ========================================
    Route::get('/reports', function () {
        return Inertia::render('Reports/index');
    })->middleware('permission:reports.show')->name('reports.index');
});