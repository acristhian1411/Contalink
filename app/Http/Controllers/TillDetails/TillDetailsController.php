<?php

namespace App\Http\Controllers\TillDetails;

use App\Models\TillDetails;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;

class TillDetailsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            $t = TillDetails::query()->first();
            $query = TillDetails::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            return $this->showAll($datos, 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
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
            $rules = [
                'till_id' => 'required|integer',
                'ref_id' => 'required|integer',
                'person_id' => 'required|integer',
                'td_desc' => 'required|string|max:255',
                'td_date' => 'required|date',
                'td_type' => 'required|boolean',
                'td_amount' => 'required|numeric',
            ];
            $request->validate($rules);
            $tillDetails = TillDetails::create($request->all());
            return $this->showAfterAction($tillDetails,'create', 201);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo guardar los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=> 'Los datos no son correrctos',
                'details'=> method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    /**
     * Store a newly created resource in storage from an array
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFromArray(Request $request){
        try{
            $tillDetails = collect($request->all())->map(function ($item) {
                return TillDetails::create($item);
            });
            return $this->showAll($tillDetails, 201);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo guardar los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=> 'Ls datos no son correrctos',
                'details'=> method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TillDetails  $tillDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try{
            $tillDetails = TillDetails::findOrFail($id);
            $audits = $tillDetails->audits;
            return $this->showOne($tillDetails,$audits, 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=> 'Ls datos no son correrctos',
                'details'=> method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    /**
     * Display a list of resource filtered by till_id and a range of dates
     * @param  \Illuminate\Http\Request  $request
     * @param int $till_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByTillIdAndDate(Request $request, $till_id){
        try{
            $tillDetails = TillDetails::where('till_id', $till_id)
                ->get();
            if(($request->has('from') && $request->from != '') && ($request->has('to') && $request->to != '')){
                $tillDetails = $tillDetails->whereBetween('td_date', [$request->from, $request->to]);
            }
            if($request->has('type') && $request->type != '' && $request->type != 'ambos'){
                $tillDetails = $tillDetails->where('td_type', $request->type == 'ingresos'? true : false );
            }
            return $this->showAll($tillDetails,'api','', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=> 'Los datos no son correrctos',
                'details'=> method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    public function closeReportDetailed(Request $request, $tillId){
        try{
            // query to obtain the latest cash opening 
            $latestOpeningDate = TillDetails::where('till_id', $tillId)
            ->where('td_desc', 'Apertura de Caja')
            ->whereNull('deleted_at')
            ->select('created_at')
            ->orderByDesc('created_at')
            ->first();
            
            // query to obtain incomes
            $incomes = TillDetails::select('pt.payment_type_desc', 'till_details.*')
                ->join('till_detail_proof_payments as tdpp', 'tdpp.till_detail_id', '=', 'till_details.id')
                ->join('proof_payments as pp', 'pp.id', '=', 'tdpp.proof_payment_id')
                ->join('payment_types as pt', 'pt.id', '=', 'pp.payment_type_id')
                ->join('sales', 'sales.id', '=', 'till_details.ref_id')
                ->join('persons as p', 'p.id', '=', 'sales.person_id')
                ->where('till_details.till_id', $tillId)
                ->where('till_details.td_type', true)
                ->where('till_details.created_at', '>=', $latestOpeningDate->created_at)
                ->where('till_details.td_desc','!=','Apertura de Caja')
                ->where('till_details.td_desc','!=','Cierre de Caja')
                ->whereNull('till_details.deleted_at')
                ->select('p.person_fname','p.person_lastname','till_details.*')
                ->get();
    
            // query to obtain expenses
            $expenses = TillDetails::select('pt.payment_type_desc', 'till_details.*')
            ->join('till_detail_proof_payments as tdpp', 'tdpp.till_detail_id', '=', 'till_details.id')
            ->join('proof_payments as pp', 'pp.id', '=', 'tdpp.proof_payment_id')
            ->join('payment_types as pt', 'pt.id', '=', 'pp.payment_type_id')
            ->join('purchases as pu', 'pu.id', '=', 'till_details.ref_id')
            ->join('persons as p', 'p.id', '=', 'pu.person_id')
            ->where('till_details.till_id', $tillId)
            ->where('till_details.td_type', false)
            ->where('till_details.created_at', '>=', $latestOpeningDate->created_at)
            ->where('till_details.created_at', '<=', DB::raw('now()'))
            ->where('till_details.td_desc','!=','Apertura de Caja')
            ->where('till_details.td_desc','!=','Cierre de Caja')
            ->select('p.person_fname','p.person_lastname','till_details.*')
            ->get();
            $tillCloseData = [
                'incomes' => $incomes,
                'expenses' => $expenses,
            ];
            return response()->json($tillCloseData,200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    public function closeReportResume(Request $request, $tillId){
        try{
            // query to obtain the latest cash opening 
            $latestOpeningDate = TillDetails::where('till_id', $tillId)
            ->where('td_desc', 'Apertura de Caja')
            ->whereNull('deleted_at')
            ->select('created_at')
            ->orderByDesc('created_at')
            ->first();
            
            // query to obtain incomes
            $incomes = TillDetails::select('pt.payment_type_desc', DB::raw('SUM(till_details.td_amount) as total_amount'))
                ->join('till_detail_proof_payments as tdpp', 'tdpp.till_detail_id', '=', 'till_details.id')
                ->join('proof_payments as pp', 'pp.id', '=', 'tdpp.proof_payment_id')
                ->join('payment_types as pt', 'pt.id', '=', 'pp.payment_type_id')
                ->where('till_details.till_id', $tillId)
                ->where('till_details.td_type', true)
                ->where('till_details.created_at', '>=', $latestOpeningDate->created_at)
                ->whereNull('till_details.deleted_at')
                ->groupBy('pt.id')
                ->get();
    
            // query to obtain expenses
            $expenses = TillDetails::select('pt.payment_type_desc', DB::raw('SUM(till_details.td_amount) as total_amount'))
            ->join('till_detail_proof_payments as tdpp', 'tdpp.till_detail_id', '=', 'till_details.id')
            ->join('proof_payments as pp', 'pp.id', '=', 'tdpp.proof_payment_id')
            ->join('payment_types as pt', 'pt.id', '=', 'pp.payment_type_id')
            ->where('till_details.till_id', $tillId)
            ->where('till_details.td_type', false)
            ->where('till_details.created_at', '>=', $latestOpeningDate->created_at)
            ->where('till_details.created_at', '<=', DB::raw('now()'))
            ->groupBy('pt.id')
            ->get();
            $tillCloseData = [
                'incomes' => $incomes,
                'expenses' => $expenses,
            ];
            return response()->json($tillCloseData,200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TillDetails  $tillDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{
            $rules = [
                'till_id' => 'required|integer',
                'ref_id' => 'required|integer',
            ];
            $request->validate($rules);
            $tillDetails = TillDetails::findOrFail($id);
            $tillDetails->fill($request->all());
            $tillDetails->save();
            return $this->showAfterAction($tillDetails,'create', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=> 'Ls datos no son correrctos',
                'details'=> method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TillDetails  $tillDetails
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy( $id)
    {
        try{
            $tillDetails = TillDetails::findOrFail($id);
            $tillDetails->delete();
            return response()->json('Eliminado con exito');
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}
