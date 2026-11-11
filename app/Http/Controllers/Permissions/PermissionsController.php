<?php

namespace App\Http\Controllers\Permissions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class PermissionsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = Permission::query()->first();
            $query = Permission::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from ,'',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $rules=[
                'name' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $permission = Permission::create($request->all());
            return response()->json(['message'=>'Registro creado con exito','data'=>$permission]);
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
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $permission = Permission::find($id);
            $audits = $permission->audits;
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showOne($permission,$audits,200);
        } catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Display permissions for a role
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function showPermissionsByRole(Request $req,$id){
        try{
            $role = Role::find($id);
            $permissions = $role->permissions;
            // $permissions = Permission::where('role_id',$id)->get();
            $from = ($req->wantsJson() || $req->boolean('wantsJson')) ? 'api' : 'web';
            // dd($from);
            return $this->showAll($permissions, $from ,'',200);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Display the specified resource.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showPermissionsNotContainRole(Request $req,$id){
        try{
            $role = Role::findById($id);

            // Obtiene todos los permisos
            $allPermissions = Permission::all();
    
            // Obtiene los permisos que ya están asignados al rol
            $assignedPermissions = $role->permissions->pluck('id')->toArray();
            // Filtra los permisos que no están asignados al rol
            $permissionsNotAssigned = $allPermissions->filter(function ($permission) use ($assignedPermissions) {
                return !in_array($permission->id, $assignedPermissions);
            });
            $from = ($req->wantsJson() || $req->boolean('wantsJson')) ? 'api' : 'web';
            return $this->showAll($permissionsNotAssigned, $from ,'',200);
        }
        catch(\Exception $e){
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
            $Permission = Permission::findOrFail($id);
            $Permission->update($validatedData);
            // Return a success response
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$Permission]);
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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $Permission = Permission::findOrFail($id);
            $Permission->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}