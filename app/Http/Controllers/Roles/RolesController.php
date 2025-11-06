<?php

namespace App\Http\Controllers\Roles;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Permission;
use G4T\Swagger\Attributes\SwaggerSection;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\IndexRequest;
use Inertia\Inertia;
#[SwaggerSection('Roles')]
class RolesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function index(IndexRequest $req)
    {
        try{
            $t = Role::query()->first();
            $query = Role::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            // $datos = Role::all();
            $from = $req->wantsJson() ? 'api' : 'web';
            // dd($datos);
            return $this->showAll($datos, $from,'Roles/index',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    public function assignPermissionsToRole(Request $request, $roleId)
    {
        try {
            
            $role = Role::findOrFail($roleId);
            foreach ($request->permissions as $permission) {
                $role->givePermissionTo($permission); 
            }
            return response()->json([
                'message' => 'Permisos asignados correctamente al rol',
                'data' => $role->permissions,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son válidos',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo asignar los permisos al rol',
            ], 400);
        }
    }

    public function removePermissionsFromRole(Request $request, $roleId)
    {
        try {
            // Validamos que los permisos vengan en un array
            $validatedData = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,name', // Verifica que los permisos existan por nombre
            ]);

            // Buscamos el rol por ID
            $role = Role::findOrFail($roleId);

            // Removemos los permisos indicados
            $role->revokePermissionTo($validatedData['permissions']);

            return response()->json([
                'message' => 'Permisos removidos correctamente del rol',
                'data' => $role->permissions,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son válidos',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo remover los permisos del rol',
            ], 400);
        }
    }

   /**
 * Store a newly created role in storage.
 *
 * @bodyParam name string required The name of the role. Max 255 characters.
 * 
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
    public function store(StoreRoleRequest $request)
    {
        try{
            $role = Role::create($request->validated());
            return response()->json(['message'=>'Registro creado con exito','data'=>$role]);
        }catch(\Illuminate\Validation\ValidationException $e){
            // dd($e);
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=> $e->getMessage(),'message'=>'No se pudo crear registro'],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try{
            $role = Role::find($id);
            $audits = $role->audits;
            if(request()->wantsJson()){
                return $this->showOne($role,$audits,200);
            }
            return Inertia::render('Roles/show', ['role' => $role,'audits'=>$audits]);
        } catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request,$id)
    {
        try{
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            // Update the till type
            $Role = Role::findOrFail($id);
            $Role->update($validatedData);
            // Return a success response
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$Role]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar los datos'],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try{
            $Role = Role::findOrFail($id);
            $Role->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}