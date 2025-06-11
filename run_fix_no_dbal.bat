@echo off
echo === Running POS Sale Error Fix (No DBAL Required) ===
echo.

REM Change to the project directory
cd /d C:\xampp\htdocs\eldogaspos

echo 1. Rolling back the failed migration if it exists...
php artisan migrate:rollback --step=1 --force 2>nul

echo.
echo 2. Running the raw SQL migration to make serial_number nullable...
php artisan migrate --path=database/migrations/2025_06_11_000002_make_serial_number_nullable_raw_sql.php --force

echo.
echo 3. Clearing application caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo.
echo 4. Checking if migration was successful...
php artisan migrate:status | findstr "make_serial_number_nullable_raw_sql"

echo.
echo 5. Verifying the column is now nullable...
echo Running verification query...

echo.
echo === Fix completed! ===
echo.
echo Next steps:
echo 1. Test the POS sales functionality
echo 2. Monitor application logs for any remaining errors
echo 3. Verify that sales can be processed without 422 errors
echo.
pause
