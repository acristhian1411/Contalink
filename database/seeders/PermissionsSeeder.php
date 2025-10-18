<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // permissions for reports
        Permission::firstOrCreate(['name'=>'reports.show','guard_name'=> 'web']);
        // permissions for locations
        Permission::firstOrCreate(['name'=>'locations.index','guard_name'=> 'web']);
        // permissions for configuration
        Permission::firstOrCreate(['name'=>'configuration.index','guard_name'=> 'web']);
        // permissions for administration
        Permission::firstOrCreate(['name'=>'administration.index','guard_name'=> 'web']);
        // permissions for reports
        Permission::firstOrCreate(['name'=>'reports.index','guard_name'=> 'web']);
        // permissions for productSidebar
        Permission::firstOrCreate(['name'=>'products.sidebar','guard_name'=> 'web']);
        // permissions for persontypes
        Permission::firstOrCreate(['name'=>'persontypes.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persontypes.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persontypes.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persontypes.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persontypes.destroy','guard_name'=> 'web']);
        // permissions for tills
        Permission::firstOrCreate(['name'=>'tills.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tills.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tills.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tills.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tills.destroy','guard_name'=> 'web']);
        // permissions for tilltypes
        Permission::firstOrCreate(['name'=>'tilltypes.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilltypes.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilltypes.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilltypes.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilltypes.destroy','guard_name'=> 'web']);
        // permissions for contacttypes
        Permission::firstOrCreate(['name'=>'contacttypes.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'contacttypes.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'contacttypes.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'contacttypes.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'contacttypes.destroy','guard_name'=> 'web']);
        // permissions for countries
        Permission::firstOrCreate(['name'=>'countries.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'countries.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'countries.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'countries.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'countries.destroy','guard_name'=> 'web']);
        // permissions for states
        Permission::firstOrCreate(['name'=>'states.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'states.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'states.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'states.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'states.destroy','guard_name'=> 'web']);
        // permissions for cities
        Permission::firstOrCreate(['name'=>'cities.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'cities.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'cities.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'cities.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'cities.destroy','guard_name'=> 'web']);
        // permissions for categories
        Permission::firstOrCreate(['name'=>'categories.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'categories.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'categories.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'categories.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'categories.destroy','guard_name'=> 'web']);
        // permissions for ivatypes
        Permission::firstOrCreate(['name'=>'ivatypes.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'ivatypes.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'ivatypes.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'ivatypes.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'ivatypes.destroy','guard_name'=> 'web']);
        // permissions for persons
        Permission::firstOrCreate(['name'=>'persons.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persons.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persons.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persons.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'persons.destroy','guard_name'=> 'web']);
        // permissions for providers
        Permission::firstOrCreate(['name'=>'providers.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'providers.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'providers.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'providers.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'providers.destroy','guard_name'=> 'web']);
        // permissions for clients
        Permission::firstOrCreate(['name'=>'clients.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'clients.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'clients.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'clients.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'clients.destroy','guard_name'=> 'web']);
        // permissions for employees
        Permission::firstOrCreate(['name'=>'employees.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'employees.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'employees.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'employees.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'employees.destroy','guard_name'=> 'web']);
        // permissions for users
        Permission::firstOrCreate(['name'=>'users.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'users.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'users.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'users.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'users.destroy','guard_name'=> 'web']);
        // permissions for roles
        Permission::firstOrCreate(['name'=>'roles.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'roles.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'roles.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'roles.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'roles.destroy','guard_name'=> 'web']);
        // permissions for permissions
        Permission::firstOrCreate(['name'=>'permissions.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'permissions.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'permissions.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'permissions.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'permissions.destroy','guard_name'=> 'web']);
        // permissions for brands
        Permission::firstOrCreate(['name'=>'brands.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'brands.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'brands.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'brands.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'brands.destroy','guard_name'=> 'web']);
        // permissions for products
        Permission::firstOrCreate(['name'=>'products.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'products.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'products.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'products.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'products.destroy','guard_name'=> 'web']);
        // permissions for accountplans
        Permission::firstOrCreate(['name'=>'accountplans.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'accountplans.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'accountplans.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'accountplans.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'accountplans.destroy','guard_name'=> 'web']);
        // permissions for paymenttypes
        Permission::firstOrCreate(['name'=>'paymenttypes.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'paymenttypes.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'paymenttypes.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'paymenttypes.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'paymenttypes.destroy','guard_name'=> 'web']);
        // permissions for proofpayments
        Permission::firstOrCreate(['name'=>'proofpayments.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'proofpayments.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'proofpayments.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'proofpayments.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'proofpayments.destroy','guard_name'=> 'web']);
        // permissions for tilldetails
        Permission::firstOrCreate(['name'=>'tilldetails.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetails.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetails.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetails.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetails.destroy','guard_name'=> 'web']);
        // permissions for tilldetailproofpayments
        Permission::firstOrCreate(['name'=>'tilldetailproofpayments.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetailproofpayments.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetailproofpayments.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetailproofpayments.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tilldetailproofpayments.destroy','guard_name'=> 'web']);
        // permissions for tillstranfers
        Permission::firstOrCreate(['name'=>'tillstranfers.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tillstranfers.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tillstranfers.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tillstranfers.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'tillstranfers.destroy','guard_name'=> 'web']);
        // permissions for purchases
        Permission::firstOrCreate(['name'=>'purchases.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchases.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchases.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchases.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchases.destroy','guard_name'=> 'web']);
        // permissions for purchasedetails
        Permission::firstOrCreate(['name'=>'purchasedetails.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchasedetails.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchasedetails.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchasedetails.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'purchasedetails.destroy','guard_name'=> 'web']);
        // permissions for sales
        Permission::firstOrCreate(['name'=>'sales.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'sales.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'sales.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'sales.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'sales.destroy','guard_name'=> 'web']);
        // permissions for salesdetails
        Permission::firstOrCreate(['name'=>'salesdetails.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'salesdetails.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'salesdetails.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'salesdetails.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'salesdetails.destroy','guard_name'=> 'web']);
        // permissions for measurement_units
        Permission::firstOrCreate(['name'=>'measurement_units.index','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'measurement_units.show','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'measurement_units.create','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'measurement_units.update','guard_name'=> 'web']);
        Permission::firstOrCreate(['name'=>'measurement_units.destroy','guard_name'=> 'web']);
    }
}