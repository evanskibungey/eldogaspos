<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the specified settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'required|string|max:20',
            'company_address' => 'required|string|max:500',
            'currency_symbol' => 'required|string|max:10',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'low_stock_threshold' => 'required|integer|min:1',
            'receipt_footer' => 'nullable|string|max:1000',
        ]);

        // Process logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            
            // Get the old logo path to delete
            $oldLogoPath = Setting::where('key', 'company_logo')->value('value');
            
            // Update the logo path
            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['value' => $logoPath]
            );
            
            // Delete old logo if exists
            if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }
        }

        // Update text settings
        $textSettings = [
            'company_name',
            'company_email',
            'company_phone',
            'company_address',
            'currency_symbol',
            'tax_percentage',
            'low_stock_threshold',
            'receipt_footer',
        ];

        foreach ($textSettings as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        // Update boolean settings
        $booleanSettings = [
            'enable_stock_alerts',
            'enable_credit_sales',
            'enable_receipt_printing',
            'require_serial_number',
        ];

        foreach ($booleanSettings as $key) {
            $value = $request->has($key) ? 1 : 0;
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully.');
    }
}