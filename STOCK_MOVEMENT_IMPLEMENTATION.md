# Stock Movement Implementation Documentation

## Overview
This document outlines the implementation of comprehensive stock movement tracking for POS sales and sale voids in the Eldogas POS system.

## Changes Made

### 1. POS Sales Stock Movement Tracking

#### Files Modified:
- `app/Http/Controllers/Pos/SaleController.php`
- `app/Http/Controllers/API/SaleController.php`

#### Implementation Details:

**Added Import:**
```php
use App\Models\StockMovement;
```

**Enhanced `processCartItems()` Method:**
When a sale is processed, the system now:
1. Creates a `SaleItem` record
2. Decrements the product stock
3. **NEW:** Creates a `StockMovement` record with the following details:
   - `type`: 'out' (inventory leaving)
   - `quantity`: Items sold
   - `unit_price`: Price per unit
   - `reference_type`: 'sale'
   - `reference_id`: Sale ID
   - `notes`: Descriptive text indicating POS or API sale
   - `serial_number`: Product serial number (if available)
   - `created_by`: Authenticated user ID

**Benefits:**
- Complete audit trail of all inventory deductions through sales
- Ability to track which sale caused each inventory reduction
- Serial number tracking for individual items
- User accountability for each transaction

---

### 2. Sale Void Stock Movement Tracking

#### Files Modified:
- `app/Http/Controllers/Pos/SaleController.php`
- `app/Http/Controllers/API/SaleController.php`

#### Implementation Details:

**Enhanced `void()` Method:**
When a sale is voided, the system now:
1. Updates sale status to 'voided'
2. Increments product stock for each item
3. **NEW:** Creates a `StockMovement` record for each returned item with:
   - `type`: 'in' (inventory returning)
   - `quantity`: Items being returned
   - `unit_price`: Original unit price
   - `reference_type`: 'sale_void'
   - `reference_id`: Original sale ID
   - `notes`: Descriptive text including receipt number and void reason (API only)
   - `serial_number`: Product serial number (if available)
   - `created_by`: User who voided the sale
4. Adjusts customer balance (for credit sales)

**Benefits:**
- Complete audit trail of voided sales and returned inventory
- Ability to track why inventory was returned to stock
- Link between voided sale and stock restoration
- Accountability for who voided each sale

---

## Database Schema Reference

### StockMovement Table Structure:
```
- id (primary key)
- product_id (foreign key to products)
- type (enum: 'in', 'out')
- quantity (integer)
- unit_price (decimal, nullable)
- reference_type (string, nullable) - e.g., 'sale', 'sale_void', 'manual_adjustment', 'initial'
- reference_id (unsigned big integer, nullable)
- notes (string, nullable)
- serial_number (string, nullable)
- created_by (foreign key to users, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## Reference Type Values

### Current Reference Types in System:
1. **'initial'** - Stock added when product is first created
2. **'adjustment'** - Stock adjusted during product update
3. **'manual_adjustment'** - Stock manually adjusted via admin panel
4. **'sale'** - Stock deducted from a sale transaction (NEW)
5. **'sale_void'** - Stock returned from a voided sale (NEW)

---

## Usage Examples

### Querying Stock Movements for a Sale:
```php
$saleStockMovements = StockMovement::where('reference_type', 'sale')
    ->where('reference_id', $saleId)
    ->get();
```

### Querying Stock Movements for a Voided Sale:
```php
$voidStockMovements = StockMovement::where('reference_type', 'sale_void')
    ->where('reference_id', $saleId)
    ->get();
```

### Getting All Movements for a Product:
```php
$productMovements = StockMovement::where('product_id', $productId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Audit Report - Stock Movements by User:
```php
$userMovements = StockMovement::where('created_by', $userId)
    ->with('product')
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## Testing Recommendations

### Test Case 1: POS Sale Creates Stock Movement
1. Log in to POS
2. Add products to cart
3. Complete a sale
4. Verify stock movements created:
   - Check `stock_movements` table has new records
   - Confirm `type` = 'out'
   - Confirm `reference_type` = 'sale'
   - Verify product stock was decremented

### Test Case 2: Sale Void Creates Stock Movement
1. Access a completed sale
2. Void the sale
3. Verify stock movements created:
   - Check `stock_movements` table has new records
   - Confirm `type` = 'in'
   - Confirm `reference_type` = 'sale_void'
   - Verify product stock was incremented

### Test Case 3: Serial Number Tracking
1. Create sale with products that have serial numbers
2. Verify serial numbers are recorded in stock movements
3. Void the sale
4. Verify serial numbers are preserved in void stock movements

### Test Case 4: API Sale Stock Movements
1. Use API endpoint to create a sale
2. Verify stock movements are created via API
3. Verify movements have appropriate notes distinguishing API vs POS sales

---

## Impact Analysis

### Database Impact:
- **New Records:** Each sale item will create one stock movement record
- **New Records:** Each voided sale item will create one additional stock movement record
- **Storage:** Minimal - stock movements are relatively small records
- **Performance:** Negligible impact as movements are created within existing transactions

### Reporting Capabilities Enhanced:
1. Complete inventory audit trail
2. Ability to reconcile inventory changes
3. Track user accountability for stock changes
4. Analyze sales patterns with inventory movements
5. Identify voided sales impact on inventory

### Compliance Benefits:
- Full traceability of inventory movements
- Audit-ready inventory records
- User accountability tracking
- Ability to generate compliance reports

---

## Future Enhancements

### Potential Additions:
1. Stock movement reports in admin dashboard
2. Real-time stock movement alerts for large quantities
3. Integration with cylinder transactions
4. Automated stock movement reconciliation
5. Stock movement analytics and trends

---

## Rollback Procedure

If issues arise, to rollback these changes:

1. **Database:** No schema changes required - rollback is safe
2. **Code:** Revert the following files to previous versions:
   - `app/Http/Controllers/Pos/SaleController.php`
   - `app/Http/Controllers/API/SaleController.php`
3. **Cleanup:** Optionally delete stock movements with `reference_type` = 'sale' or 'sale_void' if needed

---

## Maintenance Notes

### Monitoring:
- Monitor `stock_movements` table growth
- Verify stock movements are being created correctly
- Check for any failed transactions that didn't create movements

### Regular Checks:
- Reconcile stock movements with actual inventory
- Audit stock movements for consistency
- Review logs for any errors in stock movement creation

---

## Contact & Support

For issues or questions regarding this implementation:
- Review logs in `storage/logs/laravel.log`
- Check database for orphaned stock movements
- Verify user permissions are correctly set

---

**Implementation Date:** October 7, 2025  
**Version:** 1.0  
**Status:** Completed
