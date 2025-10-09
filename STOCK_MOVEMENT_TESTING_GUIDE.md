# Stock Movement Implementation - Testing Guide

## Overview
This guide provides step-by-step instructions for testing the newly implemented stock movement tracking functionality for POS sales and sale voids.

---

## Prerequisites

Before testing, ensure:
1. ✅ Database is accessible
2. ✅ Application is running
3. ✅ You have both POS user and Admin user credentials
4. ✅ Test products exist in the inventory
5. ✅ Test customers exist in the system

---

## Test Scenario 1: POS Sale Stock Movement Creation

### Objective
Verify that stock movements are automatically created when a sale is completed through POS.

### Steps

1. **Login to POS**
   - Navigate to POS interface
   - Login with POS user credentials

2. **Create a Test Sale**
   - Add 2-3 products to cart
   - Note down:
     - Product names
     - Quantities
     - Current stock levels
   - Complete the sale (Cash payment)
   - Note the Receipt Number

3. **Verify Database Records**
   ```sql
   -- Check if stock movements were created
   SELECT * FROM stock_movements 
   WHERE reference_type = 'sale' 
   ORDER BY created_at DESC 
   LIMIT 10;
   ```

4. **Expected Results**
   - ✅ One stock movement record per product in the sale
   - ✅ `type` = 'out'
   - ✅ `reference_type` = 'sale'
   - ✅ `reference_id` = Sale ID
   - ✅ `quantity` matches sale quantity
   - ✅ `unit_price` matches sale price
   - ✅ `notes` contains "POS sale #[sale_id]"
   - ✅ `created_by` = Your user ID
   - ✅ Product stock is reduced

5. **Verify in Admin Panel**
   - Go to Admin > Stock Movements
   - Filter by "Sale" reference type
   - Find your recent movements

---

## Test Scenario 2: API Sale Stock Movement Creation

### Objective
Verify that stock movements are created for sales made through the API.

### Steps

1. **Make API Call**
   ```bash
   curl -X POST http://your-domain/api/sales \
   -H "Content-Type: application/json" \
   -H "Authorization: Bearer YOUR_TOKEN" \
   -d '{
     "cart_items": [
       {
         "id": 1,
         "quantity": 2,
         "price": 50.00
       }
     ],
     "payment_method": "cash"
   }'
   ```

2. **Check Response**
   - Note the `sale_id` from response

3. **Verify Database**
   ```sql
   SELECT * FROM stock_movements 
   WHERE reference_id = [SALE_ID] 
   AND reference_type = 'sale';
   ```

4. **Expected Results**
   - ✅ Stock movements created via API
   - ✅ Notes contain "API sale #[sale_id]"
   - ✅ All other fields match POS sale expectations

---

## Test Scenario 3: Credit Sale Stock Movement

### Objective
Verify stock movements work correctly with credit sales.

### Steps

1. **Create Credit Sale**
   - Login to POS
   - Add products to cart
   - Select "Credit" payment method
   - Enter customer details
   - Complete sale

2. **Verify Stock Movements**
   ```sql
   SELECT sm.*, s.payment_method, s.receipt_number
   FROM stock_movements sm
   JOIN sales s ON sm.reference_id = s.id
   WHERE s.payment_method = 'credit'
   ORDER BY sm.created_at DESC
   LIMIT 5;
   ```

3. **Expected Results**
   - ✅ Stock movements created normally
   - ✅ Customer balance increased
   - ✅ Sale marked as pending payment

---

## Test Scenario 4: Sale Void Stock Movement Creation

### Objective
Verify that stock movements are created when a sale is voided.

### Steps

1. **Create a Sale to Void**
   - Complete a POS sale
   - Note the Sale ID and Receipt Number
   - Note product quantities

2. **Void the Sale**
   - Navigate to Sale History
   - Find the sale you just created
   - Click "Void" button
   - Confirm the void action

3. **Verify Database**
   ```sql
   -- Check void stock movements
   SELECT * FROM stock_movements 
   WHERE reference_type = 'sale_void' 
   ORDER BY created_at DESC 
   LIMIT 10;
   ```

4. **Expected Results**
   - ✅ One stock movement per product
   - ✅ `type` = 'in' (returning to stock)
   - ✅ `reference_type` = 'sale_void'
   - ✅ `reference_id` = Original Sale ID
   - ✅ `quantity` matches original sale quantity
   - ✅ Notes contain "voided sale #[sale_id]" and receipt number
   - ✅ Product stock is increased back
   - ✅ Customer balance adjusted (if credit sale)

5. **Verify Stock Reconciliation**
   ```sql
   -- Check that stock was properly restored
   SELECT p.name, p.stock, 
          (SELECT SUM(quantity) FROM stock_movements 
           WHERE product_id = p.id AND type = 'in') as total_in,
          (SELECT SUM(quantity) FROM stock_movements 
           WHERE product_id = p.id AND type = 'out') as total_out
   FROM products p
   WHERE p.id = [PRODUCT_ID];
   ```

---

## Test Scenario 5: Serial Number Tracking

### Objective
Verify that serial numbers are properly tracked in stock movements.

### Steps

1. **Create Sale with Serialized Product**
   - Find a product with a serial number
   - Add to cart with serial number
   - Complete sale

2. **Verify Serial Numbers**
   ```sql
   SELECT sm.serial_number, si.serial_number, p.name
   FROM stock_movements sm
   JOIN sale_items si ON sm.reference_id = si.sale_id
   JOIN products p ON sm.product_id = p.id
   WHERE sm.reference_type = 'sale'
   ORDER BY sm.created_at DESC
   LIMIT 5;
   ```

3. **Expected Results**
   - ✅ Serial numbers match between stock_movements and sale_items
   - ✅ Serial numbers are preserved during void

---

## Test Scenario 6: Multiple Items Sale

### Objective
Test stock movements for sales with multiple different products.

### Steps

1. **Create Complex Sale**
   - Add 5 different products
   - Vary quantities (1, 2, 3, etc.)
   - Complete sale

2. **Verify All Movements Created**
   ```sql
   SELECT sm.*, p.name, p.stock
   FROM stock_movements sm
   JOIN products p ON sm.product_id = p.id
   WHERE sm.reference_id = [SALE_ID]
   AND sm.reference_type = 'sale'
   ORDER BY sm.id;
   ```

3. **Expected Results**
   - ✅ Exactly 5 stock movement records
   - ✅ Each matches corresponding sale item
   - ✅ All products' stock reduced correctly

---

## Test Scenario 7: User Accountability

### Objective
Verify that stock movements track which user performed the action.

### Steps

1. **Create Sales with Different Users**
   - User A creates a sale
   - User B creates a sale
   - User C voids a sale

2. **Check User Attribution**
   ```sql
   SELECT sm.*, u.name as user_name, s.receipt_number
   FROM stock_movements sm
   JOIN users u ON sm.created_by = u.id
   LEFT JOIN sales s ON sm.reference_id = s.id
   WHERE sm.reference_type IN ('sale', 'sale_void')
   ORDER BY sm.created_at DESC
   LIMIT 10;
   ```

3. **Expected Results**
   - ✅ Each movement shows correct user
   - ✅ Voided movements show who voided them
   - ✅ User accountability is maintained

---

## Test Scenario 8: Stock Reconciliation Report

### Objective
Verify ability to reconcile inventory using stock movements.

### Steps

1. **Generate Reconciliation Query**
   ```sql
   SELECT 
       p.id,
       p.name,
       p.stock as current_stock,
       COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE 0 END), 0) as total_in,
       COALESCE(SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END), 0) as total_out,
       (COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END), 0)) as calculated_stock
   FROM products p
   LEFT JOIN stock_movements sm ON p.id = sm.product_id
   GROUP BY p.id, p.name, p.stock
   ORDER BY p.name;
   ```

2. **Expected Results**
   - ✅ `calculated_stock` should match `current_stock` for all products
   - ✅ Any discrepancies indicate issues

---

## Test Scenario 9: Performance Test

### Objective
Verify that stock movement creation doesn't significantly impact sale performance.

### Steps

1. **Measure Sale Time Before**
   - Record time to complete a sale
   - Average over 10 sales

2. **Check Database Performance**
   ```sql
   EXPLAIN SELECT * FROM stock_movements 
   WHERE reference_type = 'sale' 
   AND reference_id = [SALE_ID];
   ```

3. **Expected Results**
   - ✅ Sale completion time similar to before
   - ✅ Database queries are indexed
   - ✅ No significant performance degradation

---

## Test Scenario 10: Error Handling

### Objective
Verify that failed sales don't create orphaned stock movements.

### Steps

1. **Create Failed Sale Scenario**
   - Try to sell more than available stock
   - Cancel transaction mid-process

2. **Check for Orphaned Records**
   ```sql
   -- Find stock movements without valid sales
   SELECT sm.* 
   FROM stock_movements sm
   LEFT JOIN sales s ON sm.reference_id = s.id 
   WHERE sm.reference_type = 'sale' 
   AND s.id IS NULL;
   ```

3. **Expected Results**
   - ✅ No orphaned stock movements
   - ✅ Transactions rolled back properly
   - ✅ Stock remains accurate

---

## Verification Checklist

After completing all tests, verify:

- [ ] POS sales create stock movements
- [ ] API sales create stock movements
- [ ] Cash sales create stock movements
- [ ] Credit sales create stock movements
- [ ] Sale voids create return movements
- [ ] Serial numbers are tracked
- [ ] Multiple items handled correctly
- [ ] User accountability maintained
- [ ] Stock reconciliation works
- [ ] Performance is acceptable
- [ ] Error handling works properly
- [ ] Admin reports show movements
- [ ] No data integrity issues

---

## Troubleshooting

### Issue: Stock movements not created

**Check:**
1. Verify imports in controllers
2. Check logs: `storage/logs/laravel.log`
3. Verify database permissions
4. Check transaction rollbacks

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Verify database
php artisan migrate:status
```

### Issue: Stock doesn't match movements

**Check:**
1. Look for failed transactions
2. Check for manual stock updates
3. Verify all sale types covered

**Solution:**
```sql
-- Find discrepancies
SELECT p.*, 
  (SELECT SUM(CASE WHEN type='in' THEN quantity ELSE -quantity END) 
   FROM stock_movements WHERE product_id=p.id) as calculated
FROM products p
WHERE p.stock != calculated;
```

### Issue: Duplicate movements

**Check:**
1. Double-click protection on submit buttons
2. Transaction isolation levels
3. Duplicate sale records

**Solution:**
- Add unique constraints if needed
- Implement idempotency checks

---

## Running the Verification Script

To automatically verify the implementation:

```bash
php verify_stock_movements.php
```

This will:
- Count total sales vs sales with movements
- Show recent movements
- Display statistics
- Check integrity

---

## Success Criteria

Implementation is successful when:

1. ✅ 100% of new sales create stock movements
2. ✅ 100% of new voids create stock movements
3. ✅ Stock reconciliation matches 100%
4. ✅ No performance degradation
5. ✅ No errors in logs
6. ✅ All tests pass
7. ✅ Admin can view and report on movements
8. ✅ Audit trail is complete

---

## Reporting Issues

If you find issues during testing:

1. **Capture Details:**
   - Exact steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable
   - Database state
   - Log entries

2. **Check Database:**
   ```sql
   SELECT * FROM stock_movements ORDER BY created_at DESC LIMIT 20;
   SELECT * FROM sales ORDER BY created_at DESC LIMIT 20;
   ```

3. **Document:**
   - Create detailed issue report
   - Include all relevant data
   - Propose potential fixes

---

**Testing Document Version:** 1.0  
**Last Updated:** October 7, 2025  
**Status:** Ready for Testing
