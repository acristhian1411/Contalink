<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\SalesDetails\SalesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\TillDetails;
use App\Models\TillDetailProofPayments;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class SaleDeleteController extends ApiController
{
    /**
     * Delete a sale transaction and reverse all its effects on stock and till movements
     * 
     * @param Request $request
     * @param int $saleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $saleId)
    {
        // Log the start of deletion process with user context
        $userId = auth()->id() ?? 'unknown';
        Log::info("Sale deletion initiated", [
            'sale_id' => $saleId,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            // Validate input parameters
            $this->validateInputParameters($saleId);
            
            DB::beginTransaction();
            Log::info("Database transaction started for sale deletion", ['sale_id' => $saleId]);
            
            // Comprehensive validation before proceeding
            $saleData = $this->validateSaleForDeletion($saleId);
            
            Log::info("Starting deletion process for sale", [
                'sale_id' => $saleId,
                'sale_number' => $saleData['sale_number'] ?? 'unknown',
                'sale_date' => $saleData['sale_date'] ?? 'unknown',
                'person_id' => $saleData['person_id'] ?? 'unknown'
            ]);
            
            // Step 1: Reverse product stock changes
            $this->reverseProductStock($saleId);
            
            // Step 2: Delete till movements (proof payments and till details)
            $this->deleteTillMovements($saleId);
            
            // Step 3: Delete sales data (details first, then main sale record)
            $this->deleteSalesData($saleId);
            
            DB::commit();
            
            Log::info("Sale deletion completed successfully", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'execution_time' => microtime(true) - LARAVEL_START
            ]);
            
            return response()->json([
                'message' => 'Venta eliminada con éxito',
                'data' => ['sale_id' => $saleId]
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Sale not found for deletion", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Venta no encontrada',
                'message' => 'No se pudo encontrar la venta especificada'
            ], 404);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation error during sale deletion", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ], 422);
            
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            Log::error("Business logic validation error for sale deletion", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se puede eliminar la venta'
            ], 400);
            
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Database constraint violation during sale deletion", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'error_code' => $e->errorInfo[1] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error de integridad de datos',
                'message' => 'No se pudo eliminar la venta debido a restricciones de base de datos'
            ], 409);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Unexpected error during sale deletion", [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo eliminar la venta. Contacte al administrador del sistema.'
            ], 500);
        }
    }

    /**
     * Validate input parameters for the deletion request
     * 
     * @param mixed $saleId
     * @throws \InvalidArgumentException
     */
    private function validateInputParameters($saleId)
    {
        // Validate sale ID is provided and is numeric
        if (empty($saleId) || !is_numeric($saleId) || $saleId <= 0) {
            Log::warning("Invalid sale ID provided for deletion", ['sale_id' => $saleId]);
            throw new \InvalidArgumentException('ID de venta inválido');
        }
        
        Log::debug("Input parameters validated successfully", ['sale_id' => $saleId]);
    }

    /**
     * Validate that a sale exists and can be deleted
     * 
     * @param int $saleId
     * @return array Sale data for logging purposes
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    private function validateSaleForDeletion($saleId)
    {
        Log::info("Starting comprehensive sale validation", ['sale_id' => $saleId]);
        
        // Check if sale exists (throws ModelNotFoundException if not found)
        $sale = Sales::findOrFail($saleId);
        
        Log::debug("Sale found in database", [
            'sale_id' => $saleId,
            'sale_number' => $sale->sale_number,
            'sale_date' => $sale->sale_date,
            'person_id' => $sale->person_id,
            'sale_status' => $sale->sale_status
        ]);
        
        // Check if sale is already soft deleted
        if ($sale->trashed()) {
            Log::warning("Attempted to delete already deleted sale", [
                'sale_id' => $saleId,
                'deleted_at' => $sale->deleted_at
            ]);
            throw new \InvalidArgumentException('La venta ya ha sido eliminada');
        }
        
        // Additional business logic validations
        $this->validateSaleBusinessRules($sale);
        
        // Validate that sale has details (a sale without details shouldn't exist but let's be safe)
        $salesDetailsCount = SalesDetails::where('sale_id', $saleId)->whereNull('deleted_at')->count();
        if ($salesDetailsCount === 0) {
            Log::error("Sale has no details associated", [
                'sale_id' => $saleId,
                'sale_number' => $sale->sale_number
            ]);
            throw new \InvalidArgumentException('La venta no tiene detalles asociados');
        }
        
        // Validate that sale has till movements (a sale should have payment records)
        $tillDetailsCount = TillDetails::where('ref_id', $saleId)
        ->whereRaw("td_desc ILIKE ?", ['%venta%'])
        ->whereNull('deleted_at')->count();
        if ($tillDetailsCount === 0) {
            Log::error("Sale has no till movements associated", [
                'sale_id' => $saleId,
                'sale_number' => $sale->sale_number
            ]);
            throw new \InvalidArgumentException('La venta no tiene movimientos de caja asociados');
        }
        
        // Validate data integrity constraints
        $this->validateDataIntegrityConstraints($saleId, $salesDetailsCount, $tillDetailsCount);
        
        Log::info("Sale validation passed successfully", [
            'sale_id' => $saleId,
            'details_count' => $salesDetailsCount,
            'till_details_count' => $tillDetailsCount
        ]);
        
        return [
            'sale_number' => $sale->sale_number,
            'sale_date' => $sale->sale_date,
            'person_id' => $sale->person_id,
            'sale_status' => $sale->sale_status
        ];
    }

    /**
     * Validate business rules for sale deletion
     * 
     * @param Sales $sale
     * @throws \InvalidArgumentException
     */
    private function validateSaleBusinessRules($sale)
    {
        // Check if sale is in a valid status for deletion
        if (isset($sale->sale_status) && in_array($sale->sale_status, ['cancelled', 'refunded'])) {
            Log::warning("Attempted to delete sale with invalid status", [
                'sale_id' => $sale->id,
                'sale_status' => $sale->sale_status
            ]);
            throw new \InvalidArgumentException('No se puede eliminar una venta con estado: ' . $sale->sale_status);
        }
        
        // Check if sale is too old (optional business rule - can be configured)
        $saleDate = \Carbon\Carbon::parse($sale->sale_date);
        $daysSinceSale = $saleDate->diffInDays(now());
        
        if ($daysSinceSale > 30) { // Configurable threshold
            Log::warning("Attempted to delete old sale", [
                'sale_id' => $sale->id,
                'sale_date' => $sale->sale_date,
                'days_since_sale' => $daysSinceSale
            ]);
            // This could be a warning instead of an error depending on business requirements
            Log::info("Warning: Deleting sale older than 30 days", [
                'sale_id' => $sale->id,
                'days_since_sale' => $daysSinceSale
            ]);
        }
        
        Log::debug("Business rules validation passed", ['sale_id' => $sale->id]);
    }

    /**
     * Validate data integrity constraints before deletion
     * 
     * @param int $saleId
     * @param int $salesDetailsCount
     * @param int $tillDetailsCount
     * @throws \InvalidArgumentException
     */
    private function validateDataIntegrityConstraints($saleId, $salesDetailsCount, $tillDetailsCount)
    {
        // Validate that all products in sale details still exist and are not deleted
        $invalidProducts = SalesDetails::where('sale_id', $saleId)
            ->whereNull('deleted_at')
            ->whereDoesntHave('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
            
        if ($invalidProducts > 0) {
            Log::error("Sale contains references to deleted products", [
                'sale_id' => $saleId,
                'invalid_products_count' => $invalidProducts
            ]);
            throw new \InvalidArgumentException('La venta contiene productos que ya no existen en el sistema');
        }
        
        // Validate that till details reference valid tills
        $invalidTills = TillDetails::where('ref_id', $saleId)
            ->whereRaw("td_desc ILIKE ?", ['%venta%'])
            ->whereNull('deleted_at')
            ->whereDoesntHave('till', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
            
        if ($invalidTills > 0) {
            Log::error("Sale contains references to deleted tills", [
                'sale_id' => $saleId,
                'invalid_tills_count' => $invalidTills
            ]);
            throw new \InvalidArgumentException('La venta contiene referencias a cajas que ya no existen');
        }
        
        // Check for orphaned records that might indicate data corruption
        $orphanedProofPayments = TillDetailProofPayments::whereHas('tillDetail', function($query) use ($saleId) {
            $query->where('ref_id', $saleId)->whereNull('deleted_at');
        })->whereNull('deleted_at')->count();
        
        if ($orphanedProofPayments === 0 && $tillDetailsCount > 0) {
            Log::warning("Till details exist but no proof payments found", [
                'sale_id' => $saleId,
                'till_details_count' => $tillDetailsCount
            ]);
            // This might be valid for cash-only sales, so just log as warning
        }
        
        Log::debug("Data integrity constraints validated successfully", [
            'sale_id' => $saleId,
            'invalid_products' => $invalidProducts,
            'invalid_tills' => $invalidTills,
            'proof_payments_count' => $orphanedProofPayments
        ]);
    }

    /**
     * Reverse product stock changes by adding back quantities that were sold
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function reverseProductStock($saleId)
    {
        Log::info("Starting stock reversal process", ['sale_id' => $saleId]);
        
        try {
            // Retrieve all sale details with product information
            $saleDetails = $this->getSaleDetailsForStockReversal($saleId);
            
            if (empty($saleDetails)) {
                Log::error("No sale details found for stock reversal", ['sale_id' => $saleId]);
                throw new \Exception("No se encontraron detalles de venta para revertir el stock");
            }
            
            Log::info("Retrieved sale details for stock reversal", [
                'sale_id' => $saleId,
                'details_count' => count($saleDetails)
            ]);
            
            // Validate products exist and can have stock reversed
            $this->validateProductsForStockReversal($saleDetails);
            
            // Prepare data for ProductsController updatePriceAndQty method
            $stockReversalData = $this->prepareStockReversalData($saleDetails);
            
            // Log the stock reversal operation details
            Log::info("Prepared stock reversal data", [
                'sale_id' => $saleId,
                'products_to_reverse' => array_map(function($item) {
                    return [
                        'product_id' => $item['id'],
                        'quantity_to_add' => $item['product_quantity'],
                        'cost_price' => $item['product_cost_price']
                    ];
                }, $stockReversalData)
            ]);
            
            // Create request object for ProductsController
            $request = new Request([
                'controller' => 'sales_reversal',
                'details' => $stockReversalData,
                'fromController' => true
            ]);
            
            // Call ProductsController to reverse stock
            $productsController = new ProductsController();
            $result = $productsController->updatePriceAndQty($request);
            
            if ($result !== true) {
                Log::error("ProductsController returned failure for stock reversal", [
                    'sale_id' => $saleId,
                    'result' => $result
                ]);
                throw new \Exception("Error al revertir el stock de los productos");
            }
            
            // Verify stock reversal was successful by checking final quantities
            $this->verifyStockReversalSuccess($saleDetails);
            
            Log::info("Stock reversal completed successfully", [
                'sale_id' => $saleId,
                'products_processed' => count($stockReversalData)
            ]);
            
        } catch (QueryException $e) {
            Log::error("Database error during stock reversal", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al revertir el stock: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("Stock reversal failed", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al revertir el stock: {$e->getMessage()}");
        }
    }

    /**
     * Retrieve sale details with product information for stock reversal
     * 
     * @param int $saleId
     * @return array
     */
    private function getSaleDetailsForStockReversal($saleId)
    {
        return SalesDetails::where('sale_id', $saleId)
            ->with(['product' => function($query) {
                $query->select('id', 'product_cost_price');
            }])
            ->select('id', 'product_id', 'sd_qty', 'sd_amount')
            ->get()
            ->toArray();
    }

    /**
     * Validate that products can have their stock reversed
     * 
     * @param array $saleDetails
     * @throws \Exception
     */
    private function validateProductsForStockReversal($saleDetails)
    {
        foreach ($saleDetails as $detail) {
            // Check if product still exists
            if (!isset($detail['product']) || empty($detail['product'])) {
                Log::error("Product not found for stock reversal", [
                    'product_id' => $detail['product_id'],
                    'sale_detail_id' => $detail['id']
                ]);
                throw new \Exception("Producto ID {$detail['product_id']} no encontrado para revertir stock");
            }
            
            // Validate quantity is positive
            if ($detail['sd_qty'] <= 0) {
                Log::error("Invalid quantity for stock reversal", [
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['sd_qty']
                ]);
                throw new \Exception("Cantidad inválida para revertir stock del producto ID {$detail['product_id']}");
            }
            
            // Check if product is not deleted
            $product = Products::find($detail['product_id']);
            if (!$product || $product->trashed()) {
                Log::error("Product is deleted, cannot reverse stock", [
                    'product_id' => $detail['product_id']
                ]);
                throw new \Exception("No se puede revertir stock de producto eliminado ID {$detail['product_id']}");
            }
        }
        
        Log::debug("Products validation passed for stock reversal", [
            'products_count' => count($saleDetails)
        ]);
    }

    /**
     * Prepare stock reversal data in the format expected by ProductsController
     * 
     * @param array $saleDetails
     * @return array
     */
    private function prepareStockReversalData($saleDetails)
    {
        $stockReversalData = [];
        
        foreach ($saleDetails as $detail) {
            $stockReversalData[] = [
                'id' => $detail['product_id'],
                'product_quantity' => $detail['sd_qty'], // Quantity to add back
                'product_cost_price' => $detail['product']['product_cost_price'] // Maintain original cost price
            ];
        }
        
        Log::debug("Prepared stock reversal data", [
            'products_count' => count($stockReversalData),
            'total_quantity_to_reverse' => array_sum(array_column($stockReversalData, 'product_quantity'))
        ]);
        
        return $stockReversalData;
    }

    /**
     * Verify that stock reversal was successful by checking product quantities
     * 
     * @param array $saleDetails
     * @throws \Exception
     */
    private function verifyStockReversalSuccess($saleDetails)
    {
        $verificationErrors = [];
        
        foreach ($saleDetails as $detail) {
            $product = Products::find($detail['product_id']);
            
            if (!$product) {
                $verificationErrors[] = "Producto ID {$detail['product_id']} no encontrado durante verificación";
                continue;
            }
            
            // Log the current stock level for audit purposes
            Log::debug("Stock level after reversal", [
                'product_id' => $detail['product_id'],
                'current_quantity' => $product->product_quantity,
                'reversed_quantity' => $detail['sd_qty']
            ]);
            
            // Additional verification could be added here if needed
            // For example, checking if the stock level is reasonable
            if ($product->product_quantity < 0) {
                Log::warning("Product has negative stock after reversal", [
                    'product_id' => $detail['product_id'],
                    'current_quantity' => $product->product_quantity
                ]);
            }
        }
        
        if (!empty($verificationErrors)) {
            Log::error("Stock reversal verification failed", [
                'errors' => $verificationErrors
            ]);
            throw new \Exception("Errores en verificación de reversión de stock: " . implode(', ', $verificationErrors));
        }
        
        Log::debug("Stock reversal verification completed successfully", [
            'products_verified' => count($saleDetails)
        ]);
    }

    /**
     * Delete all till movements associated with a sale (TillDetails and TillDetailProofPayments)
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function deleteTillMovements($saleId)
    {
        Log::info("Starting till movements deletion process", ['sale_id' => $saleId]);
        
        try {
            // Find all TillDetails records associated with the sale
            $tillDetails = $this->getTillDetailsForSale($saleId);
            if (empty($tillDetails)) {
                Log::error("No till movements found for sale", ['sale_id' => $saleId]);
                throw new \Exception("No se encontraron movimientos de caja para la venta");
            }
            
            Log::info("Retrieved till details for deletion", [
                'sale_id' => $saleId,
                'till_details_count' => count($tillDetails),
                'till_details' => array_map(function($td) {
                    return [
                        'id' => $td['id'],
                        'till_id' => $td['till_id'],
                        'amount' => $td['td_amount'],
                        'description' => $td['td_desc']
                    ];
                }, $tillDetails)
            ]);
            
            // Validate till details before deletion
            $this->validateTillDetailsForDeletion($tillDetails);
            
            // Delete TillDetailProofPayments for each TillDetail
            $this->deleteTillDetailProofPayments($tillDetails);
            
            // Delete TillDetails records
            $this->deleteTillDetailsRecords($tillDetails);
            
            // Verify deletion was successful
            $this->verifyTillMovementsDeletion($saleId);
            
            Log::info("Till movements deletion completed successfully", [
                'sale_id' => $saleId,
                'deleted_till_details' => count($tillDetails)
            ]);
            
        } catch (QueryException $e) {
            Log::error("Database error during till movements deletion", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al eliminar movimientos de caja: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("Till movements deletion failed", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los movimientos de caja: {$e->getMessage()}");
        }
    }

    /**
     * Validate till details before deletion
     * 
     * @param array $tillDetails
     * @throws \Exception
     */
    private function validateTillDetailsForDeletion($tillDetails)
    {
        foreach ($tillDetails as $tillDetail) {
            // Validate till still exists
            $till = \App\Models\Tills::find($tillDetail['till_id']);
            if (!$till || $till->trashed()) {
                Log::error("Till not found for till detail", [
                    'till_detail_id' => $tillDetail['id'],
                    'till_id' => $tillDetail['till_id']
                ]);
                throw new \Exception("Caja ID {$tillDetail['till_id']} no encontrada para detalle de caja ID {$tillDetail['id']}");
            }
            
            // Validate amounts are reasonable
            if (!is_numeric($tillDetail['td_amount']) || $tillDetail['td_amount'] < 0) {
                Log::error("Invalid amount in till detail", [
                    'till_detail_id' => $tillDetail['id'],
                    'amount' => $tillDetail['td_amount']
                ]);
                throw new \Exception("Monto inválido en detalle de caja ID {$tillDetail['id']}");
            }
        }
        
        Log::debug("Till details validation passed", [
            'till_details_count' => count($tillDetails)
        ]);
    }

    /**
     * Retrieve all TillDetails records associated with a sale
     * 
     * @param int $saleId
     * @return array
     */
    private function getTillDetailsForSale($saleId)
    {
        return TillDetails::where('ref_id', $saleId)
            ->whereRaw("td_desc ILIKE ?", ['%venta%'])
            ->whereNull('deleted_at')
            ->select('id', 'till_id', 'ref_id', 'td_desc', 'td_amount')
            ->get()
            ->toArray();
    }

    /**
     * Delete all TillDetailProofPayments records for the given TillDetails
     * 
     * @param array $tillDetails
     * @throws \Exception
     */
    private function deleteTillDetailProofPayments($tillDetails)
    {
        Log::info("Starting till detail proof payments deletion", [
            'till_details_count' => count($tillDetails)
        ]);
        
        try {
            $tillDetailProofPaymentsController = new TillDetailProofPaymentsController();
            $deletedProofPaymentsCount = 0;
            $errors = [];
            
            foreach ($tillDetails as $tillDetail) {
                // Find all TillDetailProofPayments for this TillDetail
                $proofPayments = TillDetailProofPayments::where('till_detail_id', $tillDetail['id'])
                    ->whereNull('deleted_at')
                    ->get();
                
                Log::debug("Found proof payments for till detail", [
                    'till_detail_id' => $tillDetail['id'],
                    'proof_payments_count' => $proofPayments->count()
                ]);
                
                // Delete each TillDetailProofPayment using the existing controller
                foreach ($proofPayments as $proofPayment) {
                    try {
                        $result = $tillDetailProofPaymentsController->destroy($proofPayment->id);
                        
                        // Check if deletion was successful (controller returns JSON response)
                        if ($result->getStatusCode() !== 200) {
                            $error = "Error al eliminar el comprobante de pago ID: {$proofPayment->id}";
                            $errors[] = $error;
                            Log::error("Failed to delete proof payment", [
                                'proof_payment_id' => $proofPayment->id,
                                'status_code' => $result->getStatusCode(),
                                'response' => $result->getContent()
                            ]);
                        } else {
                            $deletedProofPaymentsCount++;
                            Log::debug("Successfully deleted proof payment", [
                                'proof_payment_id' => $proofPayment->id
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        $error = "Error al eliminar comprobante de pago ID {$proofPayment->id}: {$e->getMessage()}";
                        $errors[] = $error;
                        Log::error("Exception deleting proof payment", [
                            'proof_payment_id' => $proofPayment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            if (!empty($errors)) {
                throw new \Exception("Errores al eliminar comprobantes de pago: " . implode(', ', $errors));
            }
            
            Log::info("Till detail proof payments deletion completed", [
                'deleted_count' => $deletedProofPaymentsCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Till detail proof payments deletion failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los comprobantes de pago de caja: {$e->getMessage()}");
        }
    }

    /**
     * Delete all TillDetails records using the existing controller
     * 
     * @param array $tillDetails
     * @throws \Exception
     */
    private function deleteTillDetailsRecords($tillDetails)
    {
        Log::info("Starting till details records deletion", [
            'till_details_count' => count($tillDetails)
        ]);
        
        try {
            $tillDetailsController = new TillDetailsController();
            $deletedTillDetailsCount = 0;
            $errors = [];
            
            foreach ($tillDetails as $tillDetail) {
                try {
                    $result = $tillDetailsController->destroy($tillDetail['id']);
                    
                    // Check if deletion was successful (controller returns JSON response)
                    if ($result->getStatusCode() !== 200) {
                        $error = "Error al eliminar el detalle de caja ID: {$tillDetail['id']}";
                        $errors[] = $error;
                        Log::error("Failed to delete till detail", [
                            'till_detail_id' => $tillDetail['id'],
                            'status_code' => $result->getStatusCode(),
                            'response' => $result->getContent()
                        ]);
                    } else {
                        $deletedTillDetailsCount++;
                        Log::debug("Successfully deleted till detail", [
                            'till_detail_id' => $tillDetail['id']
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error al eliminar detalle de caja ID {$tillDetail['id']}: {$e->getMessage()}";
                    $errors[] = $error;
                    Log::error("Exception deleting till detail", [
                        'till_detail_id' => $tillDetail['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if (!empty($errors)) {
                throw new \Exception("Errores al eliminar detalles de caja: " . implode(', ', $errors));
            }
            
            Log::info("Till details records deletion completed", [
                'deleted_count' => $deletedTillDetailsCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Till details records deletion failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los detalles de caja: {$e->getMessage()}");
        }
    }

    /**
     * Verify that till movements deletion was successful
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function verifyTillMovementsDeletion($saleId)
    {
        // Check that no active till details remain for this sale
        $remainingTillDetails = TillDetails::where('ref_id', $saleId)
            ->whereRaw("td_desc ILIKE ?", ['%venta%'])
            ->whereNull('deleted_at')
            ->count();
            
        if ($remainingTillDetails > 0) {
            Log::error("Till details still exist after deletion", [
                'sale_id' => $saleId,
                'remaining_count' => $remainingTillDetails
            ]);
            throw new \Exception("Aún existen {$remainingTillDetails} detalles de caja sin eliminar");
        }
        
        // Check that no active proof payments remain for this sale's till details
        $remainingProofPayments = TillDetailProofPayments::whereHas('tillDetail', function($query) use ($saleId) {
            $query->where('ref_id', $saleId);
        })->whereNull('deleted_at')->count();
        
        if ($remainingProofPayments > 0) {
            Log::error("Proof payments still exist after deletion", [
                'sale_id' => $saleId,
                'remaining_count' => $remainingProofPayments
            ]);
            throw new \Exception("Aún existen {$remainingProofPayments} comprobantes de pago sin eliminar");
        }
        
        Log::debug("Till movements deletion verification passed", [
            'sale_id' => $saleId
        ]);
    }

    /**
     * Delete all sales data (details first, then main sale record) to maintain referential integrity
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function deleteSalesData($saleId)
    {
        Log::info("Starting sales data deletion process", ['sale_id' => $saleId]);
        
        try {
            // Delete sales details first to maintain referential integrity
            $this->deleteSalesDetails($saleId);
            
            // Delete main sales record
            $this->deleteSalesRecord($saleId);
            
            // Verify deletion was successful
            $this->verifySalesDataDeletion($saleId);
            
            Log::info("Sales data deletion completed successfully", ['sale_id' => $saleId]);
            
        } catch (QueryException $e) {
            Log::error("Database error during sales data deletion", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al eliminar datos de venta: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("Sales data deletion failed", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los datos de venta: {$e->getMessage()}");
        }
    }

    /**
     * Delete all SalesDetails records for a sale using existing controller
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function deleteSalesDetails($saleId)
    {
        Log::info("Starting sales details deletion", ['sale_id' => $saleId]);
        
        try {
            // Find all SalesDetails records for the sale
            $salesDetails = $this->getSalesDetailsForDeletion($saleId);
            
            if (empty($salesDetails)) {
                Log::error("No sales details found for deletion", ['sale_id' => $saleId]);
                throw new \Exception("No se encontraron detalles de venta para eliminar");
            }
            
            Log::info("Retrieved sales details for deletion", [
                'sale_id' => $saleId,
                'details_count' => count($salesDetails),
                'details' => array_map(function($sd) {
                    return [
                        'id' => $sd['id'],
                        'product_id' => $sd['product_id'],
                        'quantity' => $sd['sd_qty']
                    ];
                }, $salesDetails)
            ]);
            
            // Delete each SalesDetails record using existing controller
            $salesDetailsController = new SalesDetailsController();
            $deletedDetailsCount = 0;
            $errors = [];
            
            foreach ($salesDetails as $salesDetail) {
                try {
                    $result = $salesDetailsController->destroy($salesDetail['id']);
                    
                    // Check if deletion was successful (controller returns JSON response)
                    if ($result->getStatusCode() !== 200) {
                        $error = "Error al eliminar el detalle de venta ID: {$salesDetail['id']}";
                        $errors[] = $error;
                        Log::error("Failed to delete sales detail", [
                            'sales_detail_id' => $salesDetail['id'],
                            'status_code' => $result->getStatusCode(),
                            'response' => $result->getContent()
                        ]);
                    } else {
                        $deletedDetailsCount++;
                        Log::debug("Successfully deleted sales detail", [
                            'sales_detail_id' => $salesDetail['id']
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error al eliminar detalle de venta ID {$salesDetail['id']}: {$e->getMessage()}";
                    $errors[] = $error;
                    Log::error("Exception deleting sales detail", [
                        'sales_detail_id' => $salesDetail['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if (!empty($errors)) {
                throw new \Exception("Errores al eliminar detalles de venta: " . implode(', ', $errors));
            }
            
            Log::info("Sales details deletion completed", [
                'sale_id' => $saleId,
                'deleted_count' => $deletedDetailsCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Sales details deletion failed", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los detalles de venta: {$e->getMessage()}");
        }
    }

    /**
     * Delete the main Sales record using existing controller
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function deleteSalesRecord($saleId)
    {
        Log::info("Starting main sales record deletion", ['sale_id' => $saleId]);
        
        try {
            // Delete main sales record using existing controller
            $salesController = new SalesController();
            $result = $salesController->destroy($saleId);
            
            // Check if deletion was successful (controller returns JSON response)
            if ($result->getStatusCode() !== 200) {
                Log::error("Failed to delete main sales record", [
                    'sale_id' => $saleId,
                    'status_code' => $result->getStatusCode(),
                    'response' => $result->getContent()
                ]);
                throw new \Exception("Error al eliminar el registro principal de venta ID: {$saleId}");
            }
            
            Log::info("Main sales record deleted successfully", ['sale_id' => $saleId]);
            
        } catch (\Exception $e) {
            Log::error("Main sales record deletion failed", [
                'sale_id' => $saleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar el registro principal de venta: {$e->getMessage()}");
        }
    }

    /**
     * Verify that sales data deletion was successful
     * 
     * @param int $saleId
     * @throws \Exception
     */
    private function verifySalesDataDeletion($saleId)
    {
        // Check that no active sales details remain
        $remainingSalesDetails = SalesDetails::where('sale_id', $saleId)
            ->whereNull('deleted_at')
            ->count();
            
        if ($remainingSalesDetails > 0) {
            Log::error("Sales details still exist after deletion", [
                'sale_id' => $saleId,
                'remaining_count' => $remainingSalesDetails
            ]);
            throw new \Exception("Aún existen {$remainingSalesDetails} detalles de venta sin eliminar");
        }
        
        // Check that main sales record is soft deleted
        $sale = Sales::withTrashed()->find($saleId);
        if (!$sale || !$sale->trashed()) {
            Log::error("Main sales record not properly deleted", [
                'sale_id' => $saleId,
                'sale_exists' => $sale ? 'yes' : 'no',
                'is_trashed' => $sale ? ($sale->trashed() ? 'yes' : 'no') : 'n/a'
            ]);
            throw new \Exception("El registro principal de venta no fue eliminado correctamente");
        }
        
        Log::debug("Sales data deletion verification passed", [
            'sale_id' => $saleId
        ]);
    }

    /**
     * Retrieve all SalesDetails records for a sale that need to be deleted
     * 
     * @param int $saleId
     * @return array
     */
    private function getSalesDetailsForDeletion($saleId)
    {
        return SalesDetails::where('sale_id', $saleId)
            ->whereNull('deleted_at')
            ->select('id', 'sale_id', 'product_id', 'sd_qty')
            ->get()
            ->toArray();
    }
}