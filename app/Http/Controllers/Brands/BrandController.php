<?php

namespace App\Http\Controllers\Brands;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Models\Brand;
use Inertia\Inertia;

class BrandController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function index()
    {
        try{
            $t = Brand::query()->first();
            $query = Brand::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from, 'Brands/index',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function store(Request $request)
    {
        try{
            $rules = [
                'brand_name' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $brand = Brand::create($request->all());
            return response()->json(['message'=>'Registro creado con exito','data'=>$brand],201);
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
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try{
            $brand = Brand::findOrFail($id);
            $audits = $brand->audits;
            if(request()->wantsJson()){
                return $this->showOne($brand,$audits,200);
            }
            return Inertia::render('Brands/show', ['brand' => $brand,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update(Request $request, $id)
    {
        try{
            $rules = [
                'brand_name' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $brand = Brand::findOrFail($id);
            $brand->update($request->all());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$brand],200);
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
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function destroy($id)
    {
        try{
            $brand = Brand::findOrFail($id);
            $brand->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
