<?php

namespace App\Http\Controllers\PurchasesDetails;

use App\Models\PurchasesDetails;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class PurchasesDetailsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            $t = PurchasesDetails::query()->first();
            $query = PurchasesDetails::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            return $this->showAll($datos, 200);
        }catch(\Exception $e){   
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se obtenían los datos'],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try{
            $reglas = [
                'purchase_id' => 'required',
                'product_id' => 'required',
                'pd_desc' => 'required',
                'pd_qty' => 'required',
                'pd_amount' => 'required'
            ];
            $request->validate($reglas);
            $purchasesDetails = PurchasesDetails::create($request->all());
            return $this->showAfterAction($purchasesDetails,'create', 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se creaba el registro'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }
    
    /**
     * Almacena muchos detalles de compra a la vez
     * @param  \Illuminate\Http\Request  $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeMany(Request $req){
        try{
            $reglas = [
                'details'=> 'required:array'
            ];
            $req->validate($reglas);
            $details = PurchasesDetails::insert($req->details);
            $algo = [];
            
            return $this->showAfterAction($algo,'create', 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se creaba el registro'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ],422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchasesDetails  $purchasesDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try{
            $purchasesDetails = PurchasesDetails::findOrFail($id);
            $audits = $purchasesDetails->audits;
            return $this->showOne($purchasesDetails,$audits,200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se obtenía el registro'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchasesDetails  $purchasesDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{
            $reglas = [
                'purchase_id' => 'required',
                'product_id' => 'required',
                'pd_desc' => 'required',
                'pd_qty' => 'required',
                'pd_amount' => 'required'
            ];
            $request->validate($reglas);
            $purchasesDetails = PurchasesDetails::findOrFail($id);
            $purchasesDetails->update($request->all());
            return $this->showAfterAction($purchasesDetails,'update', 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se actualizaba el registro'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchasesDetails  $purchasesDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try{
            $purchasesDetails = PurchasesDetails::findOrFail($id);
            $purchasesDetails->delete();
            return response()->json(['message'=>'Eliminado con exito!']);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se eliminaba el registro'],500);
        }
    }
}
