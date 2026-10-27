<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        if($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        
            Inertia::share([
                'user' => function () {
                    if (Auth::user()) {
                        // Obtener los roles del usuario
                        $roles = Auth::user()->getRoleNames()->map(function($role) {
                            return $role->name; // Extraer solo el nombre del rol
                        })->toArray();
                        // Obtener los permisos asociados a esos roles
                        // dd($roles);
                        $permissions = Role::whereIn('name', $roles)
                                           ->with('permissions') // Asegúrate de tener la relación definida en el modelo Role
                                           ->get()
                                           ->pluck('permissions')
                                           ->flatten()
                                           ->pluck('name')
                                           ->unique()
                                           ->values()
                                           ->toArray(); // Extraer los nombres de todos los roles

                        return [
                            'id' => Auth::user()->id,
                            'person_id'=>Auth::user()->person_id,
                            'roles' => $roles,
                            'permissions' => $permissions,
                        ];
                    }
                    return null;
                },
        ]);
    }
}
