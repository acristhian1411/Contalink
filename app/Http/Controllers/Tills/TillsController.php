<?php

namespace App\Http\Controllers\Tills;

use App\Models\Tills;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class TillsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = Tills::query()->first();
            $query = Tills::query();
            $query = $this->filterData($query, $t);
            $datos = $query->join('till_types','tills.t_type_id','=','till_types.id')
            ->select('tills.*','till_types.till_type_desc')
            ->get();
            return $this->showAll($datos, 'api','',200);
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
            $rules = [
                'till_name' => 'required|string|max:255',
                't_type_id' => 'required|integer',
            ];
            $request->validate($rules);
            $tills = Tills::create($request->all());
            return $this->showAfterAction($tills,'create',201);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'Ocurrió un error al procesar los datos.'],500);            
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
     * @param  \App\Models\Tills  $tills
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $till = Tills::findOrFail($id);
            $audits = $till->audits;
            if(request()->wantsJson()){
                return $this->showOne($till, $audits, 200);
            }
            return Inertia::render('Tills/show', ['tills' => $till, 'audits' => $audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);            
        }
    }

    /**
     * Show the list of tills opened by the user
     * @param \Illuminate\Http\Request $req
     * @param int $id
     * @return mixed
     */
    public function showTillsByUser(Request $req, int $id){
        try{
            $tills = Tills::join('till_details','tills.id','=','till_details.till_id')
            ->where('person_id', $id)
            ->where('till_status', true)
            ->where('td_desc', 'Apertura de Caja')
            ->select('tills.*')
            ->orderBy('tills.id')
            ->orderByDesc('till_details.created_at')
            ->distinct('tills.id')
            ->get();
            return $this->showAll($tills, 'api','',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * * Show the till amount of a specific till if the till is open
     * @param \Illuminate\Http\Request $req
     * @param int $id
     * @return mixed
     */
    public function showTillAmount(Request $req, int $id){
        try{
            $till = Tills::findOrFail($id);
            if($till->till_status == true){
                $amount = DB::select("
                    WITH apertura AS (
                        SELECT td.created_at
                        FROM till_details td
                        WHERE td.deleted_at IS NULL
                        AND td.td_desc = 'Apertura de Caja'
                        AND td.till_id = {$id}
                        ORDER BY td.created_at DESC
                        LIMIT 1
                    ),
                    egresos AS (
                        SELECT COALESCE(SUM(td.td_amount), 0) AS suma
                        FROM till_details td
                        left JOIN till_detail_proof_payments dpp ON dpp.till_detail_id = td.id
                        left JOIN proof_payments pp ON pp.id = dpp.proof_payment_id
                        CROSS JOIN apertura
                        WHERE td.td_type = false
                        AND td.created_at >= apertura.created_at
                        AND td.till_id = {$id}
                        AND td.deleted_at IS NULL
                    ),
                    ingresos AS (
                        SELECT COALESCE(SUM(td.td_amount), 0) AS suma
                        FROM till_details td
                        left JOIN till_detail_proof_payments dpp ON dpp.till_detail_id = td.id
                        left JOIN proof_payments pp ON pp.id = dpp.proof_payment_id
                        CROSS JOIN apertura
                        WHERE td.created_at >= apertura.created_at
                        AND td.till_id = {$id}
                        and td.td_type = true
                        AND td.deleted_at IS NULL
                        AND dpp.deleted_at IS NULL
                    )
                    SELECT CAST((ingresos.suma - egresos.suma) AS float) AS till_amount
                    FROM ingresos, egresos
                ");
                if($req->fromController == true){
                    return $amount[0]->till_amount;
                }
                return response()->json(['till_amount'=>$amount[0]->till_amount]);
            }else{
                if($req->fromController == true){
                    return false;
                }
                return response()->json(['error'=>'till closed','message'=>'La caja está cerrada'],500);
            }
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * * Show a list of tills filtered by till_type
     * @param \Illuminate\Http\Request $req
     * @param int $id
     * @return mixed
     */
    public function getByTypeId($id){
        try{
            $tills = Tills::where('t_type_id', $id)->get();
            return $this->showAll($tills, 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tills  $tills
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $rules = [
                'till_name' => 'required|string|max:255',
                't_type_id' => 'required|integer',
            ];
            $request->validate($rules);
            $tills = Tills::findOrFail($id);
            $tills->update($request->all());
            return $this->showAfterAction($tills,'update',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar los datos'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tills  $tills
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $tills = Tills::findOrFail($id);
            $tills->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}
