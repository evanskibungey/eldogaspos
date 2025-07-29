<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * Get all available settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $settings = Setting::all()->pluck('value', 'key');
            
            // Add default values for any missing essential settings
            $defaultSettings = [
                'currency_symbol' => '$',
                'company_name' => 'EldoGas',
                'low_stock_threshold' => 5,
                'tax_percentage' => 0,
            ];
            
            // Merge defaults with actual settings
            foreach ($defaultSettings as $key => $value) {
                if (!isset($settings[$key])) {
                    $settings[$key] = $value;
                }
            }
            
            return response()->json([
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching settings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching settings'
            ], 500);
        }
    }

    /**
     * Get a specific setting by key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function show($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                // Return default values for common settings
                $defaultValues = [
                    'currency_symbol' => '$',
                    'company_name' => 'EldoGas',
                    'low_stock_threshold' => 5,
                    'tax_percentage' => 0,
                ];
                
                if (isset($defaultValues[$key])) {
                    return response()->json([
                        'key' => $key,
                        'value' => $defaultValues[$key]
                    ]);
                }
                
                return response()->json([
                    'message' => 'Setting not found'
                ], 404);
            }
            
            return response()->json([
                'key' => $setting->key,
                'value' => $setting->value
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching setting: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching setting'
            ], 500);
        }
    }
    
    /**
     * Get multiple settings by key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMultiple(Request $request)
    {
        try {
            $keys = $request->keys;
            
            if (!is_array($keys)) {
                return response()->json([
                    'message' => 'Keys must be an array'
                ], 422);
            }
            
            $settings = Setting::whereIn('key', $keys)->get()->pluck('value', 'key');
            
            // Add default values for any missing essential settings
            $defaultSettings = [
                'currency_symbol' => '$',
                'company_name' => 'EldoGas',
                'low_stock_threshold' => 5,
                'tax_percentage' => 0,
            ];
            
            // Only include requested defaults
            $relevantDefaults = array_intersect_key($defaultSettings, array_flip($keys));
            
            // Merge defaults with actual settings
            foreach ($relevantDefaults as $key => $value) {
                if (!isset($settings[$key])) {
                    $settings[$key] = $value;
                }
            }
            
            return response()->json([
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching multiple settings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching settings'
            ], 500);
        }
    }
}