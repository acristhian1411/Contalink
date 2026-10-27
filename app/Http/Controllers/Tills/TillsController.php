<?php

namespace App\Http\Controllers\Tills;

use App\Models\Tills;
use App\Models\TillType;
use App\Models\TillsTransfer;
use App\Http\Controllers\TillDetails\TillDetailsController;
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
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $t = Tills::query()->first();
            $query = Tills::query();
            $query = $this->filterData($query, $t);
            $datos = $query->join('till_types','tills.t_type_id','=','till_types.id')
            ->select('tills.*','till_types.till_type_desc')
            ->get();
            return $this->showAll($datos, 'api','',200);
        }

        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:id,till_name,t_type_id,till_status',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:5,10,20,50,100',
        ]);

        $items = Tills::query()
            ->join('till_types', 'tills.t_type_id', '=', 'till_types.id')
            ->select('tills.*', 'till_types.till_type_desc')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where('tills.till_name', 'ilike', "%{$v}%");
            })
            ->orderBy('tills.'.($filters['sort_by'] ?? 'id'), $filters['order'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Tills/index', [
            'items' => $items,
            'filters' => $request->only(['search', 'sort_by', 'order', 'per_page']),
            'tillTypes' => TillType::orderBy('till_type_desc')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'till_name' => 'required|string|max:255',
            'till_account_number' => 'nullable|string|max:50',
            't_type_id' => 'required|integer|exists:till_types,id',
        ]);

        $till = Tills::create($validated);

        if ($request->wantsJson()) {
            return $this->showAfterAction($till, 'create', 201);
        }

        return back()->with('success', 'Caja creada con éxito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tills  $tills
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $till = Tills::findOrFail($id);

        if ($request->wantsJson()) {
            return $this->showOne($till, $till->audits, 200);
        }

        $details = new TillDetailsController();

        return Inertia::render('Tills/show', [
            'till' => $till,
            'audits' => $till->audits,
            'tillAmount' => $till->till_status
                ? $this->showTillAmount(new Request(['fromController' => true]), (int) $id)
                : null,
            'closeReport' => $till->till_status
                ? $details->closeReportResumeData((int) $id)
                : null,
            'transfers' => $this->transfersData((int) $id),
            'openTills' => Tills::where('till_status', true)
                ->where('id', '!=', $id)
                ->orderBy('till_name')
                ->get(),
            'history' => $details->historyData($request, (int) $id),
        ]);
    }

    /**
     * Transfers where the given till is origin or destiny.
     */
    private function transfersData(int $tillId)
    {
        return TillsTransfer::query()
            ->leftJoin('tills as o', 'o.id', '=', 'tills_transfers.origin_id')
            ->leftJoin('tills as d', 'd.id', '=', 'tills_transfers.destiny_id')
            ->where(function ($q) use ($tillId) {
                $q->where('tills_transfers.origin_id', $tillId)
                    ->orWhere('tills_transfers.destiny_id', $tillId);
            })
            ->select('tills_transfers.*', 'o.till_name as origin_name', 'd.till_name as destiny_name')
            ->orderByDesc('tills_transfers.t_transfer_date')
            ->get();
    }

    /**
     * Close report page (detailed) rendered as Inertia.
     */
    public function closeDetailed($id)
    {
        $till = Tills::findOrFail($id);

        return Inertia::render('Tills/tillsCloseReportDetailed', [
            'till' => $till,
            'closeReport' => (new TillDetailsController())->closeReportDetailedData((int) $id),
        ]);
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
        $validated = $request->validate([
            'till_name' => 'required|string|max:255',
            'till_account_number' => 'nullable|string|max:50',
            't_type_id' => 'required|integer|exists:till_types,id',
        ]);

        $till = Tills::findOrFail($id);
        $till->update($validated);

        if ($request->wantsJson()) {
            return $this->showAfterAction($till, 'update', 200);
        }

        return back()->with('success', 'Caja actualizada con éxito');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tills  $tills
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $till = Tills::findOrFail($id);
        $till->delete();

        if ($request->wantsJson()) {
            return response()->json(['message'=>'Eliminado con exito']);
        }

        return back()->with('success', 'Caja eliminada con éxito');
    }
}
