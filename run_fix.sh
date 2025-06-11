#!/bin/bash

# POS Sale Error Fix - Migration Runner
echo "=== Running POS Sale Error Fix ==="
echo ""

# Change to the project directory
cd /c/xampp/htdocs/eldogaspos

echo "1. Running migration to make serial_number nullable..."
php artisan migrate --force

echo ""
echo "2. Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo ""
echo "3. Checking if migration was successful..."
php artisan migrate:status | grep "make_serial_number_nullable"

echo ""
echo "=== Fix completed! ==="
echo ""
echo "Next steps:"
echo "1. Test the POS sales functionality"
echo "2. Monitor application logs for any remaining errors"
echo "3. Verify that sales can be processed without 422 errors"
