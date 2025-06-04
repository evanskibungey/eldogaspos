# POS Error Modal - When It Should Appear

## Legitimate Error Scenarios

### 1. **Stock-Related Errors**

#### a) Out of Stock
**When:** Product has 0 stock
```javascript
// Triggered in addToCart() method
if (product.stock <= 0) {
    this.showError = true;
    this.errorMessage = 'This product is out of stock.';
}
```
**How to test:**
1. Set a product's stock to 0 in the database
2. Try to add it to cart
3. Error modal should appear with "This product is out of stock."

#### b) Exceeding Available Stock
**When:** Trying to add more items than available
```javascript
// Triggered when increasing quantity
if (this.cart[existingIndex].quantity + 1 > product.stock) {
    this.showError = true;
    this.errorMessage = `Cannot add more. Only ${product.stock} available in stock.`;
}
```
**How to test:**
1. Add a product with stock = 5 to cart
2. Try to increase quantity to 6
3. Error modal should appear with "Cannot add more. Only 5 available in stock."

### 2. **Sale Processing Errors**

#### a) Validation Errors
**When:** Required fields are missing
- Credit sale without customer name
- Credit sale without customer phone
- Empty cart

**Server response:**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "customer_details.name": ["The customer details.name field is required."],
        "customer_details.phone": ["The customer details.phone field is required."]
    }
}
```

**How to test:**
1. Select "Credit" payment method
2. Leave customer name or phone empty
3. Click "Complete Sale"
4. Error modal should show validation message

#### b) Database Errors
**When:** 
- Customer creation fails
- Sale record creation fails
- Stock update fails

**How to test:**
1. Stop MySQL service in XAMPP
2. Try to complete a sale
3. Error modal: "Network error or server exception occurred."

#### c) Concurrent Stock Issues
**When:** Two users buy the same last item
```php
// In processCartItems()
if ($product->stock < $item['quantity']) {
    throw new \Exception("Insufficient stock for {$product->name}");
}
```

### 3. **Network/Connection Errors**

#### a) No Internet Connection
**When:** AJAX request fails
```javascript
catch (error) {
    console.error('Sale exception:', error);
    this.errorMessage = 'Network error or server exception occurred.';
    this.showError = true;
}
```

**How to test:**
1. Disconnect internet/stop Apache
2. Try to complete a sale
3. Error modal appears

#### b) Server Timeout
**When:** Request takes too long
- Default timeout is usually 30 seconds
- Database queries taking too long

#### c) CSRF Token Issues
**When:** Session expired
- Returns 419 status code
- Usually after being idle for too long

**How to test:**
1. Open POS dashboard
2. Wait for session to expire (2 hours by default)
3. Try to complete a sale
4. Error modal: "Network error or server exception occurred."

### 4. **Server-Side Errors**

#### a) PHP Fatal Errors
**When:** Code has bugs
- Undefined methods
- Missing classes
- Syntax errors

#### b) Missing Database Tables
**When:** Migrations not run
```php
// Would throw exception
Sale::create($saleData); // If 'sales' table doesn't exist
```

#### c) Permission Issues
**When:** Server can't write logs/files
- Storage directory not writable
- Can't create receipts

## How to Test Each Error Type

### 1. Create Test Products
```sql
-- Out of stock product
INSERT INTO products (name, category_id, sku, price, stock, min_stock, status) 
VALUES ('TEST - Out of Stock', 1, 'TEST-001', 100, 0, 5, 'active');

-- Low stock product
INSERT INTO products (name, category_id, sku, price, stock, min_stock, status) 
VALUES ('TEST - Low Stock', 1, 'TEST-002', 200, 2, 5, 'active');

-- Normal product
INSERT INTO products (name, category_id, sku, price, stock, min_stock, status) 
VALUES ('TEST - Normal Stock', 1, 'TEST-003', 300, 50, 10, 'active');
```

### 2. Test Validation Errors
```javascript
// In browser console while on POS page
// This simulates a credit sale without customer details
fetch('/pos/sales', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        cart_items: [{id: 1, quantity: 1, price: 100}],
        payment_method: 'credit',
        customer_details: {name: '', phone: ''} // Empty details
    })
}).then(r => r.json()).then(console.log);
```

### 3. Test Network Errors
```javascript
// Simulate network failure
fetch('https://invalid-domain-that-doesnt-exist.com/test')
    .catch(error => console.log('Network error:', error));
```

### 4. Monitor Errors
Add this to the POS dashboard for debugging:
```javascript
// Add to posSystem() init method
window.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
});

// Log all fetch errors
const originalFetch = window.fetch;
window.fetch = function(...args) {
    return originalFetch.apply(this, args)
        .catch(error => {
            console.error('Fetch error:', error);
            throw error;
        });
};
```

## Common Missing Elements That Trigger Errors

### 1. **Database Issues**
- Missing tables (not migrated)
- Missing columns (outdated migrations)
- Foreign key constraints failing
- Duplicate key errors

### 2. **Configuration Issues**
- Wrong database credentials in .env
- Missing APP_KEY
- Incorrect permissions
- Missing storage symlink

### 3. **Code Issues**
- Missing models or controllers
- Incorrect route names
- Missing methods
- Syntax errors in PHP

### 4. **Data Issues**
- No categories in database
- No products in database
- Missing default customer
- Corrupted data

## Error Prevention Checklist

1. **Before Going Live:**
   - [ ] All products have stock > 0
   - [ ] All products have valid prices
   - [ ] All products have categories
   - [ ] Default walk-in customer exists
   - [ ] All database tables exist
   - [ ] Storage is writable
   - [ ] CSRF token is in meta tag

2. **Regular Maintenance:**
   - [ ] Monitor low stock products
   - [ ] Check for negative stock
   - [ ] Verify database backups
   - [ ] Test sale processing weekly
   - [ ] Clear old logs
   - [ ] Update stock levels

3. **Error Handling Best Practices:**
   - Always show user-friendly messages
   - Log detailed errors server-side
   - Auto-dismiss non-critical errors
   - Provide actionable solutions
   - Test all error scenarios

## Debug Mode

To see more detailed errors, create this helper:

```javascript
// Add to POS dashboard for debugging
window.posDebug = {
    testStockError: function() {
        // Manually trigger stock error
        const posData = Alpine.$data(document.querySelector('[x-data="posSystem()"]'));
        posData.showError = true;
        posData.errorMessage = 'TEST: Stock error triggered manually';
    },
    
    testNetworkError: function() {
        // Simulate network failure
        fetch('/invalid-endpoint-test')
            .catch(error => console.log('Network test:', error));
    },
    
    showState: function() {
        // Show current Alpine.js state
        const posData = Alpine.$data(document.querySelector('[x-data="posSystem()"]'));
        console.log('Current POS State:', posData);
    }
};

// Usage in browser console:
// posDebug.testStockError()
// posDebug.showState()
```

The error modal is designed to appear only when there's a genuine problem that prevents the user from completing their action. It should NOT appear on page load unless there's an actual error to display.
