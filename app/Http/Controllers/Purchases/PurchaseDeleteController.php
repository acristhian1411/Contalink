<?php

namespace App\Http\Controllers\purchases;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Purchases\PurchasesController;
use App\Http\Controllers\PurchasesDetails\PurchasesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Models\Purchases;
use App\Models\PurchasesDetails;
use App\Models\TillDetails;
use App\Models\TillDetailProofPayments;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class PurchaseDeleteController extends ApiController
{
    /**
     * Delete a purchase transaction and reverse all its effects on stock and till movements
     * 
     * @param Request $request
     * @param int $purchaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $purchaseId)
    {
        // Log the start of deletion process with user context
        $userId = auth()->id() ?? 'unknown';
        Log::info("purchase deletion initiated", [
            'purchaseId' => $purchaseId,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            // Validate input parameters
            $this->validateInputParameters($purchaseId);
            
            DB::beginTransaction();
            Log::info("Database transaction started for purchase deletion", ['purchaseId' => $purchaseId]);
            
            // Comprehensive validation before proceeding
            $purchaseData = $this->validatepurchaseForDeletion($purchaseId);
            
            Log::info("Starting deletion process for purchase", [
                'purchaseId' => $purchaseId,
                'purchase_number' => $purchaseData['purchase_number'] ?? 'unknown',
                'purchase_date' => $purchaseData['purchase_date'] ?? 'unknown',
                'person_id' => $purchaseData['person_id'] ?? 'unknown'
            ]);
            
            // Step 1: Reverse product stock changes
            $this->reverseProductStock($purchaseId);
            
            // Step 2: Delete till movements (proof payments and till details)
            $this->deleteTillMovements($purchaseId);
            
            // Step 3: Delete purchases data (details first, then main purchase record)
            $this->deletePurchaseData($purchaseId);
            
            DB::commit();
            
            Log::info("purchase deletion completed successfully", [
                'purchaseId' => $purchaseId,
                'user_id' => $userId,
                'execution_time' => microtime(true) - LARAVEL_START
            ]);
            
            return response()->json([
                'message' => 'Compra eliminada con éxito',
                'data' => ['purchaseId' => $purchaseId]
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("purchase not found for deletion", [
                'purchaseId' => $purchaseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Compra no encontrada',
                'message' => 'No se pudo encontrar la Compra especificada'
            ], 404);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation error during purchase deletion", [
                'purchaseId' => $purchaseId,
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
            Log::error("Business logic validation error for purchase deletion", [
                'purchaseId' => $purchaseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se puede eliminar la Compra'
            ], 400);
            
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Database constraint violation during purchase deletion", [
                'purchaseId' => $purchaseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'error_code' => $e->errorInfo[1] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error de integridad de datos',
                'message' => 'No se pudo eliminar la Compra debido a restricciones de base de datos'
            ], 409);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Unexpected error during purchase deletion", [
                'purchaseId' => $purchaseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'No se pudo eliminar la Compra. Contacte al administrador del sistema.'
            ], 500);
        }
    }

    /**
     * Validate input parameters for the deletion request
     * 
     * @param mixed $purchaseId
     * @throws \InvalidArgumentException
     */
    private function validateInputParameters($purchaseId)
    {
        // Validate purchase ID is provided and is numeric
        if (empty($purchaseId) || !is_numeric($purchaseId) || $purchaseId <= 0) {
            Log::warning("Invalid purchase ID provided for deletion", ['purchaseId' => $purchaseId]);
            throw new \InvalidArgumentException('ID de Compra inválido');
        }
        
        Log::debug("Input parameters validated successfully", ['purchaseId' => $purchaseId]);
    }

    /**
     * Validate that a purchase exists and can be deleted
     * 
     * @param int $purchaseId
     * @return array purchase data for logging purposes
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    private function validatepurchaseForDeletion($purchaseId)
    {
        Log::info("Starting comprehensive purchase validation", ['purchaseId' => $purchaseId]);
        
        // Check if purchase exists (throws ModelNotFoundException if not found)
        $purchase = Purchases::findOrFail($purchaseId);
        
        Log::debug("purchase found in database", [
            'purchaseId' => $purchaseId,
            'purchase_number' => $purchase->purchase_number,
            'purchase_date' => $purchase->purchase_date,
            'person_id' => $purchase->person_id,
            'purchase_status' => $purchase->purchase_status
        ]);
        
        // Check if purchase is already soft deleted
        if ($purchase->trashed()) {
            Log::warning("Attempted to delete already deleted purchase", [
                'purchaseId' => $purchaseId,
                'deleted_at' => $purchase->deleted_at
            ]);
            throw new \InvalidArgumentException('La Compra ya ha sido eliminada');
        }
        
        // Additional business logic validations
        $this->validatepurchaseBusinessRules($purchase);
        
        // Validate that purchase has details (a purchase without details shouldn't exist but let's be safe)
        $purchasesDetailsCount = PurchasesDetails::where('purchase_id', $purchaseId)->whereNull('deleted_at')->count();
        if ($purchasesDetailsCount === 0) {
            Log::error("purchase has no details associated", [
                'purchaseId' => $purchaseId,
                'purchase_number' => $purchase->purchase_number
            ]);
            throw new \InvalidArgumentException('La Compra no tiene detalles asociados');
        }
        
        // Validate that purchase has till movements (a purchase should have payment records)
        $tillDetailsCount = TillDetails::where('ref_id', $purchaseId)
        ->whereRaw("td_desc ILIKE ?", ['%compra%'])
        ->whereNull('deleted_at')->count();
        if ($tillDetailsCount === 0) {
            Log::error("purchase has no till movements associated", [
                'purchaseId' => $purchaseId,
                'purchase_number' => $purchase->purchase_number
            ]);
            throw new \InvalidArgumentException('La Compra no tiene movimientos de caja asociados');
        }
        
        // Validate data integrity constraints
        $this->validateDataIntegrityConstraints($purchaseId, $purchasesDetailsCount, $tillDetailsCount);
        
        Log::info("purchase validation passed successfully", [
            'purchaseId' => $purchaseId,
            'details_count' => $purchasesDetailsCount,
            'till_details_count' => $tillDetailsCount
        ]);
        
        return [
            'purchase_number' => $purchase->purchase_number,
            'purchase_date' => $purchase->purchase_date,
            'person_id' => $purchase->person_id,
            'purchase_status' => $purchase->purchase_status
        ];
    }

    /**
     * Validate business rules for purchase deletion
     * 
     * @param purchases $purchase
     * @throws \InvalidArgumentException
     */
    private function validatepurchaseBusinessRules($purchase)
    {
        // Check if purchase is in a valid status for deletion
        if (isset($purchase->purchase_status) && in_array($purchase->purchase_status, ['cancelled', 'refunded'])) {
            Log::warning("Attempted to delete purchase with invalid status", [
                'purchaseId' => $purchase->id,
                'purchase_status' => $purchase->purchase_status
            ]);
            throw new \InvalidArgumentException('No se puede eliminar una Compra con estado: ' . $purchase->purchase_status);
        }
        
        // Check if purchase is too old (optional business rule - can be configured)
        $purchaseDate = \Carbon\Carbon::parse($purchase->purchase_date);
        $daysSincepurchase = $purchaseDate->diffInDays(now());
        
        if ($daysSincepurchase > 30) { // Configurable threshold
            Log::warning("Attempted to delete old purchase", [
                'purchaseId' => $purchase->id,
                'purchase_date' => $purchase->purchase_date,
                'days_since_purchase' => $daysSincepurchase
            ]);
            // This could be a warning instead of an error depending on business requirements
            Log::info("Warning: Deleting purchase older than 30 days", [
                'purchaseId' => $purchase->id,
                'days_since_purchase' => $daysSincepurchase
            ]);
        }
        
        Log::debug("Business rules validation passed", ['purchaseId' => $purchase->id]);
    }

    /**
     * Validate data integrity constraints before deletion
     * 
     * @param int $purchaseId
     * @param int $purchasesDetailsCount
     * @param int $tillDetailsCount
     * @throws \InvalidArgumentException
     */
    private function validateDataIntegrityConstraints($purchaseId, $purchasesDetailsCount, $tillDetailsCount)
    {
        // Validate that all products in purchase details still exist and are not deleted
        $invalidProducts = PurchasesDetails::where('purchase_id', $purchaseId)
            ->whereNull('deleted_at')
            ->whereDoesntHave('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
            
        if ($invalidProducts > 0) {
            Log::error("purchase contains references to deleted products", [
                'purchaseId' => $purchaseId,
                'invalid_products_count' => $invalidProducts
            ]);
            throw new \InvalidArgumentException('La Compra contiene productos que ya no existen en el sistema');
        }
        
        // Validate that till details reference valid tills
        $invalidTills = TillDetails::where('ref_id', $purchaseId)
            ->whereRaw("td_desc ILIKE ?", ['%compra%'])
            ->whereNull('deleted_at')
            ->whereDoesntHave('till', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
            
        if ($invalidTills > 0) {
            Log::error("purchase contains references to deleted tills", [
                'purchaseId' => $purchaseId,
                'invalid_tills_count' => $invalidTills
            ]);
            throw new \InvalidArgumentException('La Compra contiene referencias a cajas que ya no existen');
        }
        
        // Check for orphaned records that might indicate data corruption
        $orphanedProofPayments = TillDetailProofPayments::whereHas('tillDetail', function($query) use ($purchaseId) {
            $query->where('ref_id', $purchaseId)->whereNull('deleted_at');
        })->whereNull('deleted_at')->count();
        
        if ($orphanedProofPayments === 0 && $tillDetailsCount > 0) {
            Log::warning("Till details exist but no proof payments found", [
                'purchaseId' => $purchaseId,
                'till_details_count' => $tillDetailsCount
            ]);
            // This might be valid for cash-only purchases, so just log as warning
        }
        
        Log::debug("Data integrity constraints validated successfully", [
            'purchaseId' => $purchaseId,
            'invalid_products' => $invalidProducts,
            'invalid_tills' => $invalidTills,
            'proof_payments_count' => $orphanedProofPayments
        ]);
    }

    /**
     * Reverse product stock changes by adding back quantities that were sold
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function reverseProductStock($purchaseId)
    {
        Log::info("Starting stock reversal process", ['purchaseId' => $purchaseId]);
        
        try {
            // Retrieve all purchase details with product information
            $purchaseDetails = $this->getpurchaseDetailsForStockReversal($purchaseId);
            
            if (empty($purchaseDetails)) {
                Log::error("No purchase details found for stock reversal", ['purchaseId' => $purchaseId]);
                throw new \Exception("No se encontraron detalles de Compra para revertir el stock");
            }
            
            Log::info("Retrieved purchase details for stock reversal", [
                'purchaseId' => $purchaseId,
                'details_count' => count($purchaseDetails)
            ]);
            
            // Validate products exist and can have stock reversed
            $this->validateProductsForStockReversal($purchaseDetails);
            
            // Prepare data for ProductsController updatePriceAndQty method
            $stockReversalData = $this->prepareStockReversalData($purchaseDetails);
            
            // Log the stock reversal operation details
            Log::info("Prepared stock reversal data", [
                'purchaseId' => $purchaseId,
                'products_to_reverse' => array_map(function($item) {
                    return [
                        'product_id' => $item['id'],
                        'quantity_to_subtract' => $item['product_quantity'],
                        'cost_price' => $item['product_cost_price']
                    ];
                }, $stockReversalData)
            ]);
            
            // Create request object for ProductsController
            $request = new Request([
                'controller' => 'purchases_reversal',
                'details' => $stockReversalData,
                'fromController' => true
            ]);
            
            // Call ProductsController to reverse stock
            $productsController = new ProductsController();
            $result = $productsController->updatePriceAndQty($request);
            
            if ($result !== true) {
                Log::error("ProductsController returned failure for stock reversal", [
                    'purchaseId' => $purchaseId,
                    'result' => $result
                ]);
                throw new \Exception("Error al revertir el stock de los productos");
            }
            
            // Verify stock reversal was successful by checking final quantities
            $this->verifyStockReversalSuccess($purchaseDetails);
            
            Log::info("Stock reversal completed successfully", [
                'purchaseId' => $purchaseId,
                'products_processed' => count($stockReversalData)
            ]);
            
        } catch (QueryException $e) {
            Log::error("Database error during stock reversal", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al revertir el stock: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("Stock reversal failed", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al revertir el stock: {$e->getMessage()}");
        }
    }

    /**
     * Retrieve purchase details with product information for stock reversal
     * 
     * @param int $purchaseId
     * @return array
     */
    private function getpurchaseDetailsForStockReversal($purchaseId)
    {
        return PurchasesDetails::where('purchase_id', $purchaseId)
            ->with(['product' => function($query) {
                $query->select('id', 'product_cost_price');
            }])
            ->select('id', 'product_id', 'pd_qty', 'pd_amount')
            ->get()
            ->toArray();
    }

    /**
     * Validate that products can have their stock reversed
     * 
     * @param array $purchaseDetails
     * @throws \Exception
     */
    private function validateProductsForStockReversal($purchaseDetails)
    {
        foreach ($purchaseDetails as $detail) {
            // Check if product still exists
            if (!isset($detail['product']) || empty($detail['product'])) {
                Log::error("Product not found for stock reversal", [
                    'product_id' => $detail['product_id'],
                    'purchase_detail_id' => $detail['id']
                ]);
                throw new \Exception("Producto ID {$detail['product_id']} no encontrado para revertir stock");
            }
            
            // Validate quantity is positive
            if ($detail['pd_qty'] <= 0) {
                Log::error("Invalid quantity for stock reversal", [
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['pd_qty']
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
            'products_count' => count($purchaseDetails)
        ]);
    }

    /**
     * Prepare stock reversal data in the format expected by ProductsController
     * 
     * @param array $purchaseDetails
     * @return array
     */
    private function prepareStockReversalData($purchaseDetails)
    {
        $stockReversalData = [];
        
        foreach ($purchaseDetails as $detail) {
            $stockReversalData[] = [
                'id' => $detail['product_id'],
                'product_quantity' => $detail['pd_qty'], // Quantity to add back
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
     * @param array $purchaseDetails
     * @throws \Exception
     */
    private function verifyStockReversalSuccess($purchaseDetails)
    {
        $verificationErrors = [];
        
        foreach ($purchaseDetails as $detail) {
            $product = Products::find($detail['product_id']);
            
            if (!$product) {
                $verificationErrors[] = "Producto ID {$detail['product_id']} no encontrado durante verificación";
                continue;
            }
            
            // Log the current stock level for audit purposes
            Log::debug("Stock level after reversal", [
                'product_id' => $detail['product_id'],
                'current_quantity' => $product->product_quantity,
                'reversed_quantity' => $detail['pd_qty']
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
            'products_verified' => count($purchaseDetails)
        ]);
    }

    /**
     * Delete all till movements associated with a purchase (TillDetails and TillDetailProofPayments)
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function deleteTillMovements($purchaseId)
    {
        Log::info("Starting till movements deletion process", ['purchaseId' => $purchaseId]);
        
        try {
            // Find all TillDetails records associated with the purchase
            $tillDetails = $this->getTillDetailsForpurchase($purchaseId);
            
            if (empty($tillDetails)) {
                Log::error("No till movements found for purchase", ['purchaseId' => $purchaseId]);
                throw new \Exception("No se encontraron movimientos de caja para la Compra");
            }
            
            Log::info("Retrieved till details for deletion", [
                'purchaseId' => $purchaseId,
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
            $this->verifyTillMovementsDeletion($purchaseId);
            
            Log::info("Till movements deletion completed successfully", [
                'purchaseId' => $purchaseId,
                'deleted_till_details' => count($tillDetails)
            ]);
            
        } catch (QueryException $e) {
            Log::error("Database error during till movements deletion", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al eliminar movimientos de caja: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("Till movements deletion failed", [
                'purchaseId' => $purchaseId,
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
     * Retrieve all TillDetails records associated with a purchase
     * 
     * @param int $purchaseId
     * @return array
     */
    private function getTillDetailsForpurchase($purchaseId)
    {
        return TillDetails::where('ref_id', $purchaseId)
            ->whereRaw("td_desc ILIKE ?", ['%compra%'])
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
     * @param int $purchaseId
     * @throws \Exception
     */
    private function verifyTillMovementsDeletion($purchaseId)
    {
        // Check that no active till details remain for this purchase
        $remainingTillDetails = TillDetails::where('ref_id', $purchaseId)
            ->whereRaw("td_desc ILIKE ?", ['%compra%'])
            ->whereNull('deleted_at')
            ->count();
            
        if ($remainingTillDetails > 0) {
            Log::error("Till details still exist after deletion", [
                'purchaseId' => $purchaseId,
                'remaining_count' => $remainingTillDetails
            ]);
            throw new \Exception("Aún existen {$remainingTillDetails} detalles de caja sin eliminar");
        }
        
        // Check that no active proof payments remain for this purchase's till details
        $remainingProofPayments = TillDetailProofPayments::whereHas('tillDetail', function($query) use ($purchaseId) {
            $query->where('ref_id', $purchaseId);
        })->whereNull('deleted_at')->count();
        
        if ($remainingProofPayments > 0) {
            Log::error("Proof payments still exist after deletion", [
                'purchaseId' => $purchaseId,
                'remaining_count' => $remainingProofPayments
            ]);
            throw new \Exception("Aún existen {$remainingProofPayments} comprobantes de pago sin eliminar");
        }
        
        Log::debug("Till movements deletion verification passed", [
            'purchaseId' => $purchaseId
        ]);
    }

    /**
     * Delete all purchases data (details first, then main purchase record) to maintain referential integrity
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function deletePurchaseData($purchaseId)
    {
        Log::info("Starting purchases data deletion process", ['purchaseId' => $purchaseId]);
        
        try {
            // Delete purchases details first to maintain referential integrity
            $this->deletepurchasesDetails($purchaseId);
            
            // Delete main purchases record
            $this->deletepurchasesRecord($purchaseId);
            
            // Verify deletion was successful
            $this->verifypurchasesDataDeletion($purchaseId);
            
            Log::info("purchases data deletion completed successfully", ['purchaseId' => $purchaseId]);
            
        } catch (QueryException $e) {
            Log::error("Database error during purchases data deletion", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown'
            ]);
            throw new \Exception("Error de base de datos al eliminar datos de Compra: " . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error("purchases data deletion failed", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los datos de Compra: {$e->getMessage()}");
        }
    }

    /**
     * Delete all PurchasesDetails records for a purchase using existing controller
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function deletepurchasesDetails($purchaseId)
    {
        Log::info("Starting purchases details deletion", ['purchaseId' => $purchaseId]);
        
        try {
            // Find all PurchasesDetails records for the purchase
            $PurchasesDetails = $this->getpurchasesDetailsForDeletion($purchaseId);
            
            if (empty($PurchasesDetails)) {
                Log::error("No purchases details found for deletion", ['purchaseId' => $purchaseId]);
                throw new \Exception("No se encontraron detalles de Compra para eliminar");
            }
            
            Log::info("Retrieved purchases details for deletion", [
                'purchaseId' => $purchaseId,
                'details_count' => count($PurchasesDetails),
                'details' => array_map(function($sd) {
                    return [
                        'id' => $sd['id'],
                        'product_id' => $sd['product_id'],
                        'quantity' => $sd['pd_qty']
                    ];
                }, $PurchasesDetails)
            ]);
            
            // Delete each PurchasesDetails record using existing controller
            $purchasesDetailsController = new PurchasesDetailsController();
            $deletedDetailsCount = 0;
            $errors = [];
            
            foreach ($PurchasesDetails as $purchasesDetail) {
                try {
                    $result = $purchasesDetailsController->destroy($purchasesDetail['id']);
                    
                    // Check if deletion was successful (controller returns JSON response)
                    if ($result->getStatusCode() !== 200) {
                        $error = "Error al eliminar el detalle de Compra ID: {$purchasesDetail['id']}";
                        $errors[] = $error;
                        Log::error("Failed to delete purchases detail", [
                            'purchases_detail_id' => $purchasesDetail['id'],
                            'status_code' => $result->getStatusCode(),
                            'response' => $result->getContent()
                        ]);
                    } else {
                        $deletedDetailsCount++;
                        Log::debug("Successfully deleted purchases detail", [
                            'purchases_detail_id' => $purchasesDetail['id']
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error al eliminar detalle de Compra ID {$purchasesDetail['id']}: {$e->getMessage()}";
                    $errors[] = $error;
                    Log::error("Exception deleting purchases detail", [
                        'purchases_detail_id' => $purchasesDetail['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if (!empty($errors)) {
                throw new \Exception("Errores al eliminar detalles de Compra: " . implode(', ', $errors));
            }
            
            Log::info("purchases details deletion completed", [
                'purchaseId' => $purchaseId,
                'deleted_count' => $deletedDetailsCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("purchases details deletion failed", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar los detalles de Compra: {$e->getMessage()}");
        }
    }

    /**
     * Delete the main purchases record using existing controller
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function deletepurchasesRecord($purchaseId)
    {
        Log::info("Starting main purchases record deletion", ['purchaseId' => $purchaseId]);
        
        try {
            // Delete main purchases record using existing controller
            $purchasesController = new PurchasesController();
            $result = $purchasesController->destroy($purchaseId);
            
            // Check if deletion was successful (controller returns JSON response)
            if ($result->getStatusCode() !== 200) {
                Log::error("Failed to delete main purchases record", [
                    'purchaseId' => $purchaseId,
                    'status_code' => $result->getStatusCode(),
                    'response' => $result->getContent()
                ]);
                throw new \Exception("Error al eliminar el registro principal de Compra ID: {$purchaseId}");
            }
            
            Log::info("Main purchases record deleted successfully", ['purchaseId' => $purchaseId]);
            
        } catch (\Exception $e) {
            Log::error("Main purchases record deletion failed", [
                'purchaseId' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al eliminar el registro principal de Compra: {$e->getMessage()}");
        }
    }

    /**
     * Verify that purchases data deletion was successful
     * 
     * @param int $purchaseId
     * @throws \Exception
     */
    private function verifypurchasesDataDeletion($purchaseId)
    {
        // Check that no active purchases details remain
        $remainingpurchasesDetails = PurchasesDetails::where('purchase_id', $purchaseId)
            ->whereNull('deleted_at')
            ->count();
            
        if ($remainingpurchasesDetails > 0) {
            Log::error("purchases details still exist after deletion", [
                'purchaseId' => $purchaseId,
                'remaining_count' => $remainingpurchasesDetails
            ]);
            throw new \Exception("Aún existen {$remainingpurchasesDetails} detalles de Compra sin eliminar");
        }
        
        // Check that main purchases record is soft deleted
        $purchase = Purchases::withTrashed()->find($purchaseId);
        if (!$purchase || !$purchase->trashed()) {
            Log::error("Main purchases record not properly deleted", [
                'purchaseId' => $purchaseId,
                'purchase_exists' => $purchase ? 'yes' : 'no',
                'is_trashed' => $purchase ? ($purchase->trashed() ? 'yes' : 'no') : 'n/a'
            ]);
            throw new \Exception("El registro principal de Compra no fue eliminado correctamente");
        }
        
        Log::debug("purchases data deletion verification passed", [
            'purchaseId' => $purchaseId
        ]);
    }

    /**
     * Retrieve all PurchasesDetails records for a purchase that need to be deleted
     * 
     * @param int $purchaseId
     * @return array
     */
    private function getpurchasesDetailsForDeletion($purchaseId)
    {
        return PurchasesDetails::where('purchase_id', $purchaseId)
            ->whereNull('deleted_at')
            ->select('id', 'purchase_id', 'product_id', 'pd_qty')
            ->get()
            ->toArray();
    }
}