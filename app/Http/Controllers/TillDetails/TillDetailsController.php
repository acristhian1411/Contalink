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
        return response()->json($this->historyData($request, (int) $till_id));
    }

    /**
     * Paginated till details with date/type filters and totals over the full filtered set.
     */
    public function historyData(Request $request, int $tillId): array
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'type' => 'nullable|in:ingresos,egresos,ambos',
            'sort_by' => 'nullable|in:id,td_date,td_amount,created_at',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:5,10,20,50,100',
        ]);

        $from = $filters['from'] ?? now()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $type = $filters['type'] ?? 'ambos';
        $sortBy = $filters['sort_by'] ?? 'id';
        $order = $filters['order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $query = TillDetails::where('till_id', $tillId)
            ->when($from && $to, fn ($q) => $q->whereBetween('td_date', [$from, $to]))
            ->when($type !== 'ambos', fn ($q) => $q->where('td_type', $type === 'ingresos'));

        $totals = [
            'incomes' => (clone $query)->where('td_type', true)->sum('td_amount'),
            'expenses' => (clone $query)->where('td_type', false)->sum('td_amount'),
        ];

        $items = $query->orderBy($sortBy, $order)->paginate($perPage)->withQueryString();

        return [
            'items' => $items,
            'totals' => $totals,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'type' => $type,
                'sort_by' => $sortBy,
                'order' => $order,
                'per_page' => $perPage,
            ],
        ];
    }

    public function closeReportDetailed(Request $request, $tillId){
        return response()->json($this->closeReportDetailedData((int) $tillId));
    }

    public function closeReportDetailedData(int $tillId): array
    {
        // query to obtain the latest cash opening
        $latestOpeningDate = TillDetails::where('till_id', $tillId)
        ->where('td_desc', 'Apertura de Caja')
        ->whereNull('deleted_at')
        ->select('created_at')
        ->orderByDesc('created_at')
        ->first();

        if (!$latestOpeningDate) {
            return ['incomes' => [], 'expenses' => []];
        }

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

        return [
            'incomes' => $incomes,
            'expenses' => $expenses,
        ];
    }

    public function closeReportResume(Request $request, $tillId){
        return response()->json($this->closeReportResumeData((int) $tillId));
    }

    public function closeReportResumeData(int $tillId): array
    {
        // query to obtain the latest cash opening
        $latestOpeningDate = TillDetails::where('till_id', $tillId)
        ->where('td_desc', 'Apertura de Caja')
        ->whereNull('deleted_at')
        ->select('created_at')
        ->orderByDesc('created_at')
        ->first();

        if (!$latestOpeningDate) {
            return ['incomes' => [], 'expenses' => []];
        }

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

        return [
            'incomes' => $incomes,
            'expenses' => $expenses,
        ];
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
