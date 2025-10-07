#!/bin/bash

echo "🔧 Fixing Cylinder Management Issues"
echo "=================================="

# Clear Laravel caches
echo "1. Clearing Laravel caches..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "2. Checking cylinder routes..."
php artisan route:list --path=cylinders

echo "3. Optimizing routes..."
php artisan route:cache

echo "✅ Cache clearing completed!"
echo ""
echo "🔍 Testing API Route:"
echo "POST /api/cylinders/{id}/quick-return should now work"
