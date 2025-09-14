<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Purchases\PurchasesController;
use App\Http\Controllers\PurchasesDetails\PurchasesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Controllers\Products\ProductsController;
use Illuminate\Support\Collection;
class PurchaseStoreController extends ApiController {
    /**
     * Usa el controlador PurchasesController y PurchasesDetailsController para crear una nueva compra con cada detalle, ademas usa TillDetailsController y TillDetailProofPaymentsController para registrar el movimiento en la caja. También usara transacción de base de datos para asegurar la integridad de los datos.
     * 
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     * 
     * @see PurchasesController
     * @see PurchasesDetailsController
     * @see TillDetailsController
     * @see TillDetailProofPaymentsController
     * @see ProductsController
     * 
     * @param Request $request
     * 
     *   - user_id: int
     *   - person_id: int | object
     *   - purchase_date: date
     *   - purchase_number: string
     *   - purchase_details: array
     *   - till_id: int
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request){
        try{
            DB::beginTransaction();
            $reglas = [
                'user_id' => 'required|integer',
                'person_id' => ['required', function ($attribute, $value, $fail) {
                    if (!is_int($value) && !is_object($value)) {
                        $fail($attribute.' debe ser un entero o un objeto.');
                    }
                }],
                'purchase_date' => 'required|date',
                'purchase_number' => 'required|string|unique:purchases',
                'purchase_details' => 'required|array'
            ];
            $request->validate($reglas);
            $tills = new TillsController;
            $till_data = new Request([
                'fromController'=> true
            ]);
            $till_amount = $tills->showTillAmount($till_data,$request->till_id);
            $purchase_amount = collect($request->purchase_details)
                ->map(function ($detail) {
                    return $detail['pd_amount'] * $detail['pd_qty'];
                })
                ->sum();
            if($till_amount < $purchase_amount){
                return response()->json(['error'=>'No hay suficiente efectivo en la caja para realizar la compra','message'=>'No hay suficiente efectivo en la caja para realizar la compra'],400);
            }
            
            $product_data = new Request([
                'fromController' => true,
                'controller'=>'purchase',
                'details' => collect($request->purchase_details)->map(function ($item) {
                    return [
                        'id' => $item['product_id'],
                        'product_cost_price' => $item['pd_amount'],
                        'product_quantity' => $item['pd_qty']
                    ];
                })->toArray()
            ]);
            $products = new ProductsController;
            $update = $products->updatePriceAndQty($product_data);
            $purchases = new PurchasesController;
            $purchase_data = new Request([
                'person_id' => is_object($request->person_id) ? $request->person_id->value : $request->person_id,
                'purchase_date' => $request->purchase_date,
                'purchase_number' => $request->purchase_number,
            ]);
            $ret = $purchases->store($purchase_data);
            $purchase_id = $ret->original['data']['id'];
            $purchase_details = new PurchasesDetailsController;
            $details = collect($request->purchase_details)->map(function ($item) use ($purchase_id) {
                return array_merge($item, [
                    'purchase_id' => $purchase_id,
                    'created_at' => now(),
                    'updated_at' => now()
            ]);
            })->toArray();
            $purchase_details_data = new Request([
                'details' => $details
            ]);
            $det = $purchase_details->storeMany($purchase_details_data);
            $till_details = new TillDetailsController;
            $till_details_data = new Request([
                'till_id' => $request->till_id,
                'account_p_id'=> 1,
                'ref_id' => $purchase_id,
                'person_id' => $request->user_id,
                'td_desc' => "Compra {$request->purchase_number}",
                'td_date' => $request->purchase_date,
                'td_type' => false,
                'td_amount' => $purchase_amount
            ]);
            $till_detail_stored = $till_details->store($till_details_data);
            $till_detail_proof_payments = new TillDetailProofPaymentsController;
            $till_detail_proof_payments_data = new Request([
                'till_detail_id' => $till_detail_stored->original['data']['id'],
                'proof_payment_id' => empty($request->proofPayments) ? 1 : $request->proofPayments[0]['value'],
                'td_pr_desc' => empty($request->proofPayments) ? 1 : ($request->proofPayments[0]['value'] == 1 ? 'Efectivo' : $request->proofPayments[0]['td_pr_desc']),
            ]);
            $till_detail_proof_payments_stored = $till_detail_proof_payments->store($till_detail_proof_payments_data);
            DB::commit();
            $something = [];
            return $this->showAfterAction($something,'create', 201);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['error' => $e, 'message'=>'Ocurrió un error mientras se creaba el registro'],500);
        }catch(\Illuminate\Validation\ValidationException $e){
            DB::rollBack();
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ]);
        }
        
    }
}