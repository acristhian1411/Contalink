# Sales Deletion Feature Design

## Overview

This design implements a comprehensive sales deletion system that maintains data integrity by orchestrating multiple existing controllers to reverse the effects of a sale transaction. The solution follows the established architecture pattern used in `SaleStoreController`, creating a new orchestrator controller that manages the complex deletion process through database transactions.

## Architecture

### Controller Structure
The solution introduces a new `SaleDeleteController` that acts as an orchestrator, similar to how `SaleStoreController` manages the creation process. This controller will:

- Coordinate the deletion process across multiple entities
- Manage database transactions for atomicity
- Handle error scenarios with proper rollback
- Follow existing error handling and response patterns

### Transaction Flow
```
1. Begin Database Transaction
2. Validate Sale Exists and Not Already Deleted
3. Retrieve Sale Details and Till Movements
4. Reverse Product Stock Changes
5. Delete Till Detail Proof Payments
6. Delete Till Details
7. Delete Sales Details
8. Delete Main Sale Record
9. Commit Transaction (or Rollback on Error)
```

## Components and Interfaces

### SaleDeleteController
**Location**: `app/Http/Controllers/Sales/SaleDeleteController.php`

**Primary Method**: `destroy(Request $request, $saleId)`

**Dependencies**:
- `SalesController` - for main sale deletion
- `SalesDetailsController` - for sales details deletion
- `TillDetailsController` - for till movements deletion
- `TillDetailProofPaymentsController` - for payment proof deletion
- `ProductsController` - for stock reversal

**Key Methods**:
```php
public function destroy(Request $request, $saleId)
public function validateSaleForDeletion($saleId)
public function reverseProductStock($saleDetails)
public function deleteTillMovements($saleId)
public function deleteSaleDetails($saleId)
```

### Integration Points

#### Stock Reversal Process
- Retrieve all `SalesDetails` for the sale
- For each detail, call `ProductsController::updatePriceAndQty()` with:
  - `controller` = 'sales_reversal' (new identifier)
  - `details` array containing product quantities to add back
  - Maintain original cost prices from the sale details

#### Till Movement Deletion
- Query `TillDetails` where `ref_id` = sale ID
- For each till detail:
  - Delete associated `TillDetailProofPayments` records
  - Delete the `TillDetails` record itself
- Use existing controller `destroy` methods

#### Sales Data Deletion
- Delete all `SalesDetails` records for the sale
- Delete the main `Sales` record
- Use existing controller `destroy` methods with soft delete

## Data Models

### Affected Models and Relationships

#### Sales Model
- **Relationships**: `hasMany(SalesDetails)`, `hasMany(TillDetails)`
- **Deletion**: Soft delete via existing `SalesController::destroy()`

#### SalesDetails Model
- **Relationships**: `belongsTo(Sales)`, `belongsTo(Products)`
- **Deletion**: Soft delete via existing `SalesDetailsController::destroy()`
- **Stock Impact**: Quantities must be added back to products

#### TillDetails Model
- **Relationships**: `hasMany(TillDetailProofPayments)`
- **Deletion**: Soft delete via existing `TillDetailsController::destroy()`
- **Identification**: Found by `ref_id` matching sale ID

#### TillDetailProofPayments Model
- **Relationships**: `belongsTo(TillDetails)`
- **Deletion**: Soft delete via existing controller

#### Products Model
- **Stock Update**: Use existing `updatePriceAndQty()` method
- **Logic**: Add back quantities that were subtracted during sale

## Error Handling

### Transaction Management
```php
try {
    DB::beginTransaction();
    
    // Validation
    $this->validateSaleForDeletion($saleId);
    
    // Orchestrated deletion process
    $this->reverseProductStock($saleDetails);
    $this->deleteTillMovements($saleId);
    $this->deleteSaleDetails($saleId);
    $this->deleteSale($saleId);
    
    DB::commit();
    return $this->showAfterAction([], 'delete', 200);
    
} catch (\Exception $e) {
    DB::rollBack();
    return response()->json([
        'error' => $e->getMessage(),
        'message' => 'No se pudo eliminar la venta'
    ], 500);
}
```

### Validation Rules
- Sale must exist and not be soft deleted
- Sale must have associated details and till movements
- All related records must be accessible for deletion

### Error Scenarios
1. **Sale Not Found**: Return 404 with appropriate message
2. **Sale Already Deleted**: Return 400 with appropriate message
3. **Stock Reversal Failure**: Rollback transaction, return 500
4. **Till Movement Deletion Failure**: Rollback transaction, return 500
5. **Database Constraint Violations**: Rollback transaction, return 500

## Testing Strategy

### Unit Tests
- Test individual methods of `SaleDeleteController`
- Mock dependencies to isolate functionality
- Test error handling scenarios
- Verify transaction rollback behavior

### Integration Tests
- Test complete deletion flow with real database
- Verify stock quantities are correctly restored
- Confirm till movements are properly removed
- Test with various payment method combinations

### Test Data Requirements
- Sample sales with multiple products
- Sales with different payment methods
- Sales with various quantities and prices
- Edge cases (single item sales, cash-only sales)

### Key Test Scenarios
1. **Successful Deletion**: Complete flow works correctly
2. **Stock Reversal Accuracy**: Product quantities restored exactly
3. **Till Movement Cleanup**: All related records removed
4. **Transaction Rollback**: Partial failures don't corrupt data
5. **Validation Errors**: Proper error responses for invalid requests

## Implementation Notes

### ProductsController Enhancement
The existing `updatePriceAndQty()` method needs to handle a new controller type:
```php
// Add to existing method
if($req->controller == 'sales_reversal'){
    $product->product_quantity += intval($value['product_quantity']);
}
```

### Route Definition
Add route to existing sales routes:
```php
Route::delete('/sales/{id}/delete', [SaleDeleteController::class, 'destroy']);
```

### Logging Strategy
- Log deletion attempts with sale ID and user information
- Log each step of the deletion process for audit trail
- Log errors with full context for troubleshooting

### Performance Considerations
- Process deletions in single transaction to minimize lock time
- Use eager loading to reduce database queries
- Consider batch operations for large sales with many details