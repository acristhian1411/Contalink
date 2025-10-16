<?php

namespace App\Http\Controllers\Purchases;

use App\Models\Purchases;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;

class PurchasesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            $t = Purchases::query()->first();
            $query = Purchases::query();
            $query = $this->filterData($query, $t);
            $datos = $query
            ->with('person')
            ->get();
            return $this->showAll($datos, 'api','',200);
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
                'person_id' => 'required',
                'purchase_date' => 'required',
                'purchase_number' => 'required',
            ];
            // dd($request);
            $request->validate($reglas);
            $purchases = Purchases::create($request->all());
            // dd($purchases);
            return $this->showAfterAction($purchases,'create', 201);
        }catch(\Exception $e){
            // dd($e->getMessage());
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
     * Display the specified resource.
     *
     * @param  \App\Models\Purchases  $purchases
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try{
            $purchases = Purchases::find($id);
            $audits = $purchases->audits;
            if(request()->wantsJson()){
                return $this->showOne($purchases,$audits, 200);
            }
            return Inertia::render('Purchases/show', ['purchases' => $purchases, 'audits' => $audits]);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se obtenía el registro'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchases  $purchases
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{
            $reglas = [
                'person_id' => 'required',
                'purchase_desc' => 'required',
                'purchase_date' => 'required',
                'purchase_number' => 'required',
                'purchase_status' => 'required',
                'purchase_type' => 'required'
            ];
            $request->validate($reglas);
            $purchases = Purchases::find($id);
            $purchases->update($request->all());
            return $this->showAfterAction($purchases,'update', 200);
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
     * @param  \App\Models\Purchases  $purchases
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try{
            $purchases = Purchases::find($id);
            $purchases->delete();
            return response()->json(['message'=>'Eliminado con exito!']);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage(), 'message'=>'Ocurrió un error mientras se eliminaba el registro'],500);
        }
    }
}
