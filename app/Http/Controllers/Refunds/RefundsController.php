<?php

namespace App\Http\Controllers\Refunds;

use App\Models\Refunds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\RefundDetails\RefundDetailsController;
use App\Models\Products;
use App\Models\SalesDetails;

class RefundsController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $t = Refunds::query()->first();
            $query = Refunds::with([
                'sale.sales_details.product.measurementUnit',
                'refundDetails.product.measurementUnit'
            ]);
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            return $this->showAll($datos, 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => $e,
                'message' => 'No se pudo obtener los registros'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            DB::beginTransaction();
            $rules = [
                'sale_id' => 'required',
                'refund_date' => 'required',
                'refund_obs' => 'nullable',
                'refund_details' => 'required|array',
            ];
            $request->validate($rules);

            // Validate refund quantities against original sale quantities
            $this->validateRefundQuantities($request->sale_id, $request->refund_details);

            $refunds = Refunds::create($request->all());
            
            // Process refund details and update inventory
            $refundDetailsData = [];
            foreach($request->refund_details as $detail){
                $product = Products::with('measurementUnit')->findOrFail($detail['product_id']);
                $refundQuantity = $detail['sd_qty_devuelto'];

                // Validate quantity according to measurement unit
                if ($product->measurementUnit && !\App\Validators\QuantityValidator::validate($refundQuantity, $product->measurementUnit)) {
                    $errorMessage = \App\Validators\QuantityValidator::getErrorMessage($refundQuantity, $product->measurementUnit);
                    throw new \Exception("Producto {$product->product_name}: {$errorMessage}");
                }

                // Update product inventory (add back the refunded quantity)
                $product->increment('product_quantity', $refundQuantity);

                $refundDetailsData[] = [
                    'refund_id' => $refunds->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $refundQuantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert refund details
            RefundDetails::insert($refundDetailsData);

            DB::commit();
            return response()->json(['message'=>'Registro creado con exito','data'=>$refunds],201);
        }
        catch(\Illuminate\Validation\ValidationException $e){
            DB::rollBack();
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo crear el registro']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Refunds $refunds)
    {
        try{
            $refunds = Refunds::with([
                'sale.sales_details.product.measurementUnit',
                'refundDetails.product.measurementUnit'
            ])->findOrFail($refunds->id);
            
            $audits = $refunds->audits;
            return $this->showOne($refunds,$audits,200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Refunds $refunds)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try{
            $rules = [
                'sale_id' => 'required',
                'refund_date' => 'required',
                'refund_obs' => 'nullable',
                'refund_status' => 'required',
            ];
            $request->validate($rules);
            $refunds = Refunds::findOrFail($id);
            $refunds->update($request->all());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$refunds],200);
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
     */
    public function destroy(int $id)
    {
        try{
            DB::beginTransaction();
            
            $refunds = Refunds::with('refundDetails.product')->findOrFail($id);
            
            // Reverse inventory updates before deleting
            foreach ($refunds->refundDetails as $detail) {
                $product = $detail->product;
                // Subtract the refunded quantity back from inventory
                $product->decrement('product_quantity', $detail->quantity);
            }
            
            $refunds->delete();
            
            DB::commit();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }

    /**
     * Validate that refund quantities don't exceed original sale quantities
     */
    private function validateRefundQuantities($saleId, $refundDetails)
    {
        // Get original sale details
        $saleDetails = SalesDetails::where('sale_id', $saleId)
            ->with('product.measurementUnit')
            ->get()
            ->keyBy('product_id');

        // Get existing refund quantities for this sale
        $existingRefunds = DB::table('refunds')
            ->join('refund_details', 'refunds.id', '=', 'refund_details.refund_id')
            ->where('refunds.sale_id', $saleId)
            ->whereNull('refunds.deleted_at')
            ->whereNull('refund_details.deleted_at')
            ->select('refund_details.product_id', DB::raw('SUM(refund_details.quantity) as total_refunded'))
            ->groupBy('refund_details.product_id')
            ->pluck('total_refunded', 'product_id')
            ->toArray();

        foreach ($refundDetails as $detail) {
            $productId = $detail['product_id'];
            $refundQuantity = $detail['sd_qty_devuelto'];

            // Check if product exists in original sale
            if (!isset($saleDetails[$productId])) {
                throw new \Exception("El producto ID {$productId} no existe en la venta original");
            }

            $originalQuantity = $saleDetails[$productId]->sd_qty;
            $alreadyRefunded = $existingRefunds[$productId] ?? 0;
            $totalRefundQuantity = $alreadyRefunded + $refundQuantity;

            // Validate that total refund doesn't exceed original quantity
            if ($totalRefundQuantity > $originalQuantity) {
                $product = $saleDetails[$productId]->product;
                $unitName = $product->measurementUnit ? $product->measurementUnit->unit_name : 'Unidad';
                
                throw new \Exception(
                    "Cantidad de devolución excede la cantidad original para {$product->product_name}. " .
                    "Original: {$originalQuantity} {$unitName}, " .
                    "Ya devuelto: {$alreadyRefunded} {$unitName}, " .
                    "Intentando devolver: {$refundQuantity} {$unitName}"
                );
            }
        }
    }
}
