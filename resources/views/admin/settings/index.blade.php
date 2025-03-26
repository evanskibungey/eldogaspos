<!-- resources/views/admin/settings/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 p-4 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- Company Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Company Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Company Name -->
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                                    <input type="text" name="company_name" id="company_name" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['company_name'] ?? old('company_name') }}" required>
                                    @error('company_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Company Email -->
                                <div>
                                    <label for="company_email" class="block text-sm font-medium text-gray-700">Company Email</label>
                                    <input type="email" name="company_email" id="company_email" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['company_email'] ?? old('company_email') }}" required>
                                    @error('company_email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Company Phone -->
                                <div>
                                    <label for="company_phone" class="block text-sm font-medium text-gray-700">Company Phone</label>
                                    <input type="text" name="company_phone" id="company_phone" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['company_phone'] ?? old('company_phone') }}" required>
                                    @error('company_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Company Address -->
                                <div>
                                    <label for="company_address" class="block text-sm font-medium text-gray-700">Company Address</label>
                                    <textarea name="company_address" id="company_address" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required>{{ $settings['company_address'] ?? old('company_address') }}</textarea>
                                    @error('company_address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Company Logo -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700">Company Logo</label>
                                <div class="mt-1 flex items-center">
                                    <div class="mr-4">
                                        @if(isset($settings['company_logo']))
                                            <img src="{{ asset('storage/' . $settings['company_logo']) }}" 
                                                alt="Company Logo" 
                                                class="h-16 w-auto object-contain border border-gray-200 rounded-md p-1">
                                        @else
                                            <div class="h-16 w-24 flex items-center justify-center bg-gray-100 border border-gray-200 rounded-md">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <label for="logo" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                                            Browse
                                            <input id="logo" name="logo" type="file" class="sr-only" accept="image/*">
                                        </label>
                                        <p class="ml-2 text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                                    </div>
                                </div>
                                @error('logo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- System Preferences -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">System Preferences</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Currency Symbol -->
                                <div>
                                    <label for="currency_symbol" class="block text-sm font-medium text-gray-700">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" id="currency_symbol" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['currency_symbol'] ?? old('currency_symbol') }}" required>
                                    @error('currency_symbol')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Tax Percentage -->
                                <div>
                                    <label for="tax_percentage" class="block text-sm font-medium text-gray-700">Tax Percentage (%)</label>
                                    <input type="number" name="tax_percentage" id="tax_percentage" step="0.01" min="0" max="100"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['tax_percentage'] ?? old('tax_percentage') }}">
                                    @error('tax_percentage')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Low Stock Threshold -->
                                <div>
                                    <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" id="low_stock_threshold" min="1"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value="{{ $settings['low_stock_threshold'] ?? old('low_stock_threshold') }}" required>
                                    @error('low_stock_threshold')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Receipt Footer -->
                            <div class="mt-6">
                                <label for="receipt_footer" class="block text-sm font-medium text-gray-700">Receipt Footer Message</label>
                                <textarea name="receipt_footer" id="receipt_footer" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >{{ $settings['receipt_footer'] ?? old('receipt_footer') }}</textarea>
                                @error('receipt_footer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- System Features -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">System Features</h3>
                            
                            <div class="space-y-4">
                                <!-- Enable Stock Alerts -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="enable_stock_alerts" name="enable_stock_alerts" type="checkbox" 
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            {{ isset($settings['enable_stock_alerts']) && $settings['enable_stock_alerts'] ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="enable_stock_alerts" class="font-medium text-gray-700">Enable Stock Alerts</label>
                                        <p class="text-gray-500">Receive notifications when products are running low on stock</p>
                                    </div>
                                </div>

                                <!-- Enable Credit Sales -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="enable_credit_sales" name="enable_credit_sales" type="checkbox" 
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            {{ isset($settings['enable_credit_sales']) && $settings['enable_credit_sales'] ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="enable_credit_sales" class="font-medium text-gray-700">Enable Credit Sales</label>
                                        <p class="text-gray-500">Allow sales on credit for registered customers</p>
                                    </div>
                                </div>

                                <!-- Enable Receipt Printing -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="enable_receipt_printing" name="enable_receipt_printing" type="checkbox" 
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            {{ isset($settings['enable_receipt_printing']) && $settings['enable_receipt_printing'] ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="enable_receipt_printing" class="font-medium text-gray-700">Enable Receipt Printing</label>
                                        <p class="text-gray-500">Automatically print receipts after each sale</p>
                                    </div>
                                </div>

                                <!-- Require Serial Number -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="require_serial_number" name="require_serial_number" type="checkbox" 
                                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            {{ isset($settings['require_serial_number']) && $settings['require_serial_number'] ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="require_serial_number" class="font-medium text-gray-700">Require Serial Number</label>
                                        <p class="text-gray-500">Require serial numbers for all products during sales</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Preview logo image
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.querySelector('img[alt="Company Logo"]') || document.createElement('img');
                    imgContainer.src = e.target.result;
                    imgContainer.alt = 'Company Logo';
                    imgContainer.className = 'h-16 w-auto object-contain border border-gray-200 rounded-md p-1';
                    
                    const container = document.querySelector('.mr-4');
                    
                    if (!document.querySelector('img[alt="Company Logo"]')) {
                        container.innerHTML = '';
                        container.appendChild(imgContainer);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
    @endpush
</x-app-layout>