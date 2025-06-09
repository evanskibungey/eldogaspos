<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleOfflineMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:offline-mode {action : enable, disable, or status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle offline mode for the POS system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        switch ($action) {
            case 'enable':
                $this->enableOfflineMode($envPath, $envContent);
                break;
            case 'disable':
                $this->disableOfflineMode($envPath, $envContent);
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error('Invalid action. Use enable, disable, or status.');
                return 1;
        }

        return 0;
    }

    private function enableOfflineMode($envPath, $envContent)
    {
        if (strpos($envContent, 'OFFLINE_MODE_ENABLED=') !== false) {
            $envContent = preg_replace('/OFFLINE_MODE_ENABLED=.*/', 'OFFLINE_MODE_ENABLED=true', $envContent);
        } else {
            $envContent .= "\n\n# Offline Mode Configuration\nOFFLINE_MODE_ENABLED=true\n";
        }

        File::put($envPath, $envContent);
        
        $this->info('✅ Offline mode has been ENABLED');
        $this->info('Run "php artisan config:clear" to apply changes');
        $this->newLine();
        $this->warn('📱 Production Mode Features:');
        $this->line('- Full offline functionality');
        $this->line('- Works without internet connection');
        $this->line('- Automatic background sync when online');
        $this->line('- Offline sales storage');
        $this->line('- Connection status monitoring');
        $this->line('- Manual sync controls');
    }

    private function disableOfflineMode($envPath, $envContent)
    {
        if (strpos($envContent, 'OFFLINE_MODE_ENABLED=') !== false) {
            $envContent = preg_replace('/OFFLINE_MODE_ENABLED=.*/', 'OFFLINE_MODE_ENABLED=false', $envContent);
        } else {
            $envContent .= "\n\n# Offline Mode Configuration\nOFFLINE_MODE_ENABLED=false\n";
        }

        File::put($envPath, $envContent);
        
        $this->info('✅ Offline mode has been DISABLED');
        $this->info('Run "php artisan config:clear" to apply changes');
        $this->newLine();
        $this->warn('💻 Development Mode Features:');
        $this->line('- Works online-only (no offline complexity)');
        $this->line('- No "You\'re Offline" messages');
        $this->line('- All POS features work normally');
        $this->line('- Sales processing works online');
        $this->line('- No offline capabilities');
        $this->line('- No offline storage or sync');
    }

    private function showStatus()
    {
        $isEnabled = config('offline.enabled', false);
        
        $this->newLine();
        $this->info('🔍 Current Offline Mode Status');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($isEnabled) {
            $this->info('Status: ENABLED ✅');
            $this->newLine();
            $this->line('📱 Active Features:');
            $this->line('- Full offline functionality');
            $this->line('- Automatic background sync');
            $this->line('- Offline sales storage');
            $this->line('- Connection monitoring');
        } else {
            $this->warn('Status: DISABLED ❌');
            $this->newLine();
            $this->line('💻 Active Features:');
            $this->line('- Online-only mode');
            $this->line('- No offline capabilities');
            $this->line('- Standard POS functionality');
        }
        
        $this->newLine();
        $this->line('Database Configuration:');
        $this->line('- Name: ' . config('offline.database.name'));
        $this->line('- Version: ' . config('offline.database.version'));
        
        $this->newLine();
        $this->line('Sync Configuration:');
        $this->line('- Auto sync interval: ' . (config('offline.sync.auto_sync_interval') / 1000) . ' seconds');
        $this->line('- Max retry attempts: ' . config('offline.sync.max_retry_attempts'));
        $this->line('- Retry delay: ' . (config('offline.sync.retry_delay') / 1000) . ' seconds');
    }
}
