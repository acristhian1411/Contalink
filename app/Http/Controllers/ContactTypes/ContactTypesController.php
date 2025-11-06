<?php

namespace App\Http\Controllers\ContactTypes;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Models\ContactType;
use Inertia\Inertia;
class ContactTypesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = ContactType::query()->first();
            $query = ContactType::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from, 'ContactTypes/List',200);
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
    public function store(Request $request)
    {
        try{
            $rules = [
                'cont_type_desc' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $ContactType = ContactType::create($request->validated());
            return response()->json(['message'=>'Registro creado con exito','data'=>$ContactType],201);
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
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $ContactType = ContactType::findOrFail($id);
            $audits = $ContactType->audits;
            if(request()->wantsJson()){
                return $this->showOne($ContactType,$audits,200);
            }
            return Inertia::render('ContactTypes/Show', ['contacttype' => $ContactType,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ContactType  $ContactType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $rules = [
                'cont_type_desc' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $ContactType = ContactType::findOrFail($id);
            $ContactType->update($request->validated());
            if(request()->wantsJson()){
                return response()->json(['message'=>'Registro Actualizado con exito','data'=>$ContactType],200);
            }
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
     * @param  \App\Models\ContactType  $ContactType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $ContactType = ContactType::findOrFail($id);
            $ContactType->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
