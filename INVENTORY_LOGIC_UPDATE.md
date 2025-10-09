# Cylinder Transaction Inventory Logic - Updated

## New Inventory Management Flow

### When Transaction is Created (Both Drop-off & Advance Collection)
✅ **Inventory is IMMEDIATELY deducted**
- This reflects reality: once a cylinder is allocated to a transaction, it's no longer available for sale
- Applies to BOTH transaction types:
  - **Drop-off**: Customer leaves empty, stock reserved for refilling
  - **Advance Collection**: Customer takes filled cylinder immediately

### When Transaction is Completed
✅ **No additional inventory changes**
- **Drop-off completion**: Customer collects - inventory already deducted
- **Advance collection completion**: Customer returns empty - inventory already deducted

### When Transaction is Cancelled/Deleted
✅ **Inventory is RESTORED**
- Stock is returned to available inventory
- Only applies to non-completed transactions
- Stock movement recorded with "restored" note

## Benefits of This Approach

1. **Accurate Real-Time Inventory**
   - Stock levels always reflect available inventory for sale
   - Prevents overselling committed stock

2. **Better Stock Management**
   - Reserved cylinders are tracked properly
   - Clear visibility of available vs committed stock

3. **Clearer Business Logic**
   - Simple rule: Create = Deduct, Cancel = Restore
   - No complex conditional logic based on transaction type

## Stock Movement Records

### On Transaction Creation:
```
Stock deducted - Cylinder drop-off transaction #123 created 
(Ref: CYL-2025-001, Customer: John Doe, Product: 13kg Refill, Qty: 3)
```

### On Transaction Cancellation:
```
Stock restored - Cylinder drop-off transaction #123 cancelled 
(Ref: CYL-2025-001, Customer: John Doe, Product: 13kg Refill, Qty: 3)
```

## Example Scenarios

### Scenario 1: Drop-off Transaction
1. **Create**: Customer drops 3 empty 13kg cylinders → Stock deducted: -3
2. **Complete**: Customer collects refilled cylinders → No inventory change
3. Total impact: -3 units

### Scenario 2: Advance Collection
1. **Create**: Customer takes 2 filled 6kg cylinders → Stock deducted: -2
2. **Complete**: Customer returns empty cylinders → No inventory change
3. Total impact: -2 units

### Scenario 3: Cancelled Transaction
1. **Create**: Transaction for 5 units created → Stock deducted: -5
2. **Cancel**: Transaction cancelled before completion → Stock restored: +5
3. Total impact: 0 units

## Migration Notes

If you have existing transactions created before this update:
- Their inventory was already properly managed
- No data migration needed
- New logic applies to all new transactions going forward
