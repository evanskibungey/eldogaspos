<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get a setting value by key with fallback
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        try {
            // First try to get from config cache
            $configValue = config('settings.' . $key);
            if ($configValue !== null) {
                return $configValue;
            }
            
            // If not in config, try to get from database
            if (Schema::hasTable('settings')) {
                $setting = Setting::where('key', $key)->first();
                if ($setting) {
                    return $setting->value;
                }
            }
        } catch (\Exception $e) {
            // If database is not available, return default
        }
        
        // Return hardcoded defaults for essential settings
        $defaults = [
            'company_name' => 'EldoGas POS',
            'currency_symbol' => 'KSh',
            'tax_percentage' => '16',
            'receipt_footer' => 'Thank you for your business!',
        ];
        
        return $defaults[$key] ?? $default;
    }
}

if (!function_exists('all_settings')) {
    /**
     * Get all settings as key-value array
     *
     * @return array
     */
    function all_settings()
    {
        try {
            if (Schema::hasTable('settings')) {
                return Setting::pluck('value', 'key')->toArray();
            }
        } catch (\Exception $e) {
            // Return empty array if database is not available
        }
        
        return [];
    }
}
