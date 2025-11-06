<?php

namespace App\Http\Controllers\Cities;

use App\Models\Cities;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;
class CitiesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            $t = Cities::query()->first();
            $query = Cities::query();
            $query = $this->filterData($query, $t)
            ->join('states','states.id','=','cities.state_id')
            ->join('countries','states.country_id','=','countries.id')
            ->select('cities.*','states.state_name', 'countries.country_name');
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from,'Cities/index', 200);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Almacena una nueva ciudad en la base de datos.
     *
     * @param \Illuminate\Http\Request $request Objeto de solicitud que contiene los siguientes datos:
     * 
     * - **city_name**: (string) Nombre de la ciudad. Requerido. Máximo 255 caracteres.
     * - **city_code**: (string) Código de la ciudad. Requerido. Máximo 255 caracteres.
     * - **state_id**: (int) ID del estado al que pertenece la ciudad. Requerido.
     * 
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos de la ciudad creada o con mensajes de error.
     * 
     * @throws \Illuminate\Validation\ValidationException Si los datos no cumplen con las reglas de validación.
     * @throws \Exception Si ocurre algún error durante la creación del registro.
     */
    public function store(Request $request)
    {
        try{
            $rules=[
                'city_name' => 'required|string|max:255',
                'city_code' => 'required|string|max:255',
                'state_id' => 'required|int',
            ];
            $request->validate($rules);
            $cities = Cities::create($request->validated());
            return $this->showAfterAction($cities,'create',201);
        }
        catch(\Illuminate\Validation\ValidationException $e){
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
            $cities = Cities::where('cities.id', $id)
            ->join('states','states.id','=','cities.state_id')
            ->join('countries','states.country_id','=','countries.id')
            ->select('cities.*','states.state_name', 'countries.country_name')
            ->first();
            $audits = $cities->audits;
            if(request()->wantsJson()){
                return $this->showOne($cities, $audits, 200);
            }
            return Inertia::render('Cities/show', ['city' => $cities, 'audits'=>$audits]);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update(Request $request, $id)
    {
        try{
            $rules=[
                'city_name' => 'required|string|max:255',
                'city_code' => 'required|string|max:255',
                'state_id' => 'required|int',
            ];
            $request->validate($rules);
            $cities = Cities::findOrFail($id);
            $cities->update($request->validated());
            $cities->save();
            return $this->showAfterAction($cities,'update',200);
        }
        catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=> $e->getMessage(),'message'=>'No se pudo actualizar los datos'],400);
        }
    }

    
    public function cityByStateId($id){
        try{
            $cities = Cities::where('state_id', $id)
            ->join('states','states.id','=','cities.state_id')
            ->join('countries','states.country_id','=','countries.id')
            ->get();
            return $this->showAll($cities, 200);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo obtener los datos']);
        }
    }

    
    public function cityByCountryId($id){
        try{
            $cities = Cities::join('states','states.id','=','cities.state_id')
            ->join('countries','states.country_id','=','countries.id')
            ->where('states.country_id', $id)
            ->get();
            return $this->showAll($cities, 200);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function destroy($id)
    {
        try{
            $cities = Cities::find($id);
            $cities->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo eliminar los datos']);
        }
    }
}
