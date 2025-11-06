<?php

namespace App\Http\Controllers\Sales;

use App\Models\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;
class SalesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = Sales::query()->first();
            $query = Sales::query();
            $query = $this->filterData($query, $t);
            $datos = $query
            ->with("person")
            ->get();
            return $this->showAll($datos, 'api','',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'], 500);
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
                'person_id' => 'required',
                'sale_date' => 'required',
                'sale_number' => 'required'
            ];
            $request->validate( $reglas);
            $data = $request->only(['person_id', 'sale_date', 'sale_number']);
            $sales = Sales::create($data);
            return $this->showAfterAction($sales,'create', 201);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'], 500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->errors(),
                'message'=>'Los datos enviados no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
        ], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sales  $sales
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $sales = Sales::findOrFail($id)->with('person')->with('tills_details', function ($query) {
                $query->with('till');
                $query->with('tillproofPayments')->with('tillproofPayments.proofPayments')->with('tillproofPayments.proofPayments.paymentType');
            })->with('sales_details', function ($query) {
                $query->with('product')->with('product.ivaType');
            })->with('audits')
            ->first();
            $audits = $sales->audits;
            if(request()->wantsJson()){
                return $this->showOne($sales,$audits, 200);
            }
            return Inertia::render('Sales/show', ['sales' => $sales, 'audits' => $audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sales  $sales
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $reglas = [
                'person_id' => 'required',
                'sale_desc' => 'required',
                'sale_date' => 'required',
                'sale_number' => 'required',
                'sale_status' => 'required',
                'sale_type' => 'required'
            ];
            $request->validate( $reglas);
            $data = $request->only(['person_id', 'sale_desc', 'sale_date', 'sale_number', 'sale_status', 'sale_type']);
            $sales = Sales::findOrFail($id);
            $sales->update($data);
            return $this->showAfterAction($sales,'update', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar los datos'], 500);
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
     * @param  \App\Models\Sales  $sales
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $sales = Sales::findOrFail($id);
            $sales->delete();
            return response()->json(['message'=>'Eliminado con exito!']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro'],500);
        }
    }

    /**
     * Search for a sale by its number.
     * this consult return the sale with all the details, the client, the tills and the payment types.
     * and each details with its product and iva type.
     *
     * @param string $searchTerm
     * @return \Illuminate\Http\Response
     */
    public function searchByNumber($searchTerm)
    {
        try{
            $sales = Sales::where('sale_number', $searchTerm)
            ->with('person')
            ->with(['tills_details' => function ($query) {
                $query->with('till');
                $query->with('tillproofPayments')->with('tillproofPayments.proofPayments')->with('tillproofPayments.proofPayments.paymentType');
            }])
            ->with(['sales_details' => function ($query) {
                $query->withTrashed()->with('product')->with('product.ivaType');
            }])
            ->first();
            if (!$sales) {
                return response()->json(['message'=>'No se encontro el registro','data'=>[]], 404);
            }
            return $this->showAfterAction($sales, 'show', 200);
        }catch(\Exception $e){
            dd($e);
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'], 500);
        }
    }
    
}
