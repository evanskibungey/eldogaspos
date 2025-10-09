# Cylinder Inventory Integration - Implementation Documentation

## Overview
This document outlines the implementation of product inventory integration with the cylinder management system. Previously, cylinder transactions operated independently without affecting product inventory. Now, when cylinder transactions are completed, the system automatically updates product inventory and creates corresponding stock movement records.

---

## Problem Addressed

**Gap:** Cylinder transactions don't affect product inventory – The cylinder management system currently operates independently and does not update the main product inventory.

**Solution:** Implemented integration so that when cylinder transactions are marked as completed, the system updates inventory and creates the respective product stock movement records accordingly.

---

## Changes Made

### 1. Database Migration

**File Created:** `database/migrations/2025_10_07_000001_add_product_id_to_cylinder_transactions.php`

**Purpose:** Add `product_id` field to link cylinder transactions with products in inventory.

**Changes:**
- Added `product_id` column (nullable foreign key to products table)
- Added index for query performance
- Includes rollback capability

**To Run:**
```bash
php artisan migrate
```

---

### 2. CylinderTransaction Model Updates

**File Modified:** `app/Models/CylinderTransaction.php`

**Changes Made:**

#### Added to Fillable Array:
```php
'product_id'
```

#### New Relationships:
```php
public function product()
{
    return $this->belongsTo(Product::class);
}

public function stockMovements()
{
    return $this->hasMany(StockMovement::class, 'reference_id')
                ->where('reference_type', 'cylinder_transaction');
}

public function cancellationStockMovements()
{
    return $this->hasMany(StockMovement::class, 'reference_id')
                ->where('reference_type', 'cylinder_cancellation');
}
```

---

### 3. CylinderController Updates

**File Modified:** `app/Http/Controllers/Admin/CylinderController.php`

**New Imports Added:**
```php
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;
```

#### Changes Summary:

1. **create() method:** Now loads products for form selection
2. **edit() method:** Now loads products for form editing
3. **store() method:**
   - Added product_id validation
   - Saves product_id with transaction
   - Deducts inventory for advance collections immediately
   
4. **update() method:**
   - Added product_id validation
   - Updates product_id field

5. **complete() method:**
   - Deducts inventory for drop-offs when customer collects
   - No additional deduction for advance collections (already done on creation)

6. **cancel() method:**
   - Restores inventory if it was previously deducted
   - Creates cancellation stock movement

7. **destroy() method:**
   - Restores inventory if it was previously deducted
   - Creates cancellation stock movement

8. **quickComplete() method:**
   - Deducts inventory when customer collects cylinder

#### New Private Helper Methods:

**deductInventoryForCylinder()**
- Verifies stock availability
- Decrements product stock
- Creates stock movement record
- Logs the operation

**restoreInventoryForCylinder()**
- Checks if inventory was previously deducted
- Increments product stock
- Creates cancellation stock movement
- Logs the operation

**generateStockMovementNote()**
- Creates descriptive notes for stock movements
- Includes transaction details for audit trail

---

### 4. StockMovement Model Updates

**File Modified:** `app/Models/StockMovement.php`

**New Scope Methods:**
```php
public function scopeCylinderTransactions($query)
{
    return $query->where('reference_type', 'cylinder_transaction');
}

public function scopeCylinderCancellations($query)
{
    return $query->where('reference_type', 'cylinder_cancellation');
}
```

---

## Business Logic Flow

### Scenario 1: Drop-Off Transaction

**Customer Journey:**
1. Customer drops off empty cylinder for refilling
2. Transaction created (status: 'active')
3. **No inventory deduction yet**
4. Cylinder gets refilled
5. Customer collects filled cylinder
6. Transaction marked as completed
7. **Inventory deducted at this point**
8. Stock movement created

**Inventory Impact:** Deducted when completed (collection)

---

### Scenario 2: Advance Collection Transaction

**Customer Journey:**
1. Customer takes filled cylinder immediately
2. Transaction created (status: 'active')
3. **Inventory deducted immediately**
4. **Stock movement created**
5. Customer returns empty cylinder later
6. Transaction marked as completed
7. **No additional inventory deduction**

**Inventory Impact:** Deducted immediately when created

---

### Scenario 3: Transaction Cancellation

**For Any Transaction Type:**
1. Transaction is cancelled
2. System checks if inventory was deducted
3. If yes:
   - Inventory restored
   - Cancellation stock movement created
4. Transaction status set to 'cancelled'

**Inventory Impact:** Restored if previously deducted

---

### Scenario 4: Transaction Deletion

**Same as Cancellation:**
1. Transaction is deleted
2. System checks if inventory was deducted
3. If yes:
   - Inventory restored
   - Cancellation stock movement created
4. Transaction deleted from database

**Inventory Impact:** Restored if previously deducted

---

## Stock Movement Reference Types

### New Reference Types Added:

| Reference Type | Description | Type | When Created |
|---------------|-------------|------|--------------|
| `cylinder_transaction` | Cylinder transaction completed | out | When cylinder is collected or given to customer |
| `cylinder_cancellation` | Cancelled cylinder transaction | in | When transaction is cancelled or deleted |

### Complete Reference Types List:

1. `initial` - Initial stock on product creation (in)
2. `adjustment` - Stock adjusted during product update (in/out)
3. `manual_adjustment` - Manual stock adjustment (in/out)
4. `sale` - Stock sold through POS/API (out)
5. `sale_void` - Stock returned from void (in)
6. `cylinder_transaction` - **Cylinder transaction (out) [NEW]**
7. `cylinder_cancellation` - **Cancelled cylinder (in) [NEW]**

---

## Database Schema Changes

### cylinder_transactions Table

**New Column:**
```sql
product_id BIGINT UNSIGNED NULL
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
INDEX (product_id)
```

**Properties:**
- Nullable: Yes (allows existing transactions to remain valid)
- Foreign Key: Links to products table
- On Delete: RESTRICT (prevents product deletion if linked to transactions)
- Indexed: Yes (for query performance)

---

## API & Usage Examples

### Creating Cylinder Transaction with Product

```php
$transaction = CylinderTransaction::create([
    'customer_id' => $customerId,
    'product_id' => $productId, // NEW: Link to product
    'cylinder_size' => '13kg',
    'cylinder_type' => 'LPG',
    'transaction_type' => 'advance_collection',
    'payment_status' => 'paid',
    'amount' => 1500.00,
    // ... other fields
]);

// Inventory automatically deducted for advance_collection
```

### Query Cylinder Transactions with Product

```php
// Get transaction with product details
$transaction = CylinderTransaction::with('product')->find($id);

// Access product information
if ($transaction->product) {
    echo $transaction->product->name;
    echo $transaction->product->stock;
}
```

### Query Stock Movements for Cylinder

```php
// Get all cylinder-related movements
$movements = StockMovement::cylinderTransactions()->get();

// Get movements for specific transaction
$transaction = CylinderTransaction::find($id);
$movements = $transaction->stockMovements;

// Get cancellation movements
$cancellations = StockMovement::cylinderCancellations()->get();
```

### Check if Transaction Affected Inventory

```php
$transaction = CylinderTransaction::find($id);

// Check if inventory was deducted
$hasStockMovements = $transaction->stockMovements()->exists();

// Get the stock movement details
$movements = $transaction->stockMovements()
    ->with('product')
    ->get();
```

---

## Inventory Deduction Logic

### When is Inventory Deducted?

```php
// DROP-OFF: Deducted when completed
if ($transaction->isDropOff()) {
    // Deduct when: complete() or quickComplete() is called
    // Reason: Customer gets cylinder only when collecting
}

// ADVANCE COLLECTION: Deducted immediately
if ($transaction->isAdvanceCollection()) {
    // Deduct when: store() creates the transaction
    // Reason: Customer takes cylinder immediately
}
```

### Quantity Logic

Currently: **1 cylinder = 1 unit of product stock**

This can be modified in the `deductInventoryForCylinder()` method if your business logic differs.

---

## Error Handling

### Insufficient Stock

```php
try {
    $transaction->complete();
} catch (\Exception $e) {
    // Error: "Insufficient stock for [Product Name]. Available: X"
    // Transaction is rolled back
    // No changes to inventory
}
```

### Product Not Found

```php
// If product_id is invalid or product deleted
try {
    $transaction->complete();
} catch (\Exception $e) {
    // Error logged
    // Transaction rolled back
    // User notified
}
```

### Transaction Integrity

All inventory changes happen within database transactions:
- If any step fails, everything rolls back
- Ensures data consistency
- No orphaned stock movements

---

## Logging

### What Gets Logged

**Successful Operations:**
```
INFO: Inventory deducted for cylinder transaction
- transaction_id: 123
- product_id: 45
- quantity: 1
- new_stock: 25
```

**Errors:**
```
ERROR: Error deducting inventory for cylinder
- Exception message
- Stack trace
```

**Restoration:**
```
INFO: Inventory restored for cancelled cylinder transaction
- transaction_id: 123
- product_id: 45
- quantity: 1
- new_stock: 26
```

---

## Testing Checklist

### Test Case 1: Drop-Off with Product
- [ ] Create drop-off transaction with product
- [ ] Verify no inventory deduction on creation
- [ ] Complete transaction
- [ ] Verify inventory deducted
- [ ] Verify stock movement created
- [ ] Check stock movement details

### Test Case 2: Advance Collection with Product
- [ ] Create advance collection with product
- [ ] Verify inventory deducted immediately
- [ ] Verify stock movement created on creation
- [ ] Complete transaction (return empty)
- [ ] Verify no additional deduction
- [ ] Check stock levels are correct

### Test Case 3: Drop-Off without Product
- [ ] Create drop-off without product_id
- [ ] Complete transaction
- [ ] Verify no inventory changes
- [ ] Verify no stock movements created
- [ ] Confirm transaction completes successfully

### Test Case 4: Transaction Cancellation
- [ ] Create advance collection with product
- [ ] Verify inventory deducted
- [ ] Cancel transaction
- [ ] Verify inventory restored
- [ ] Verify cancellation stock movement created

### Test Case 5: Insufficient Stock
- [ ] Set product stock to 0
- [ ] Try to create advance collection
- [ ] Verify error message
- [ ] Verify transaction not created
- [ ] Verify no stock movements

### Test Case 6: Product Deletion Protection
- [ ] Create transaction with product
- [ ] Try to delete linked product
- [ ] Verify deletion fails
- [ ] Verify appropriate error message

### Test Case 7: Quick Complete
- [ ] Create drop-off with product
- [ ] Use quickComplete() method
- [ ] Verify inventory deducted
- [ ] Verify stock movement created

### Test Case 8: Multiple Transactions Same Product
- [ ] Create multiple transactions for same product
- [ ] Complete all transactions
- [ ] Verify total inventory reduction is correct
- [ ] Verify all stock movements created

---

## Migration Guide for Existing Data

### For Existing Transactions

**Option 1: Leave as-is**
- Existing transactions without product_id continue to work
- No inventory impact from old transactions
- New transactions can have products linked

**Option 2: Backfill Product Links**
```php
// Example backfill script
$transactions = CylinderTransaction::whereNull('product_id')->get();

foreach ($transactions as $transaction) {
    // Logic to determine which product matches
    $product = Product::where('name', 'LIKE', '%' . $transaction->cylinder_size . '%')
                     ->first();
    
    if ($product) {
        $transaction->update(['product_id' => $product->id]);
    }
}
```

**Recommendation:** Start fresh with new transactions, leave old ones without product links.

---

## Performance Considerations

### Database Impact

**Additional Queries per Transaction:**
- 1 SELECT (verify product/stock)
- 1 UPDATE (decrement/increment stock)
- 1 INSERT (create stock movement)

**Total:** ~3 additional queries per cylinder operation

**Impact:** Minimal - all within same transaction

### Index Usage

**New Indexes:**
- `cylinder_transactions.product_id` - For joins and lookups
- Stock movements already indexed on `reference_type` and `reference_id`

### Optimization Tips

1. **Eager Load Product:**
```php
$transactions = CylinderTransaction::with('product')->get();
```

2. **Batch Operations:**
If processing many transactions, use database transactions:
```php
DB::transaction(function() {
    // Process multiple cylinders
});
```

---

## Configuration Options

### Quantity Per Cylinder

Currently hardcoded to 1. To change:

**Location:** `CylinderController::deductInventoryForCylinder()`

```php
// Change this line:
$quantity = 1;

// To:
$quantity = $cylinder->getQuantityFromSize(); // Custom method
```

### Stock Movement Notes

**Location:** `CylinderController::generateStockMovementNote()`

Customize the note format to match your business needs.

---

## Troubleshooting

### Issue: Inventory Not Deducting

**Check:**
1. Is `product_id` set on transaction?
2. Is transaction being completed?
3. Check logs for errors
4. Verify product still exists

**Debug:**
```php
$transaction = CylinderTransaction::find($id);
Log::info('Transaction details', [
    'product_id' => $transaction->product_id,
    'status' => $transaction->status,
    'type' => $transaction->transaction_type
]);
```

### Issue: Stock Movement Not Created

**Check:**
1. Database transaction errors
2. StockMovement model fillable array
3. Foreign key constraints
4. Log files

**Query:**
```sql
SELECT * FROM stock_movements 
WHERE reference_type IN ('cylinder_transaction', 'cylinder_cancellation')
ORDER BY created_at DESC 
LIMIT 20;
```

### Issue: Inventory Restored Incorrectly

**Check:**
1. Was inventory actually deducted?
2. Check `restoreInventoryForCylinder()` logic
3. Verify transaction type and status
4. Check stock movement records

**Diagnostic Query:**
```sql
SELECT ct.id, ct.transaction_type, ct.status, 
       ct.collection_date, ct.return_date,
       COUNT(sm.id) as movement_count
FROM cylinder_transactions ct
LEFT JOIN stock_movements sm ON ct.id = sm.reference_id 
  AND sm.reference_type LIKE 'cylinder%'
WHERE ct.id = ?
GROUP BY ct.id;
```

---

## Security Considerations

### Product Deletion Protection

- Foreign key constraint prevents product deletion if linked to transactions
- Use soft deletes if needed
- Or mark product as inactive instead

### Stock Manipulation

- All inventory changes logged
- User accountability tracked
- Changes within transactions for data integrity

### Access Control

- Ensure proper permissions for cylinder operations
- Log all cylinder completions and cancellations
- Monitor unusual stock deduction patterns

---

## Future Enhancements

### Potential Additions:

1. **Variable Quantities**
   - Support for partial cylinders
   - Different units per size

2. **Batch Operations**
   - Complete multiple cylinders at once
   - Bulk inventory updates

3. **Reporting**
   - Cylinder inventory report
   - Stock movement by cylinder type
   - Cylinder turnover analysis

4. **Automation**
   - Auto-complete based on rules
   - Scheduled reconciliation
   - Low stock alerts for cylinders

5. **Integration**
   - Link to purchase orders
   - Supplier management
   - Cylinder tracking system

---

## Maintenance Notes

### Regular Tasks:

**Daily:**
- Monitor logs for inventory errors
- Verify stock movements created

**Weekly:**
- Reconcile cylinder inventory
- Review cancelled transactions
- Check for orphaned records

**Monthly:**
- Analyze cylinder stock patterns
- Review slow-moving cylinders
- Clean up old transactions

### Monitoring Queries:

**Recent Cylinder Movements:**
```sql
SELECT * FROM stock_movements 
WHERE reference_type IN ('cylinder_transaction', 'cylinder_cancellation')
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY created_at DESC;
```

**Transactions Without Products:**
```sql
SELECT COUNT(*) as count,
       transaction_type,
       status
FROM cylinder_transactions
WHERE product_id IS NULL
GROUP BY transaction_type, status;
```

**Inventory Reconciliation:**
```sql
SELECT p.id, p.name, p.stock as current_stock,
       COUNT(CASE WHEN sm.type = 'in' THEN 1 END) as stock_in_count,
       COUNT(CASE WHEN sm.type = 'out' THEN 1 END) as stock_out_count
FROM products p
LEFT JOIN stock_movements sm ON p.id = sm.product_id 
  AND sm.reference_type LIKE 'cylinder%'
GROUP BY p.id, p.name, p.stock;
```

---

## Rollback Procedure

If issues arise:

### 1. Code Rollback
```bash
git revert <commit_hash>
```

### 2. Database Rollback
```bash
php artisan migrate:rollback --step=1
```

### 3. Data Cleanup (if needed)
```sql
-- Remove cylinder stock movements
DELETE FROM stock_movements 
WHERE reference_type IN ('cylinder_transaction', 'cylinder_cancellation');

-- Remove product_id from existing transactions (if migration already run)
UPDATE cylinder_transactions SET product_id = NULL;
```

---

## Support & Contact

### Documentation Files:
- `CYLINDER_INVENTORY_INTEGRATION.md` - This file
- `STOCK_MOVEMENT_IMPLEMENTATION.md` - Base stock movement system
- `STOCK_MOVEMENT_QUICK_REFERENCE.md` - Quick code examples

### Log Files:
- `storage/logs/laravel.log` - Check for cylinder inventory errors

### Testing:
- Use test environment first
- Create test products for cylinders
- Test all transaction types before production

---

**Implementation Date:** October 7, 2025  
**Version:** 1.0  
**Status:** Implementation Complete, Testing Pending  
**Breaking Changes:** None (backward compatible)
