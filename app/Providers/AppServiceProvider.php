<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load settings from database into config
        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::all();
                
                foreach ($settings as $setting) {
                    Config::set('settings.' . $setting->key, $setting->value);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if database is not set up yet
            // This prevents errors during initial setup
        }
    }
}