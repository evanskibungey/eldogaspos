# Cylinder Inventory Integration - Deployment Guide

## Pre-Deployment Checklist

### 1. Backup Everything
```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup codebase
tar -czf codebase_backup_$(date +%Y%m%d).tar.gz /path/to/eldogaspos
```

### 2. Review Changes
- [ ] Read CYLINDER_INVENTORY_INTEGRATION.md
- [ ] Review CYLINDER_INTEGRATION_SUMMARY.md
- [ ] Check all modified files
- [ ] Understand business logic flow

### 3. Prepare Test Data
- [ ] Identify or create test cylinder products
- [ ] Have test customer accounts ready
- [ ] Prepare test scenarios list

---

## Deployment Steps

### Step 1: Code Deployment

```bash
# Pull latest code
cd /path/to/eldogaspos
git pull origin main

# Or upload files if not using git
# - Upload modified controller
# - Upload modified models
# - Upload new migration
# - Upload verification scripts
```

### Step 2: Run Migration

```bash
# Run the migration
php artisan migrate

# Expected output:
# Migrating: 2025_10_07_000001_add_product_id_to_cylinder_transactions
# Migrated:  2025_10_07_000001_add_product_id_to_cylinder_transactions
```

### Step 3: Verify Migration

```bash
# Check if column was added
php artisan tinker

# In tinker:
Schema::hasColumn('cylinder_transactions', 'product_id');
# Should return: true

# Exit tinker
exit
```

### Step 4: Clear Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimizations
php artisan config:cache
php artisan route:cache
```

### Step 5: Run Verification Script

```bash
php verify_cylinder_integration.php
```

**Expected Output:**
- Total transactions count
- Products linked count
- Stock movements statistics
- Integrity checks
- No critical errors

---

## Post-Deployment Testing

### Test 1: Drop-Off Transaction with Product

**Steps:**
1. Login to admin/POS
2. Navigate to Cylinders > Create Transaction
3. Fill in details:
   - Customer: Select test customer
   - **Product: Select a cylinder product** ← NEW
   - Size: 13kg
   - Type: LPG  
   - Transaction Type: Drop-Off
   - Amount: 1500
   - Payment: Paid
4. Save transaction
5. Note the transaction ID and product stock level

**Verify:**
```sql
-- Check transaction created
SELECT * FROM cylinder_transactions WHERE id = [ID];

-- Check product stock (should NOT have changed yet)
SELECT stock FROM products WHERE id = [PRODUCT_ID];

-- Check no stock movements yet
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_transaction' 
AND reference_id = [ID];
```

6. Complete the transaction (customer collects)
7. Check product stock again

**Verify:**
```sql
-- Product stock should be reduced by 1
SELECT stock FROM products WHERE id = [PRODUCT_ID];

-- Stock movement should exist
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_transaction' 
AND reference_id = [ID];
```

**Expected Result:**
- ✅ Transaction created successfully
- ✅ No inventory change on creation
- ✅ Inventory reduced by 1 on completion
- ✅ Stock movement created
- ✅ Movement notes are descriptive

---

### Test 2: Advance Collection with Product

**Steps:**
1. Create advance collection transaction
2. Link to product
3. Save transaction

**Verify Immediately:**
```sql
-- Product stock should be reduced immediately
SELECT stock FROM products WHERE id = [PRODUCT_ID];

-- Stock movement should exist immediately
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_transaction' 
AND reference_id = [ID];
```

4. Later, complete transaction (customer returns empty)

**Verify:**
```sql
-- Product stock should remain the same (no additional deduction)
SELECT stock FROM products WHERE id = [PRODUCT_ID];

-- Should still have only one stock movement
SELECT COUNT(*) FROM stock_movements 
WHERE reference_type = 'cylinder_transaction' 
AND reference_id = [ID];
```

**Expected Result:**
- ✅ Inventory reduced immediately on creation
- ✅ Stock movement created immediately
- ✅ No additional deduction on completion
- ✅ Only one stock movement exists

---

### Test 3: Transaction Without Product

**Steps:**
1. Create any transaction type
2. **Do NOT select a product**
3. Save and complete transaction

**Verify:**
```sql
-- Transaction should work normally
SELECT * FROM cylinder_transactions WHERE id = [ID];

-- No stock movements should be created
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_transaction' 
AND reference_id = [ID];
```

**Expected Result:**
- ✅ Transaction works normally
- ✅ No errors
- ✅ No inventory changes
- ✅ No stock movements
- ✅ Backward compatible

---

### Test 4: Transaction Cancellation

**Steps:**
1. Create advance collection with product (inventory deducted)
2. Note product stock level
3. Cancel the transaction

**Verify:**
```sql
-- Product stock should be restored
SELECT stock FROM products WHERE id = [PRODUCT_ID];

-- Cancellation stock movement should exist
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_cancellation' 
AND reference_id = [ID];
```

**Expected Result:**
- ✅ Inventory restored to original level
- ✅ Cancellation stock movement created
- ✅ Transaction marked as cancelled
- ✅ Notes describe the restoration

---

### Test 5: Insufficient Stock

**Steps:**
1. Find or create a product with stock = 0
2. Try to create advance collection with that product
3. Attempt to save

**Expected Result:**
- ✅ Error message: "Insufficient stock for [Product]. Available: 0"
- ✅ Transaction NOT created
- ✅ No stock movements
- ✅ User can correct and retry

---

### Test 6: Quick Complete (POS)

**Steps:**
1. Create drop-off with product via POS
2. Use quick complete button
3. Verify inventory

**Expected Result:**
- ✅ Quick complete works
- ✅ Inventory deducted
- ✅ Stock movement created
- ✅ Fast and efficient

---

## Monitoring After Deployment

### First 24 Hours

**Check Every 2 Hours:**
```bash
# Run verification
php verify_cylinder_integration.php

# Check logs
tail -f storage/logs/laravel.log | grep -i cylinder
```

**Look For:**
- Error messages
- Failed transactions
- Inventory discrepancies
- User complaints

### First Week

**Daily Checks:**
```bash
# Run full verification
php verify_cylinder_integration.php

# Check for errors in logs
grep -i "error.*cylinder" storage/logs/laravel.log

# Verify stock accuracy
```

**SQL Monitoring:**
```sql
-- Daily summary
SELECT 
    DATE(created_at) as date,
    COUNT(*) as transactions,
    SUM(CASE WHEN product_id IS NOT NULL THEN 1 ELSE 0 END) as with_products,
    SUM(CASE WHEN product_id IS NULL THEN 1 ELSE 0 END) as without_products
FROM cylinder_transactions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Stock movement summary
SELECT 
    reference_type,
    COUNT(*) as count,
    SUM(quantity) as total_quantity
FROM stock_movements
WHERE reference_type LIKE 'cylinder%'
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY reference_type;
```

---

## User Training

### For Staff Creating Transactions

**Key Points:**
1. **Product field is optional** - Old workflow still works
2. **Link products when possible** - Better inventory tracking
3. **Check stock before creating** - Advance collections check automatically
4. **Complete transactions properly** - Inventory updates on completion

**Quick Guide:**
```
Creating Transaction:
1. Enter customer details
2. Select cylinder size
3. **NEW: Select product (optional but recommended)**
4. Enter amount
5. Choose transaction type
6. Save

Drop-Off: Inventory deducted when customer collects
Advance Collection: Inventory deducted immediately
```

### For Admins

**Dashboard Monitoring:**
- Check inventory levels daily
- Review stock movements regularly
- Investigate any discrepancies
- Monitor cancellation patterns

**Monthly Tasks:**
- Run reconciliation reports
- Review cylinder product stock
- Analyze usage patterns
- Update reorder levels

---

## Troubleshooting Guide

### Problem: Migration Fails

**Error:** "Column already exists"
```bash
# Check if column exists
php artisan tinker
Schema::hasColumn('cylinder_transactions', 'product_id');
exit

# If true, migration already ran
# Mark as migrated:
php artisan migrate:status
```

**Solution:** Migration may have already run. Verify and continue.

---

### Problem: Inventory Not Deducting

**Symptoms:**
- Transaction completes successfully
- Product stock unchanged
- No error messages

**Debug Steps:**
```php
// Check in tinker
php artisan tinker

$transaction = App\Models\CylinderTransaction::find([ID]);
$transaction->product_id; // Should have value
$transaction->status; // Should be 'completed' for drop-offs
$transaction->collection_date; // Should have date for drop-offs

// Check if method is being called
Log::info('Testing inventory deduction');
```

**Check Logs:**
```bash
grep "Inventory deducted" storage/logs/laravel.log
```

**Common Causes:**
- Product ID not set
- Transaction not completed properly
- Error in deduction logic
- Database transaction rolled back

---

### Problem: Stock Movement Not Created

**Symptoms:**
- Inventory deducted correctly
- No stock movement record

**Debug:**
```sql
-- Check if movement exists
SELECT * FROM stock_movements 
WHERE reference_type = 'cylinder_transaction'
AND reference_id = [TRANSACTION_ID];

-- Check for errors
SELECT * FROM stock_movements 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;
```

**Check Code:**
- Verify StockMovement import exists
- Check fillable array includes all fields
- Verify database transaction completes
- Check logs for errors

---

### Problem: Insufficient Stock Error When Stock Available

**Symptoms:**
- Product shows available stock
- Error says insufficient stock
- Transaction fails to create

**Debug:**
```php
php artisan tinker

$product = App\Models\Product::find([PRODUCT_ID]);
echo "Stock: " . $product->stock;

// Try manual deduction
$product->decrement('stock', 1);
echo "New Stock: " . $product->fresh()->stock;
```

**Possible Causes:**
- Concurrent transactions
- Cached stock values
- Database locking issues

**Solution:**
```bash
# Clear cache
php artisan cache:clear

# Verify actual database value
mysql> SELECT stock FROM products WHERE id = [ID];
```

---

### Problem: Inventory Restored Incorrectly

**Symptoms:**
- Cancel transaction
- Stock increases by wrong amount
- Multiple restoration records

**Debug:**
```sql
-- Check restoration logic
SELECT * FROM stock_movements
WHERE reference_type = 'cylinder_cancellation'
AND reference_id = [TRANSACTION_ID]
ORDER BY created_at;

-- Check if multiple restorations
SELECT COUNT(*) FROM stock_movements
WHERE reference_type = 'cylinder_cancellation'
AND reference_id = [TRANSACTION_ID];
```

**Fix:**
- Check `restoreInventoryForCylinder()` logic
- Verify transaction status checks
- Ensure method called only once
- Review cancellation flow

---

## Rollback Procedure

### When to Rollback

- Critical bugs affecting business
- Data integrity issues
- Performance problems
- User workflow disruption

### Rollback Steps

**Step 1: Stop New Transactions**
```php
// Temporarily disable cylinder creation
// Add to CylinderController::store()
return back()->with('error', 'Cylinder system temporarily unavailable');
```

**Step 2: Rollback Database**
```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Verify rollback
php artisan tinker
Schema::hasColumn('cylinder_transactions', 'product_id');
# Should return: false
```

**Step 3: Rollback Code**
```bash
# Revert to previous version
git revert [commit_hash]

# Or restore backup
tar -xzf codebase_backup_[date].tar.gz
```

**Step 4: Clean Data**
```sql
-- Remove cylinder stock movements
DELETE FROM stock_movements 
WHERE reference_type IN ('cylinder_transaction', 'cylinder_cancellation');

-- Verify removal
SELECT COUNT(*) FROM stock_movements 
WHERE reference_type LIKE 'cylinder%';
-- Should return: 0
```

**Step 5: Clear Caches**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Step 6: Verify System**
```bash
# Test basic functionality
# Create transaction without product
# Complete transaction
# Verify no errors
```

---

## Success Criteria

### Deployment is Successful When:

- [ ] Migration completed without errors
- [ ] All 6 test scenarios pass
- [ ] No errors in logs
- [ ] Users can create transactions
- [ ] Inventory updates correctly
- [ ] Stock movements created properly
- [ ] Cancellations work correctly
- [ ] Performance is acceptable
- [ ] Verification script shows no issues

### Key Metrics to Monitor:

**Week 1:**
- Transactions created: [Target]
- Transactions with products: [Target %]
- Inventory accuracy: 100%
- Error rate: <0.1%
- User complaints: 0

**Week 2-4:**
- Continued monitoring
- User feedback collection
- Performance optimization
- Training completion

---

## Post-Deployment Optimization

### After 1 Week

**Review:**
- User adoption rate
- Product linking percentage
- Error patterns
- Performance metrics

**Optimize:**
```sql
-- Add indexes if needed
CREATE INDEX idx_cylinder_product_status 
ON cylinder_transactions(product_id, status);

-- Analyze query performance
EXPLAIN SELECT ... ;
```

### After 1 Month

**Actions:**
- Full reconciliation report
- User feedback review
- Feature enhancement planning
- Documentation updates

---

## Support Contacts

### During Deployment
- **Technical Lead:** [Name/Contact]
- **Database Admin:** [Name/Contact]
- **On-Call Developer:** [Name/Contact]

### After Deployment
- **System Issues:** [Support Channel]
- **User Questions:** [Training Lead]
- **Bug Reports:** [Issue Tracker]

---

## Deployment Timeline

### Recommended Schedule

**Day 1 (Deployment Day):**
- 08:00 - Take backups
- 08:30 - Deploy code
- 09:00 - Run migration
- 09:30 - Run verification
- 10:00 - Perform testing
- 11:00 - User training
- 12:00 - Monitor closely
- 17:00 - End of day review

**Day 2-7:**
- Daily monitoring
- User support
- Issue resolution
- Performance tracking

**Week 2:**
- Feedback collection
- Optimization
- Extended training

**Week 3-4:**
- Stabilization
- Documentation updates
- Feature requests review

---

## Emergency Contacts

### Critical Issues
- **Database Down:** Immediate rollback
- **Data Loss:** Restore from backup
- **System Unusable:** Activate rollback procedure

### Contact Tree
1. On-call developer (immediate)
2. Technical lead (within 1 hour)
3. Management (if business critical)

---

## Final Checklist

### Before Going Live
- [ ] Backups completed
- [ ] Code deployed
- [ ] Migration run successfully
- [ ] Verification passed
- [ ] All tests completed
- [ ] Caches cleared
- [ ] Documentation reviewed
- [ ] Team briefed
- [ ] Support ready
- [ ] Monitoring active

### Go/No-Go Decision
- [ ] All tests passed
- [ ] No critical errors
- [ ] Team confident
- [ ] Rollback plan ready
- [ ] Support available

**Decision:** GO / NO-GO

**Approved By:** _______________ Date: ___________

---

**Document Version:** 1.0  
**Created:** October 7, 2025  
**Last Updated:** October 7, 2025  
**Status:** Ready for Deployment
