# EldoGas POS - Quick Offline Setup Guide

## 🚀 Quick Start

### Step 1: Run Diagnostic
```bash
php offline_diagnostic.php
```

### Step 2: Fix Any Issues
```bash
php artisan pos:fix-offline-sync
```

### Step 3: Enable/Disable Offline Mode

**For Development (Recommended):**
```bash
php artisan pos:offline-mode disable
php artisan config:clear
```

**For Production:**
```bash
php artisan pos:offline-mode enable
php artisan config:clear
```

### Step 4: Build Assets
```bash
npm run build
```

### Step 5: Test
```bash
php test_offline_sync.php
```

## 📋 Available Commands

### Offline Mode Management
```bash
# Check current status
php artisan pos:offline-mode status

# Enable offline mode
php artisan pos:offline-mode enable

# Disable offline mode
php artisan pos:offline-mode disable
```

### Fix Issues
```bash
# Check for issues only
php artisan pos:fix-offline-sync --check-only

# Check and fix issues
php artisan pos:fix-offline-sync
```

## 🧪 Testing Offline Mode

### 1. Enable Offline Mode
```bash
php artisan pos:offline-mode enable
php artisan config:clear
```

### 2. Access POS
Navigate to: `http://localhost/eldogaspos/public/pos/dashboard`

### 3. Simulate Offline
1. Open Chrome DevTools (F12)
2. Go to Network tab
3. Click throttling dropdown
4. Select "Offline"
5. Try processing a sale
6. Set back to "Online"
7. Watch automatic sync

### 4. Monitor Sync
- Click "Sync" button in top navigation
- View pending operations
- Force manual sync if needed

## 🔍 Troubleshooting

### Common Issues

#### 1. "window.offlinePOS is undefined"
**Cause:** Offline mode disabled or assets not built
**Fix:**
```bash
php artisan pos:offline-mode enable
php artisan config:clear
npm run build
```

#### 2. "Column 'user_id' cannot be null" during sync
**Cause:** User ID not included in offline sync data
**Fix:** The system now automatically includes the user ID when processing offline sales. For API sync, ensure `user_id` is included in the request payload.

#### 3. Service Worker Not Loading
**Cause:** Not using HTTPS or localhost
**Fix:** Access via `localhost` or enable HTTPS

#### 4. No Sync Happening
**Cause:** No internet or offline mode disabled
**Fix:** Check connection and offline mode status

#### 5. Products Not Loading Offline
**Cause:** Products not cached
**Fix:** Load POS while online first to cache products

## 📊 Monitoring

### Check Sync Status
```bash
# Via artisan
php artisan pos:offline-mode status

# Via diagnostic
php offline_diagnostic.php

# Via test suite
php test_offline_sync.php
```

### Database Queries
```sql
-- Check pending syncs
SELECT * FROM offline_sync_logs WHERE sync_status = 'pending';

-- Check failed syncs
SELECT * FROM offline_sync_logs WHERE sync_status = 'failed';

-- Check offline sales
SELECT * FROM sales WHERE is_offline_sync = 1;
```

## 🎯 Best Practices

### Development
1. Keep offline mode **DISABLED** during development
2. Test online functionality first
3. Enable offline mode only for testing offline features

### Production
1. Enable offline mode
2. Test thoroughly in staging
3. Monitor sync logs regularly
4. Set up alerts for failed syncs

### Performance
1. Clear old sync logs periodically
2. Monitor IndexedDB usage
3. Optimize product data size
4. Limit offline data retention

## 📱 Browser Support

Requires modern browsers with:
- IndexedDB support
- Service Worker support
- ES6+ JavaScript support

Tested on:
- Chrome 58+
- Firefox 55+
- Safari 10+
- Edge 79+

## 🔐 Security Notes

- Offline data stored in browser (not encrypted)
- CSRF protection for all sync operations
- Server validates all offline data
- Duplicate sales prevented by unique receipt numbers

## 📞 Need Help?

1. Run diagnostic: `php offline_diagnostic.php`
2. Check logs: `storage/logs/laravel.log`
3. Browser console for JavaScript errors
4. Network tab for API failures

---

**Remember:** Start with offline mode DISABLED for development, enable only when ready to test offline features!
