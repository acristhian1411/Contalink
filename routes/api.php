<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TillTypes\TillTypeController;
use App\Http\Controllers\PersonTypes\PersonTypesController;
use App\Http\Controllers\AccountPlans\AccountPlanController;
use App\Http\Controllers\Categories\CategoriesController;
use App\Http\Controllers\Cities\CitiesController;
use App\Http\Controllers\Countries\CountriesController;
use App\Http\Controllers\IvaTypes\IvaTypeController;
use App\Http\Controllers\PaymentTypes\PaymentTypesController;
use App\Http\Controllers\Persons\PersonsController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\States\StatesController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\ProofPaypments\ProofPaymentsController;
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\Sales\SalesReportController;
use App\Http\Controllers\SalesDetails\SalesDetailsController;
use App\Http\Controllers\Sales\SaleStoreController;
use App\Http\Controllers\Sales\SaleDeleteController;
use App\Http\Controllers\Purchases\PurchasesController;
use App\Http\Controllers\Purchases\PurchasesReportController;
use App\Http\Controllers\Purchases\PurchaseDeleteController;
use App\Http\Controllers\PurchasesDetails\PurchasesDetailsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Roles\RolesController;
use App\Http\Controllers\Permissions\PermissionsController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\ContactTypes\ContactTypesController;
use App\Http\Controllers\Purchases\PurchaseStoreController;
use App\Http\Controllers\MeasurementUnits\MeasurementUnitsController;
use App\Http\Controllers\TillsProcess\TillsProcessController;
use App\Http\Controllers\TillsTransfers\TillsTransfersController;
use App\Http\Controllers\Refunds\RefundsController;
use App\Http\Controllers\RefundDetails\RefundDetailsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/roles', [RolesController::class, 'index'])->summary('Get list of roles with pagination.');
Route::get('/roles/{id}', [RolesController::class, 'show'])->description('Show a role.');
Route::post('/roles', [RolesController::class, 'store']);
Route::put('/roles/{id}', [RolesController::class, 'update'])->description('Update a role.');
Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->description('Delete a role.');
Route::post('/roles/{roleId}/permissions', [RolesController::class, 'assignPermissionsToRole']);
Route::delete('/roles/{roleId}/permissions', [RolesController::class, 'removePermissionsFromRole']);

Route::get('/users', [UsersController::class, 'index'])->description('Get list of users with pagination.');
Route::get('/users/{id}', [UsersController::class, 'show'])->description('Show a user.');
Route::get('/users/{id}/roles', [UsersController::class, 'showPermissionsByRole'])->description('Show permissions for a role');
Route::get('/users/{id}/rolesnotcontain', [UsersController::class, 'showPermissionsNotContainRole'])->description('Show permissions for a role');
Route::post('/users/{id}/assign-role', [UsersController::class, 'assignRole']);

Route::get('/permissions', [PermissionsController::class, 'index'])->description('Get list of permissions with pagination.');
Route::get('/permissions/{id}', [PermissionsController::class, 'show'])->description('Show a permission.');
Route::get('/permissions/{id}/roles', [PermissionsController::class, 'showPermissionsByRole'])->description('Show permissions for a role');
Route::get('/permissions/{id}/rolesnotcontain', [PermissionsController::class, 'showPermissionsNotContainRole'])->description('Show permissions for a role');

// Route::post('/login', [AuthController::class, 'login'])->name('login');
// Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/register', [AuthController::class, 'create'])->name('register');
//routes for tilltyoes
Route::get('tilltypes', [TillTypeController::class, 'index'])->description('Get list of till types with pagination.');
Route::post('tilltypes', [TillTypeController::class, 'store'])->description('Store a new till type.');
Route::put('tilltypes/{id}', [TillTypeController::class, 'update'])->description('Update a till type.');
Route::get('tilltypes/{id}', [TillTypeController::class, 'show'])->description('Show a till type.');
Route::delete('tilltypes/{id}', [TillTypeController::class, 'destroy'])->description('Delete a till type.');
//routes for personTypes
Route::get('persontypes', [PersonTypesController::class, 'index'])->description('Get list of person types with pagination.');
Route::post('persontypes', [PersonTypesController::class, 'store']);
Route::put('persontypes/{id}', [PersonTypesController::class, 'update']);
Route::get('persontypes/{id}', [PersonTypesController::class, 'show']);
Route::delete('persontypes/{id}', [PersonTypesController::class, 'destroy']);
//routes for accountplans
Route::get('accountplans', [AccountPlanController::class, 'index']);
Route::post('accountplans', [AccountPlanController::class, 'store']);
Route::put('accountplans/{id}', [AccountPlanController::class, 'update']);
Route::get('accountplans/{id}', [AccountPlanController::class, 'show']);
Route::delete('accountplans/{id}', [AccountPlanController::class, 'destroy']);
//routes for categories
Route::get('categories', [CategoriesController::class, 'index']);
Route::post('categories', [CategoriesController::class, 'store']);
Route::put('categories/{id}', [CategoriesController::class, 'update']);
Route::get('categories/{id}', [CategoriesController::class, 'show']);
Route::delete('categories/{id}', [CategoriesController::class, 'destroy']);
//routes for cities
Route::get('cities', [CitiesController::class, 'index']);
Route::post('cities', [CitiesController::class, 'store']);
Route::put('cities/{id}', [CitiesController::class, 'update']);
Route::get('cities/{id}', [CitiesController::class, 'show']);
Route::get('cities_state/{id}', [CitiesController::class, 'cityByStateId']);
Route::get('cities_country/{id}', [CitiesController::class, 'cityByCountryId']);
Route::delete('cities/{id}', [CitiesController::class, 'destroy']);
//routes for countries
Route::get('countries', [CountriesController::class, 'index']);
Route::post('countries', [CountriesController::class, 'store']);
Route::put('countries/{id}', [CountriesController::class, 'update']);
Route::get('countries/{id}', [CountriesController::class, 'show']);
Route::delete('countries/{id}', [CountriesController::class, 'destroy']);
//routes for ivatypes
Route::get('ivatypes', [IvaTypeController::class, 'index']);
Route::post('ivatypes', [IvaTypeController::class, 'store']);
Route::put('ivatypes/{id}', [IvaTypeController::class, 'update']);
Route::get('ivatypes/{id}', [IvaTypeController::class, 'show']);
Route::delete('ivatypes/{id}', [IvaTypeController::class, 'destroy']);
//routes for paymenttypes
Route::get('paymenttypes', [PaymentTypesController::class, 'index']);
Route::post('paymenttypes', [PaymentTypesController::class, 'store']);
Route::put('paymenttypes/{id}', [PaymentTypesController::class, 'update']);
Route::get('paymenttypes/{id}', [PaymentTypesController::class, 'show']);
Route::delete('paymenttypes/{id}', [PaymentTypesController::class, 'destroy']);
//routes for persons
Route::get('persons', [PersonsController::class, 'index']);
Route::post('persons', [PersonsController::class, 'store']);
Route::put('persons/{id}', [PersonsController::class, 'update']);
Route::get('persons/{id}', [PersonsController::class, 'show']);
Route::get('personsbytype/{id}', [PersonsController::class, 'personByType']);
Route::delete('persons/{id}', [PersonsController::class, 'destroy']);
Route::get('persons-search-by-type/{id}', [PersonsController::class, 'searchPerType']);
// routes for brands
Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/{id}', [BrandController::class, 'show']);
Route::post('brands', [BrandController::class, 'store']);
Route::put('brands/{id}', [BrandController::class, 'update']);
Route::delete('brands/{id}', [BrandController::class, 'destroy']);
//routes for products
Route::get('products', [ProductsController::class, 'index']);
Route::post('products', [ProductsController::class, 'store']);
Route::put('products/{id}', [ProductsController::class, 'update']);
Route::get('products/{id}', [ProductsController::class, 'show']);
Route::delete('products/{id}', [ProductsController::class, 'destroy']);
//routes for states
Route::get('states', [StatesController::class, 'index']);
Route::post('states', [StatesController::class, 'store']);
Route::put('states/{id}', [StatesController::class, 'update']);
Route::get('states/{id}', [StatesController::class, 'show']);
Route::get('states_country/{id}', [StatesController::class, 'getStatesByCountry']);
Route::delete('states/{id}', [StatesController::class, 'destroy']);
//routes for tills
Route::get('tills', [TillsController::class, 'index']);
Route::post('tills', [TillsController::class, 'store']);
Route::put('tills/{id}', [TillsController::class, 'update']);
Route::get('tills/{id}', [TillsController::class, 'show']);
Route::get('tills/{id}/amount', [TillsController::class, 'showTillAmount']);
Route::get('tills/{id}/byPerson',[TillsController::class, 'showTillsByUser']);
Route::get('tills_tilltype/{id}', [TillsController::class, 'getByTypeId']);
Route::post('tills/{id}/open', [TillsProcessController::class, 'cashOpening']);
Route::post('tills/{id}/close', [TillsProcessController::class, 'close']);
Route::get('tills/{id}/closeReportResume', [TillDetailsController::class, 'closeReportResume']);
Route::get('tills/{id}/closeReportDetailed', [TillDetailsController::class, 'closeReportDetailed']);
Route::post('tills/{id}/deposit', [TillsProcessController::class, 'deposit']);
Route::post('tills/{id}/transfer', [TillsProcessController::class, 'transfer']);
Route::delete('tills/{id}', [TillsController::class, 'destroy']);
//routes for tilldetails
Route::get('tilldetails', [TillDetailsController::class, 'index']);
Route::post('tilldetails', [TillDetailsController::class, 'store']);
Route::get('tilldetails/{till_id}/history', [TillDetailsController::class, 'showByTillIdAndDate']);
Route::put('tilldetails/{id}', [TillDetailsController::class, 'update']);
Route::get('tilldetails/{id}', [TillDetailsController::class, 'show']);
Route::delete('tilldetails/{id}', [TillDetailsController::class, 'destroy']);
//routes for tillsTransfers
Route::get('tillstransfers', [TillsTransfersController::class, 'index']);
Route::post('tillstransfers', [TillsTransfersController::class, 'store']);
Route::put('tillstransfers/{id}', [TillsTransfersController::class, 'update']);
Route::get('tillstransfers/{id}', [TillsTransfersController::class, 'show']);
Route::get('tillstransfers/{id}/history', [TillsTransfersController::class, 'showByTillIdAndDate']);
Route::delete('tillstransfers/{id}', [TillsTransfersController::class, 'destroy']);
//routes for TillDetailProofPayments
Route::get('tilldetailproofpayments', [TillDetailProofPaymentsController::class, 'index']);
Route::post('tilldetailproofpayments', [TillDetailProofPaymentsController::class, 'store']);
Route::put('tilldetailproofpayments/{id}', [TillDetailProofPaymentsController::class, 'update']);
Route::get('tilldetailproofpayments/{id}', [TillDetailProofPaymentsController::class, 'show']);
Route::delete('tilldetailproofpayments/{id}', [TillDetailProofPaymentsController::class, 'destroy']);
//routes for ProofPaypments
Route::get('proofpaypments', [ProofPaymentsController::class, 'index']);
Route::post('proofpaypments', [ProofPaymentsController::class, 'store']);
Route::post('proofpaypments_multiple', [ProofPaymentsController::class, 'storeMultiple']);
Route::put('proofpaypments_multiple', [ProofPaymentsController::class, 'updateMultiple']);
Route::put('proofpaypments/{id}', [ProofPaymentsController::class, 'update']);
Route::get('proofpaypments/{id}', [ProofPaymentsController::class, 'show']);
Route::get('proofpaypments_type/{id}', [ProofPaymentsController::class, 'showByType']);
Route::delete('proofpaypments/{id}', [ProofPaymentsController::class, 'destroy']);
//routes for sales
Route::get('sales', [SalesController::class, 'index']);
Route::post('sales', [SalesController::class, 'store']);
Route::post('storesale', [SaleStoreController::class, 'store']);
Route::delete('deletesale/{id}', [SaleDeleteController::class, 'destroy'])->whereNumber('id')->description('Delete a complete sale with stock and till reversal.');
Route::put('sales/{id}', [SalesController::class, 'update']);
Route::get('sales/{id}', [SalesController::class, 'show'])->whereNumber('id');
Route::get('salesByNumber/{searchTerm}', [SalesController::class, 'searchByNumber']);
Route::delete('sales/{id}', [SalesController::class, 'destroy']);
// routes for sales reports
Route::get('sales/report', [SalesReportController::class, 'getSalesReport']);
Route::get('sales/report/pdf', [SalesReportController::class, 'getSalesReport']);

//routes for salesdetails
Route::get('salesdetails', [SalesDetailsController::class, 'index']);
Route::post('salesdetails-many', [SalesDetailsController::class, 'storeMany']);
Route::post('salesdetails', [SalesDetailsController::class, 'store']);
Route::put('salesdetails/{id}', [SalesDetailsController::class, 'update']);
Route::get('salesdetails/{id}', [SalesDetailsController::class, 'show']);
Route::delete('salesdetails/{id}', [SalesDetailsController::class, 'destroy']);
//routes for Purchases
Route::get('purchases', [PurchasesController::class, 'index']);
Route::post('purchases', [PurchasesController::class, 'store']);
Route::put('purchases/{id}', [PurchasesController::class, 'update']);
Route::get('purchases/{id}', [PurchasesController::class, 'show'])->whereNumber('id');
Route::get('purchases/report', [PurchasesReportController::class, 'getPurchasesReport']);
Route::get('purchases/report/pdf', [PurchasesReportController::class, 'getPurchasesReport']);
Route::delete('purchases/{id}', [PurchasesController::class, 'destroy']);
Route::delete('deletepurchases/{id}', [PurchaseDeleteController::class, 'destroy']);
//routes for PurchasesDetails
Route::get('purchasesdetails', [PurchasesDetailsController::class, 'index']);
Route::post('purchasesdetails', [PurchasesDetailsController::class, 'store']);
Route::post('purchasesdetails-many', [PurchasesDetailsController::class, 'storeMany']);
Route::put('purchasesdetails/{id}', [PurchasesDetailsController::class, 'update']);
Route::get('purchasesdetails/{id}', [PurchasesDetailsController::class, 'show']);
Route::delete('purchasesdetails/{id}', [PurchasesDetailsController::class, 'destroy']);
// routes for purchaseStore
Route::post('storePurchase', [PurchaseStoreController::class, 'store']);
// routes for Refunds
Route::get('refunds', [RefundsController::class, 'index']);
Route::post('refunds', [RefundsController::class, 'store']);
Route::put('refunds/{id}', [RefundsController::class, 'update']);
Route::get('refunds/{id}', [RefundsController::class, 'show']);
Route::delete('refunds/{id}', [RefundsController::class, 'destroy']);
// routes for RefundDetails
Route::get('refunddetails', [RefundDetailsController::class, 'index']);
Route::post('refunddetails', [RefundDetailsController::class, 'store']);
Route::post('refunddetailsMany', [RefundDetailsController::class, 'storeMany']);
Route::put('refunddetails/{id}', [RefundDetailsController::class, 'update']);
Route::get('refunddetails/{id}', [RefundDetailsController::class, 'show']);
Route::delete('refunddetails/{id}', [RefundDetailsController::class, 'destroy']);
// routes for ContactTypes
Route::get('contacttypes', [ContactTypesController::class, 'index']);
Route::post('contacttypes', [ContactTypesController::class, 'store']);
Route::put('contacttypes/{id}', [ContactTypesController::class, 'update']);
Route::get('contacttypes/{id}', [ContactTypesController::class, 'show']);
Route::delete('contacttypes/{id}', [ContactTypesController::class, 'destroy']);

// routes for MeasurementUnits
Route::get('measurement-units', [MeasurementUnitsController::class, 'index'])->description('Get list of measurement units with pagination.');
Route::get('measurement-units/active', [MeasurementUnitsController::class, 'active'])->description('Get list of active measurement units.');
Route::post('measurement-units', [MeasurementUnitsController::class, 'store'])->description('Store a new measurement unit.');
Route::get('measurement-units/{id}', [MeasurementUnitsController::class, 'show'])->description('Show a measurement unit.');
Route::put('measurement-units/{id}', [MeasurementUnitsController::class, 'update'])->description('Update a measurement unit.');
Route::delete('measurement-units/{id}', [MeasurementUnitsController::class, 'destroy'])->description('Delete a measurement unit.');
Route::patch('measurement-units/{id}/activate', [MeasurementUnitsController::class, 'activate'])->description('Activate a measurement unit.');
Route::patch('measurement-units/{id}/deactivate', [MeasurementUnitsController::class, 'deactivate'])->description('Deactivate a measurement unit.');


