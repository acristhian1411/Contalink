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

Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/register', function () {
    return Inertia::render('Register/index');
});
Route::group(['middleware' => ['auth']],function(){

    Route::get('/roles', function () {
        return Inertia::render('Roles/index');
    })->middleware('permission:roles.index');

    Route::get('/roles/{id}', function ($id) {
        return Inertia::render('Roles/show', ['id' => $id]);
    })->middleware('permission:roles.show');

    Route::get('/users', [UsersController::class,'index'])->middleware('permission:users.index');
    Route::get('/users/{id}', [UsersController::class,'show'])->middleware('permission:users.show');
    Route::put('/users/{id}',[UsersController::class,'update'])->middleware('permission:users.update');
    Route::post('/users',[UsersController::class,'store'])->middleware('permission:users.create');
    Route::delete('/users/{id}',[UsersController::class,'destroy'])->middleware('permission:users.destroy');

    Route::get('/persontypes', [PersonTypesController::class,'index'])->name('persontypes')->middleware('permission:persontypes.index');
    Route::get('/persontypes/{id}',[PersonTypesController::class,'show'])->name('persontypes.show')->middleware('permission:persontypes.show');
    Route::put('/persontypes/{id}',[PersonTypesController::class,'update'])->name('persontypes.update')->middleware('permission:persontypes.update');
    Route::delete('/persontypes/{id}',[PersonTypesController::class,'destroy'])->name('persontypes.destroy')->middleware('permission:persontypes.destroy');

    // routes for measurments
    Route::get('/measurments', [MeasurementUnitsController::class,'index'])->name('measurement_units')->middleware('permission:measurement_units.index');
    Route::get('/measurments/{id}',[MeasurementUnitsController::class,'show'])->name('measurement_units.show')->middleware('permission:measurement_units.show');
    Route::put('/measurments/{id}',[MeasurementUnitsController::class,'update'])->name('measurement_units:update')->middleware('permission:measurement_units.update');
    Route::post('/measurments',[MeasurementUnitsController::class,'store'])->name('measurement_units:create')->middleware('permission:measurement_units.create');
    Route::delete('/measurments',[MeasurementUnitsController::class,'destroy'])->name('measurement_units:destroy')->middleware('permission:measurement_units.destroy');

    // routes for countries
    Route::get('/countries', [CountriesController::class,'index'])->name('countries')->middleware('permission:countries.index');
    Route::get('/countries/{id}',[CountriesController::class,'show'])->name('countries.show')->middleware('permission:countries.show');
    Route::put('/countries/{id}',[CountriesController::class,'update'])->name('countries:update')->middleware('permission:countries.update');
    Route::post('/countries',[CountriesController::class,'store'])->name('countries:create')->middleware('permission:countries.create');
    Route::delete('/countries',[CountriesController::class,'destroy'])->name('countries:destroy')->middleware('permission:countries.destroy');

    // routes for states
    Route::get('/states', [StatesController::class,'index'])->name('states')->middleware('permission:states.index');
    Route::get('/states/{id}',[StatesController::class,'show'])->name('states.show')->middleware('permission:states.show');
    Route::post('/states',[StatesController::class,'store'])->name('states:create')->middleware('permission:states.create');
    Route::put('/states/{id}',[StatesController::class,'update'])->name('states:update')->middleware('permission:states.update');
    Route::delete('/states/{id}',[StatesController::class,'destroy'])->name('states:destroy')->middleware('permission:states.destroy');

    // routes for cities
    Route::get('/cities', [CitiesController::class,'index'])->name('cities')->middleware('permission:cities.index');
    Route::get('/cities/{id}',[CitiesController::class,'show'])->name('cities.show')->middleware('permission:cities.show');
    Route::post('/cities',[CitiesController::class,'store'])->name('cities:create')->middleware('permission:cities.create');
    Route::put('/cities/{id}',[CitiesController::class,'update'])->name('cities:update')->middleware('permission:cities.update');
    Route::delete('/cities/{id}',[CitiesController::class,'destroy'])->name('cities:destroy')->middleware('permission:cities.destroy');

    Route::get('/tills',function(){
        return Inertia::render('Tills/index');
    })->middleware('permission:tills.index');

    Route::get('/tills/{id}', function ($id) {
        return Inertia::render('Tills/show', ['id' => $id]);
    })->middleware('permission:tills.show');

    Route::get('/tills/{id}/closeDetailed', function ($id) {
        return Inertia::render('Tills/tillsCloseReportDetailed', ['id' => $id]);
    })->middleware('permission:tills.show');

    Route::get('/tilltypes',[TillTypeController::class,'index'])->name('tilltypes')->middleware('permission:tilltypes.index');
    Route::get('/tilltypes/{id}',[TillTypeController::class,'show'])->name('tilltypes.show')->middleware('permission:tilltypes.show');
    Route::put('/tilltypes/{id}',[TillTypeController::class,'update'])->name('tilltypes:update')->middleware('permission:tilltypes.update');
    Route::post('/tilltypes',[TillTypeController::class,'store'])->name('tilltypes:create')->middleware('permission:tilltypes.create');
    Route::delete('/tilltypes/{id}',[TillTypeController::class,'destroy'])->name('tilltypes:destroy')->middleware('permission:tilltypes.destroy');

    Route::get('/ivatypes',[IvaTypeController::class,'index'])->name('ivatypes')->middleware('permission:ivatypes.index');
    Route::get('/ivatypes/{id}',[IvaTypeController::class,'show'])->name('ivatypes.show')->middleware('permission:ivatypes.show');
    Route::put('/ivatypes/{id}',[IvaTypeController::class,'update'])->name('ivatypes:update')->middleware('permission:ivatypes.update');
    Route::post('/ivatypes',[IvaTypeController::class,'store'])->name('ivatypes:create')->middleware('permission:ivatypes.create');
    Route::delete('/ivatypes/{id}',[IvaTypeController::class,'destroy'])->name('ivatypes:destroy')->middleware('permission:ivatypes.destroy');

    Route::get('/categories', [CategoriesController::class,'index'])->middleware('permission:categories.index');
    Route::get('/categories/{id}', [CategoriesController::class,'show'])->middleware('permission:categories.show');
    Route::put('/categories/{id}', [CategoriesController::class,'update'])->middleware('permission:categories.update');
    Route::post('/categories',[CategoriesController::class,'store'])->middleware('permission:categories.create');
    Route::delete('/categories/{id}',[CategoriesController::class,'destroy'])->middleware('permission:categories.destroy');

    
    Route::get('/paymenttypes',[PaymentTypesController::class,'index'])->name('persontypes.index')->middleware('permission:persontypes.index');
    Route::post('/paymenttypes',[PaymentTypesController::class,'store'])->name('persontypes.create')->middleware('permission:persontypes.create');
    Route::get('/paymenttypes/{id}',[PaymentTypesController::class,'show'])->name('paymenttypes.show')->middleware('permission:paymenttypes.show');
    Route::put('/paymenttypes/{id}',[PaymentTypesController::class,'update'])->name('paymenttypes.update')->middleware('permission:paymenttypes.update');
    Route::delete('/paymenttypes/{id}',[PaymentTypesController::class,'destroy'])->name('paymenttypes.destroy')->middleware('permission:paymenttypes.destroy');
    
    Route::get('/brands', [BrandController::class,'index'])->middleware('permission:brands.index');
    Route::get('/brands/{id}',[BrandController::class,'show'])->middleware('permission:brands.show');
    Route::put('/brands/{id}',[BrandController::class,'update'])->middleware('permission:brands.update');
    Route::post('/brands',[BrandController::class,'store'])->middleware('permission:brands.create');
    Route::delete('/brands/{id}',[BrandController::class,'destroy'])->middleware('permission:brands.destroy');

    Route::get('/products', [ProductsController::class,'index'])->middleware('permission:products.index');
    Route::get('/products/{id}', [ProductsController::class,'show'])->middleware('permission:products.show');

    Route::get('/reports', function () {
        return Inertia::render('Reports/index');
    })->middleware('permission:reports.show');

    Route::get('/providers', function () {
        return Inertia::render('Providers/index');
    })->middleware('permission:providers.index');

    Route::get('/providers/{id}', function ($id) {
        return Inertia::render('Providers/show', ['id' => $id]);
    })->middleware('permission:providers.show');

    Route::get('/employees',function(){
        return Inertia::render('Employees/index');
    })->middleware('permission:employees.index');

    Route::get('/employees/{id}', function ($id) {
        return Inertia::render('Employees/show', ['id' => $id]);
    })->middleware('permission:employees.show');

    Route::get('/clients',function(){
        return Inertia::render('Clients/index');
    })->middleware('permission:clients.index');

    Route::get('/clients/{id}', function ($id) {
        return Inertia::render('Clients/show', ['id' => $id]);
    })->middleware('permission:clients.show');

    Route::get('/contacttypes',[ContactTypesController::class,'index'])->name('contacttypes.index')->middleware('permission:contacttypes.index');
    Route::get('/contacttypes/{id}', [ContactTypesController::class,'show'])->middleware('permission:contacttypes.show');
    Route::put('/contacttypes/{id}', [ContactTypesController::class,'update'])->middleware('permission:contacttypes.update');
    Route::post('/contacttypes', [ContactTypesController::class,'store'])->middleware('permission:contacttypes.create');
    Route::delete('/contacttypes/{id}', [ContactTypesController::class,'destroy'])->middleware('permission:contacttypes.destroy');

    Route::get('/purchases',function(){
        return Inertia::render('Purchases/List');
    })->middleware('permission:purchases.index');

    Route::get('/create-purchase', function () {
        return Inertia::render('Purchases/Form');
    })->middleware('permission:purchases.create');

    Route::get('/purchases/{id}', function ($id) {
        return Inertia::render('Purchases/Show', ['id' => $id]);
    })->middleware('permission:purchases.show');

    Route::get('/sales', function () {
        return Inertia::render('Sales/index');
    })->middleware('permission:sales.index');

    Route::get('sales/{id}',function($id){
        return Inertia::render('Sales/Show',['id'=> $id]);
    })->middleware('permission:sales.show');

    Route::get('/create-sales', function () {
        return Inertia::render('Sales/form');
    })->middleware('permission:sales.create');

    Route::get(`/refunds`,function(){
        return Inertia::render(`Refunds/index`);
    })->middleware('permission:sales.index');

    Route::get('/create-refunds', function () {
        return Inertia::render('Refunds/form');
    })->middleware('permission:sales.create');
});


Route::get('/', function () {
    return Inertia::render('Home', ['name' => 'Usuario']);
});


Route::get('/login', function () {
    return Inertia::render('Login/index');
})->name('login');

