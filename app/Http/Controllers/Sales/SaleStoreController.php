<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\SalesDetails\SalesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Controllers\Products\ProductsController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreSalesRequest;
use App\Models\Products;
use App\Validators\QuantityValidator;
use App\Utils\QuantityValidationException;
class SaleStoreController extends ApiController
{
    /**
     * Use the store method of SalesController and SalesDetailsController to create a new sale with each details. Also it will use DBTransaction
     * 
     * @param StoreSalesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSalesRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Validar cantidades según unidades de medida de cada producto
            foreach ($request->sale_details as $detail) {
                $product = Products::with('measurementUnit')->find($detail['product_id']);
                
                if (!$product) {
                    throw new QuantityValidationException("Producto con ID {$detail['product_id']} no encontrado");
                }
                
                // Validar cantidad según la unidad de medida del producto
                if ($product->measurementUnit && !QuantityValidator::validate($detail['sd_qty'], $product->measurementUnit)) {
                    $errorMessage = QuantityValidator::getErrorMessage($detail['sd_qty'], $product->measurementUnit);
                    throw new QuantityValidationException("Producto {$product->product_name}: {$errorMessage}");
                }
                
                // Validar que hay suficiente stock
                if ($product->product_quantity < $detail['sd_qty']) {
                    $unitName = $product->measurementUnit ? $product->measurementUnit->unit_name : 'Unidad';
                    throw new QuantityValidationException("Stock insuficiente para {$product->product_name}. Disponible: {$product->product_quantity} {$unitName}, Solicitado: {$detail['sd_qty']} {$unitName}");
                }
            }
            
            $tills = new TillsController;
            $till_data = new Request([
                'fromController' => true
            ]);
            $sale_amount = collect($request->sale_details)
                ->map(function ($detail) {
                    return $detail['sd_amount'] * $detail['sd_qty'];
                })
                ->sum();
            $product_data = new Request([
                'fromController' => true,
                'controller' => 'sales',
                'details' => collect($request->sale_details)->map(function ($item) {
                    return [
                        'id' => $item['product_id'],
                        'product_cost_price' => $item['sd_amount'],
                        'product_quantity' => $item['sd_qty']
                    ];
                })->toArray()
            ]);
            // Log::alert($update);
            $Sales = new SalesController;
            $sale_data = new Request([
                'person_id' => $request->person_id,
                'sale_date' => $request->sale_date,
                'sale_number' => $request->sale_number,
            ]);
            $ret = $Sales->store($sale_data);
            Log::alert($ret);
            $sale_id = $ret->original['data']['id'];
            $sale_details = new SalesDetailsController;
            $details = collect($request->sale_details)->map(function ($item) use ($sale_id, $request) {
                return array_merge($item, [
                    'sale_id' => $sale_id,
                    'sd_desc' => "Venta {$request->sale_number}",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            })->toArray();
            $sale_details_data = new Request([
                'details' => $details
            ]);
            $det = $sale_details->storeMany($sale_details_data);
            Log::alert($det);
            foreach ($request->proofPayments as $payment) {
                $till_details = new TillDetailsController;
                $till_details_data = new Request([
                    'till_id' => $request->till_id,
                    'account_p_id' => 1,
                    'ref_id' => $sale_id,
                    'person_id' => $request->user_id,
                    'td_desc' => "Venta {$request->sale_number}",
                    'td_date' => $request->sale_date,
                    'td_type' => true,
                    'td_amount' => $payment['amount'],
                ]);
                $till_detail_stored = $till_details->store($till_details_data);
                Log::error($till_detail_stored);
                $till_detail_proof_payments = new TillDetailProofPaymentsController;
                $till_detail_proof_payments_data = new Request([
                    'till_detail_id' => $till_detail_stored->original['data']['id'],
                    'proof_payment_id' => empty($request->proofPayments) ? 1 : $payment['value'],
                    'td_pr_desc' => empty($request->proofPayments) ? 1 : ($payment['value'] === 1 ? 'Efectivo' : $payment['td_pr_desc']),
                ]);
                $till_detail_proof_payments_stored = $till_detail_proof_payments->store($till_detail_proof_payments_data);
                if ($till_detail_proof_payments_stored) {
                    continue;
                } else {
                    DB::rollBack();
                    throw new \Exception('No se pudo guardar el tipo de pago de un detalle de caja.');
                }
            }
            DB::commit();
            $something = [];
            return $this->showAfterAction($something, 'create', 201);
        } catch(QuantityValidationException $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'error'=>'',
                'errors' => $e->getErrors(),
                'message' => 'Problemas con error',
            ], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error($e->errors());
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error mientras se creaba el registro'], 500);
        }

    }
}