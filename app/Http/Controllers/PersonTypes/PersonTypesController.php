<?php

namespace App\Http\Controllers\PersonTypes;

use App\Models\PersonTypes;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;

class PersonTypesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = PersonTypes::query()->first();
            $query = PersonTypes::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from, 'PersonTypes/index', 200);
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
                'p_type_desc' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $personType = PersonTypes::create($request->validated());
            return response()->json(['message'=>'Registro creado con exito','data'=>$personType]);
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
     * @param  \App\Models\PersonTypes  $personTypes
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $personType = PersonTypes::find($id);
            $audits = $personType->audits;
            if(request()->wantsJson()){
                return $this->showOne($personType,$audits,200);
            }
            return Inertia::render('PersonTypes/show', ['person_type' => $personType,'audits'=>$audits]);
        } catch(\Exception $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'No se pudo obtener los datos'
            ],500);
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
                'p_type_desc' => 'required|string|max:255',
            ]);

            // Update the till type
            $personTypes = PersonTypes::findOrFail($id);
            $personTypes->update($validatedData);
            if(request()->wantsJson()){
                return response()->json(['message'=>'Registro Actualizado con exito','data'=>$personTypes]);
            }
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
     * @param  \App\Models\PersonTypes  $personTypes
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $personTypes = PersonTypes::findOrFail($id);
            $personTypes->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}
