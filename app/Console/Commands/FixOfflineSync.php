<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FixOfflineSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:fix-offline-sync {--check-only : Only check for issues without fixing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and fix common issues with offline sync implementation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $checkOnly = $this->option('check-only');
        $issues = 0;

        $this->info('🔧 Checking Offline Sync Implementation...');
        $this->newLine();

        // Check 1: Database tables
        $this->info('1. Checking database tables...');
        
        if (!Schema::hasTable('offline_sync_logs')) {
            $issues++;
            $this->error('   ❌ Table offline_sync_logs not found');
            
            if (!$checkOnly) {
                $this->info('   🔧 Running migration...');
                Artisan::call('migrate', ['--path' => 'database/migrations/2025_06_04_000001_create_offline_sync_logs_table.php']);
                $this->info('   ✅ Migration completed');
            }
        } else {
            $this->info('   ✅ Table offline_sync_logs exists');
        }

        // Check 2: Sales table columns
        $this->info('2. Checking sales table columns...');
        
        $requiredColumns = ['is_offline_sync', 'offline_receipt_number', 'offline_created_at'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('sales', $column)) {
                $missingColumns[] = $column;
            }
        }
        
        if (!empty($missingColumns)) {
            $issues++;
            $this->error('   ❌ Missing columns in sales table: ' . implode(', ', $missingColumns));
            
            if (!$checkOnly) {
                $this->info('   🔧 Running migration...');
                Artisan::call('migrate', ['--path' => 'database/migrations/2025_06_04_000002_add_offline_sync_columns_to_sales_table.php']);
                $this->info('   ✅ Migration completed');
            }
        } else {
            $this->info('   ✅ All required columns exist in sales table');
        }

        // Check 3: Models
        $this->info('3. Checking models...');
        
        if (!class_exists('App\Models\OfflineSyncLog')) {
            $issues++;
            $this->error('   ❌ OfflineSyncLog model not found');
            
            if (!$checkOnly) {
                $this->info('   🔧 Model should have been created. Please check app/Models/OfflineSyncLog.php');
            }
        } else {
            $this->info('   ✅ OfflineSyncLog model exists');
        }

        // Check 4: Controller
        $this->info('4. Checking controller...');
        
        if (!class_exists('App\Http\Controllers\API\OfflineSyncController')) {
            $issues++;
            $this->error('   ❌ OfflineSyncController not found');
        } else {
            $this->info('   ✅ OfflineSyncController exists');
        }

        // Check 5: Configuration
        $this->info('5. Checking configuration...');
        
        if (!config()->has('offline')) {
            $issues++;
            $this->error('   ❌ Offline configuration not found');
            
            if (!$checkOnly) {
                $this->info('   🔧 Please ensure config/offline.php exists');
            }
        } else {
            $offlineEnabled = config('offline.enabled');
            if ($offlineEnabled) {
                $this->info('   ✅ Offline mode is ENABLED');
            } else {
                $this->warn('   ⚠️  Offline mode is DISABLED');
                $this->info('      Run: php artisan pos:offline-mode enable');
            }
        }

        // Check 6: Frontend assets
        $this->info('6. Checking frontend assets...');
        
        $assets = [
            'public/sw.js' => 'Service Worker',
            'public/js/pos-system.js' => 'POS System JS',
            'public/css/offline.css' => 'Offline CSS'
        ];
        
        foreach ($assets as $path => $name) {
            if (!File::exists(base_path($path))) {
                $issues++;
                $this->error("   ❌ {$name} not found at {$path}");
                
                if (!$checkOnly) {
                    $this->info('   🔧 Please run: npm run build');
                }
            } else {
                $this->info("   ✅ {$name} exists");
            }
        }

        // Check 7: Routes
        $this->info('7. Checking API routes...');
        
        $routes = [
            'api/v1/offline/products',
            'api/v1/offline/sync-sale',
            'api/v1/offline/sync-status'
        ];
        
        $routeCollection = app('router')->getRoutes();
        foreach ($routes as $uri) {
            $found = false;
            foreach ($routeCollection as $route) {
                if ($route->uri() === $uri) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $issues++;
                $this->error("   ❌ Route not found: /{$uri}");
            } else {
                $this->info("   ✅ Route exists: /{$uri}");
            }
        }

        // Summary
        $this->newLine();
        $this->info(str_repeat('=', 50));
        
        if ($issues === 0) {
            $this->info('✅ All checks passed! Offline sync is properly configured.');
        } else {
            $this->error("❌ Found {$issues} issue(s).");
            
            if ($checkOnly) {
                $this->warn('Run without --check-only to attempt automatic fixes.');
            } else {
                $this->warn('Some issues may require manual intervention.');
                $this->newLine();
                $this->info('Next steps:');
                $this->info('1. Run: php artisan config:clear');
                $this->info('2. Run: php artisan migrate');
                $this->info('3. Run: npm run build');
                $this->info('4. Test offline functionality');
            }
        }

        return $issues === 0 ? 0 : 1;
    }
}
