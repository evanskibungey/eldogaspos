# POS Error Modal - Complete Guide

## When the Error Modal SHOULD Appear

The error modal is designed to appear **only** when there's a genuine problem that prevents the user from completing their action. It should **NOT** appear on page load.

## All Error Scenarios

### 1. **Stock-Related Errors** ✅

#### Out of Stock Error
- **Trigger**: Trying to add a product with 0 stock
- **Message**: "This product is out of stock."
- **Duration**: Auto-closes after 3 seconds

#### Insufficient Stock Error  
- **Trigger**: Trying to add more quantity than available
- **Message**: "Cannot add more. Only X available in stock."
- **Duration**: Auto-closes after 3 seconds

### 2. **Validation Errors** ✅

#### Missing Customer Details (Credit Sale)
- **Trigger**: Credit payment without customer name or phone
- **Message**: "The customer details.name field is required." / "The customer details.phone field is required."
- **Duration**: Stays until user clicks Close

#### Empty Cart
- **Trigger**: Trying to complete sale with no items
- **Message**: "The cart items field is required."
- **Duration**: Stays until user clicks Close

### 3. **Network/Connection Errors** ✅

#### No Internet Connection
- **Trigger**: Network request fails
- **Message**: "Network error or server exception occurred."
- **Duration**: Auto-closes after 5 seconds

#### Server Timeout
- **Trigger**: Request takes longer than 30 seconds
- **Message**: "Network error or server exception occurred."
- **Duration**: Auto-closes after 5 seconds

#### CSRF Token Expired
- **Trigger**: Session expired (usually after 2 hours)
- **Message**: "Network error or server exception occurred."
- **Duration**: Auto-closes after 5 seconds

### 4. **Server-Side Errors** ✅

#### Database Errors
- **Trigger**: MySQL is down, table missing, etc.
- **Message**: "Network error or server exception occurred."
- **Duration**: Auto-closes after 5 seconds

#### PHP Errors
- **Trigger**: Code exceptions, missing files, etc.
- **Message**: Server error message or "Network error or server exception occurred."
- **Duration**: Auto-closes after 5 seconds

## Testing Error Scenarios

### Quick Test Commands

1. **Create Test Products**:
```bash
php artisan pos:test-errors stock
```

2. **View Validation Tests**:
```bash
php artisan pos:test-errors validate
```

3. **View Network Tests**:
```bash
php artisan pos:test-errors network
```

4. **Clean Test Data**:
```bash
php artisan pos:test-errors clean
```

### Test Error Page
Access the error testing page at:
```
http://sales.eldogas.co.ke/pos/test-errors
```

### Manual Testing in Browser Console

Test stock error:
```javascript
// Get Alpine.js component
const pos = Alpine.$data(document.querySelector('[x-data="posSystem()"]'));

// Trigger stock error
pos.showError = true;
pos.errorMessage = 'TEST: Manual stock error';
```

Test network error:
```javascript
// Make invalid request
fetch('/invalid-endpoint')
  .catch(error => console.log('Network error:', error));
```

Test validation error:
```javascript
fetch('/pos/sales', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        cart_items: [],
        payment_method: 'credit',
        customer_details: {name: '', phone: ''}
    })
}).then(r => r.json()).then(console.log);
```

## What Could Be Missing to Trigger Errors

### 1. **Database Issues**
- Sales table not created → Run `php artisan migrate`
- No products in database → Run `php artisan db:seed`
- No categories → Create at least one category
- Missing walk-in customer → Run the QuickFixSeeder

### 2. **Configuration Issues**
- Wrong database credentials in `.env`
- Missing `APP_KEY` → Run `php artisan key:generate`
- Debug mode off → Set `APP_DEBUG=true` in `.env`

### 3. **Permission Issues**
- Storage not writable → `chmod -R 777 storage`
- Can't create logs → Check folder permissions

### 4. **Code Issues**
- Route not defined → Check `php artisan route:list`
- Controller missing → Check file exists
- Model missing → Check app/Models directory

## Error Prevention Checklist

Before going live, ensure:
- [ ] All products have stock > 0
- [ ] All products have valid prices
- [ ] All products have categories
- [ ] Default walk-in customer exists
- [ ] All database tables migrated
- [ ] Storage directory is writable
- [ ] `.env` file configured correctly
- [ ] CSRF token in page meta tag

## Debugging Tips

1. **Check Browser Console** (F12):
   - Look for red error messages
   - Check Network tab for failed requests
   - Look for 404, 419, or 500 status codes

2. **Check Laravel Log**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Enable Debug Mode**:
   In `.env`:
   ```
   APP_DEBUG=true
   APP_ENV=local
   ```

4. **Test Specific Scenarios**:
   - Add out-of-stock product
   - Try to exceed available stock
   - Complete credit sale without details
   - Stop MySQL and try a sale

## Summary

The error modal should only appear when:
1. User tries to do something impossible (add out-of-stock item)
2. User forgets required information (customer details for credit)
3. System has a problem (network down, database error)

It should NOT appear:
- On page load
- Without a user action
- With empty error message

All errors now auto-dismiss after 3-5 seconds (except validation errors that need user attention).
