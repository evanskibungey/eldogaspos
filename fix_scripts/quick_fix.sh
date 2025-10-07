#!/bin/bash

echo "🎯 Quick Fix for 'Process Empty Return' Error"
echo "============================================="

echo "1. Clearing Laravel caches..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear

echo "2. Re-caching optimized routes..."
php artisan route:cache
php artisan config:cache

echo "3. Checking cylinder routes..."
php artisan route:list --path=cylinders

echo ""
echo "✅ Fix complete! Now test the 'Process Empty Return' button."
echo ""
echo "If issue persists:"
echo "- Stop Laravel server (Ctrl+C)"
echo "- Restart with: php artisan serve"
echo "- Clear browser cache"

# Optional: restart the server automatically
read -p "Would you like to restart the Laravel server now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Restarting Laravel server..."
    pkill -f "artisan serve" 2>/dev/null
    php artisan serve &
    echo "Server restarted. Check http://localhost:8000"
fi
