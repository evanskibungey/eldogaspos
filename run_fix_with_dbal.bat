@echo off
echo === Installing doctrine/dbal and running migration ===
echo.

REM Change to the project directory
cd /d C:\xampp\htdocs\eldogaspos

echo 1. Installing doctrine/dbal package...
composer require doctrine/dbal --no-scripts

echo.
echo 2. Running migration to make serial_number nullable...
php artisan migrate --force

echo.
echo 3. Clearing application caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo.
echo 4. Checking if migration was successful...
php artisan migrate:status | findstr "make_serial_number_nullable"

echo.
echo === Fix completed! ===
echo.
echo Next steps:
echo 1. Test the POS sales functionality
echo 2. Monitor application logs for any remaining errors
echo 3. Verify that sales can be processed without 422 errors
echo.
pause
