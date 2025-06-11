@echo off
echo === Quick POS Sale Fix (No DBAL Required) ===
echo.

cd /d C:\xampp\htdocs\eldogaspos

echo Running direct database fix...
php direct_database_fix.php

echo.
echo Clearing application caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo.
echo === Fix completed! ===
echo The 422 error should now be resolved.
echo.
echo Test your POS sales functionality now!
echo.
pause
