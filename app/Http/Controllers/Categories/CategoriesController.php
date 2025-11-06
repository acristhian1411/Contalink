<?php

namespace App\Http\Controllers\Categories;

use App\Models\Categories;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SecureBasicRequest;
use Inertia\Inertia;

class CategoriesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = Categories::query()->first();
            $query = Categories::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from, 'Categories/index', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SecureBasicRequest $request)
    {
        try{
            $categories = Categories::create($request->validated());
            return response()->json(['message'=>'Registro creado con exito','data'=>$categories],201);
        }
        catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e,
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ], 422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo crear el registro']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $audits = $categories->audits;
            if(request()->wantsJson()){
                return $this->showOne($categories,$audits,200);
            }
            return Inertia::render('Categories/show', ['category' => $categories,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function update(SecureBasicRequest $request, $id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $categories->update($request->validated());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$categories],200);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar el registro']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Categories  $categories
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $categories->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
