# POS Dashboard Troubleshooting Guide

## Issue: Error Modal Appearing/Disappearing on Load

### Solution Applied:
1. **Alpine.js Initialization**: Added `x-cloak` directive and proper initialization sequence
2. **Error State Management**: Added `_initialized` flag and conditional display logic
3. **Auto-close Timers**: Error messages now auto-dismiss after 3-5 seconds

## Common Issues and Solutions

### 1. **Page Shows Blank or Loading Forever**
**Check:**
- Browser console for JavaScript errors (F12)
- Network tab for failed requests
- PHP error logs in `storage/logs/laravel.log`

**Fix:**
```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

### 2. **Products Not Showing**
**Check:**
- Database has products: `php artisan tinker` then `App\Models\Product::count()`
- Products are active: `App\Models\Product::where('status', 'active')->count()`

**Fix:**
```bash
php artisan db:seed --class=QuickFixSeeder
```

### 3. **Images Not Loading**
**Check:**
- Placeholder image exists at `public/images/placeholder.jpg`
- Storage link is created: `php artisan storage:link`

**Fix:**
1. Run the create_placeholder.php script: `php public/create_placeholder.php`
2. Or manually add a placeholder.jpg image to public/images/

### 4. **Sale Processing Errors**
**Check:**
- CSRF token is present in meta tag
- Route exists: `php artisan route:list | grep "pos.sales.store"`
- Database tables exist: `php artisan migrate:status`

**Fix:**
```bash
php artisan migrate:fresh --seed
```

### 5. **Credit Payment Not Working**
**Check:**
- Customers table exists
- Customer validation rules in controller

**Fix:**
- Ensure phone numbers are in correct format
- Check browser console for validation errors

## Debugging Steps

### 1. **Enable Debug Mode**
In `.env` file:
```
APP_DEBUG=true
APP_ENV=local
```

### 2. **Check Browser Console**
Expected output:
```
Initializing POS System...
POS System Initialized
```

### 3. **Test Basic Functionality**
1. Load page - no errors should appear
2. Search for products
3. Filter by category
4. Add product to cart
5. Complete a cash sale
6. Complete a credit sale

### 4. **Monitor Network Requests**
In browser DevTools Network tab:
- Check for 404 errors
- Check for 500 server errors
- Verify AJAX requests to `/pos/sales` endpoint

## Quick Fixes

### Clear Everything:
```bash
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan optimize:clear
```

### Reset Database:
```bash
php artisan migrate:fresh
php artisan db:seed
php artisan db:seed --class=QuickFixSeeder
```

### Fix Permissions (Linux/Mac):
```bash
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

### Fix Permissions (Windows):
Right-click on storage and bootstrap/cache folders → Properties → Security → Edit → Give full control to all users

## Still Having Issues?

1. Check Laravel log: `tail -f storage/logs/laravel.log`
2. Check PHP error log in XAMPP
3. Test with different browser
4. Try incognito/private mode
5. Disable browser extensions

## Contact Support
If issues persist after trying all solutions, collect:
- Screenshot of error
- Browser console output
- Network tab screenshot
- Laravel log entries
