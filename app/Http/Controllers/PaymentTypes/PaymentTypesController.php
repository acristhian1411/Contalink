<?php

namespace App\Http\Controllers\PaymentTypes;

use App\Models\PaymentTypes;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class PaymentTypesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = PaymentTypes::query()->first();
            $query = PaymentTypes::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            return $this->showAll($datos, 200);
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
            $reglas = [
                'payment_type_desc' => 'required'
            ];
            $request->validate($reglas);
            $paymentTypes = PaymentTypes::create($request->all());
            return response()->json(['message'=>'Registro creado con exito','data'=>$paymentTypes],201);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e,
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo crear el registro']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $paymentTypes = PaymentTypes::findOrFail($id);
            $audits = $paymentTypes->audits;
            return $this->showOne($paymentTypes,$audits, 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,  $id)
    {
        try{
            $reglas = [
                'payment_type_desc' => 'required'
            ];
            $request->validate($reglas);
            $paymentTypes = PaymentTypes::findOrFail($id);
            $paymentTypes->update($request->all());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$paymentTypes],200);
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
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\Response
     */
    public function destroy( $id)
    {
        try{
            $paymentTypes = PaymentTypes::findOrFail($id);
            $paymentTypes->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
