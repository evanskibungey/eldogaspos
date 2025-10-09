# Cylinder Transaction System - Multi-Product Implementation

## Overview
This document explains how the cylinder transaction system has been redesigned to handle multiple products with quantities in a single transaction.

## Database Changes

### New Table: `cylinder_transaction_items`
Stores individual product lines for each transaction:
- `cylinder_transaction_id` - Links to parent transaction
- `product_id` - The product being transacted
- `quantity` - Number of units
- `unit_price` - Price per unit at time of transaction
- `subtotal` - Calculated (quantity × unit_price)

### Modified Table: `cylinder_transactions`
- Removed `product_id` (now uses items table)
- Made `cylinder_size` and `cylinder_type` nullable (for reference only)
- `amount` now calculated from sum of item subtotals

## Models

### CylinderTransactionItem
- Relationship to CylinderTransaction (belongsTo)
- Relationship to Product (belongsTo)
- Method: `calculateSubtotal()`

### CylinderTransaction
- Relationship to items (hasMany CylinderTransactionItem)
- Relationship to products (belongsToMany through items)
- Method: `calculateTotalFromItems()` - Sum of all item subtotals
- Method: `getTotalQuantity()` - Sum of all item quantities

## Controller Changes

### Store Method Flow:
1. Validate transaction data and items array
2. Create/find customer
3. Create cylinder transaction
4. Loop through items array:
   - Validate stock availability
   - Create CylinderTransactionItem
   - Calculate subtotal
5. Calculate total amount from items
6. Update transaction amount
7. For advance collection: Deduct inventory immediately
8. Handle customer balance if pending payment

### Complete Method Flow:
1. For drop-off: Deduct inventory for all items
2. For advance collection: Inventory already deducted
3. Update transaction status
4. Handle customer balance adjustments
5. Create stock movements for each item

## UI Changes

### Create Transaction Page:
- Product selection with quantity input
- Add/Remove product rows dynamically
- Real-time total calculation
- Visual cart-like interface
- Stock availability warnings

### Transaction Details:
- Display all products in a table
- Show quantities and subtotals
- Total summary at bottom

## Request Format

### Creating Transaction:
```json
{
  "transaction_type": "drop_off",
  "customer_id": 1,
  "payment_status": "paid",
  "deposit_amount": 500,
  "notes": "Customer note",
  "items": [
    {"product_id": 1, "quantity": 3},
    {"product_id": 2, "quantity": 2},
    {"product_id": 3, "quantity": 1}
  ]
}
```

## Benefits
1. **Flexibility**: Handle complex real-world scenarios
2. **Accuracy**: Track exact products and quantities
3. **Inventory Control**: Precise stock management
4. **Better Reporting**: Detailed product-level insights
5. **Scalability**: Easy to extend with discounts, taxes, etc.

## Migration Steps
1. Run: `php artisan migrate` (creates items table)
2. Existing transactions will need manual data migration if any
3. Update UI to use new multi-product interface
4. Test thoroughly before production deployment
