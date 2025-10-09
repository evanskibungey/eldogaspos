@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
    $storeRoute = $isPosContext ? 'pos.cylinders.store' : 'admin.cylinders.store';
    $quickCreateUrl = $isPosContext ? route('pos.customers.quick-store') : route('admin.customers.quick-store');
@endphp

<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">New Cylinder Transaction</h1>
                    <p class="text-gray-600 mt-1">Select products with quantities and brands</p>
                </div>
                <a href="{{ route($indexRoute) }}" class="px-6 py-3 bg-white border rounded-xl font-semibold hover:bg-gray-50">← Back</a>
            </div>

            @if($products->isEmpty())
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                    <p class="font-semibold">No products available with stock</p>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-xl shadow p-6">
                            <h3 class="font-bold text-lg mb-4">Transaction Type *</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="transaction_type" value="drop_off" class="sr-only peer" checked>
                                    <div class="p-4 border-2 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50">
                                        <p class="font-bold">Drop-off First</p>
                                        <p class="text-sm text-gray-600">Collect later</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="transaction_type" value="advance_collection" class="sr-only peer">
                                    <div class="p-4 border-2 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50">
                                        <p class="font-bold">Advance Collection</p>
                                        <p class="text-sm text-gray-600">Take now, return later</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow p-6">
                            <h3 class="font-bold text-lg mb-4">Available Products</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($products as $product)
                                    <div class="border-2 rounded-lg p-4 hover:border-purple-400 hover:bg-purple-50 hover:shadow-lg transition-all cursor-pointer product-card"
                                         data-id="{{ $product->id }}"
                                         data-name="{{ $product->name }}"
                                         data-price="{{ $product->price }}"
                                         data-stock="{{ $product->stock }}">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p class="font-bold">{{ $product->name }}</p>
                                                <p class="text-sm text-gray-600">{{ $product->category->name ?? 'Uncategorized' }}</p>
                                            </div>
                                            <div class="add-to-cart-icon text-green-600">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-bold text-purple-600">KSh {{ number_format($product->price, 0) }}</span>
                                            <span class="text-xs px-2 py-1 rounded-full {{ $product->stock > $product->min_stock ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                                Stock: {{ $product->stock }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-xl shadow p-6">
                            <h3 class="font-bold text-lg mb-4">Customer *</h3>
                            <input type="text" id="customer_search" class="w-full px-4 py-3 border-2 rounded-lg" placeholder="Search customer...">
                            <input type="hidden" id="customer_id">
                            <input type="hidden" id="customer_name">
                            <input type="hidden" id="customer_phone">
                            
                            <div id="customer_dropdown" class="hidden mt-2 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                <div class="customer-option px-3 py-2 hover:bg-green-50 cursor-pointer border-b-2 border-green-200 bg-green-50"
                                     data-id="new" data-search="new add customer create">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        <div>
                                            <p class="font-semibold text-sm text-green-900">➕ Add New Customer</p>
                                            <p class="text-xs text-green-700">Create a new customer record</p>
                                        </div>
                                    </div>
                                </div>
                                @foreach($customers as $customer)
                                    <div class="customer-option px-3 py-2 hover:bg-gray-50 cursor-pointer"
                                         data-id="{{ $customer->id }}"
                                         data-name="{{ $customer->name }}"
                                         data-phone="{{ $customer->phone }}"
                                         data-search="{{ strtolower($customer->name . ' ' . $customer->phone) }}">
                                        <p class="font-semibold text-sm">{{ $customer->name }}</p>
                                        <p class="text-xs text-gray-600">{{ $customer->phone }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div id="new_customer_form" class="hidden mt-4 space-y-2">
                                <input type="text" id="new_name" placeholder="Name *" class="w-full px-3 py-2 border-2 rounded-lg">
                                <input type="text" id="new_phone" placeholder="Phone *" class="w-full px-3 py-2 border-2 rounded-lg">
                                <button type="button" id="create_customer_btn" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                                    Create & Select Customer
                                </button>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow p-6">
                            <h3 class="font-bold text-lg mb-4">Cart</h3>
                            <div id="cart-items" class="space-y-2 mb-4 min-h-[100px]">
                                <p class="text-gray-500 text-sm text-center py-8">No items</p>
                            </div>
                            
                            <div class="border-t pt-4 space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm">Subtotal:</span>
                                    <span id="subtotal" class="font-semibold">KSh 0</span>
                                </div>
                                <div id="deposit-wrapper" class="hidden">
                                    <label class="text-sm font-semibold text-red-600">Deposit Amount * (Required)</label>
                                    <input type="number" id="deposit" value="0" min="0" placeholder="Enter deposit amount" class="w-full px-3 py-2 border-2 border-red-300 rounded-lg text-sm mt-1">
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-2">
                                    <span>Total:</span>
                                    <span id="total" class="text-purple-600">KSh 0</span>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <p class="text-sm font-semibold">Payment *</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="payment" value="paid" class="sr-only peer">
                                        <div class="p-2 border-2 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 text-center text-sm font-semibold">Paid</div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="payment" value="pending" class="sr-only peer" checked>
                                        <div class="p-2 border-2 rounded-lg peer-checked:border-yellow-500 peer-checked:bg-yellow-50 text-center text-sm font-semibold">Pending</div>
                                    </label>
                                </div>
                            </div>

                            <textarea id="notes" rows="2" placeholder="Notes (optional)" class="w-full mt-4 px-3 py-2 border-2 rounded-lg text-sm"></textarea>

                            <button type="button" id="submit-btn" class="w-full mt-4 px-6 py-3 bg-gradient-to-r from-orange-600 to-pink-600 text-white font-bold rounded-xl hover:from-orange-700 hover:to-pink-700 disabled:opacity-50">
                                Create Transaction
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div id="brand-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Add Product with Brands</h3>
                <button type="button" id="brand-modal-close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="product-info" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <p class="font-semibold" id="modal-product-name"></p>
                <p class="text-sm text-gray-600" id="modal-product-price"></p>
                <p class="text-sm text-gray-600" id="modal-product-stock"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">How many units do you want to add?</label>
                <input type="number" id="total-quantity" min="1" value="1" class="w-full px-4 py-3 border-2 rounded-lg">
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold">Specify brands for each unit:</label>
                    <button type="button" id="auto-fill-brands" class="text-xs px-3 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">
                        Auto-fill
                    </button>
                </div>
                <div id="brand-inputs-container" class="space-y-2 max-h-60 overflow-y-auto"></div>
            </div>

            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Quick select common brands:</p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Total Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">K-Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Pro Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Afrigas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Hashi Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Sea Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Olibya</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Supa Gas</button>
                    <button type="button" class="quick-brand px-3 py-2 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 text-sm">Other</button>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="brand-cancel" class="flex-1 px-4 py-2 border-2 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" id="brand-confirm" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold">Add to Cart</button>
            </div>
        </div>
    </div>

    @include('admin.cylinders.cart-script')
</x-app-layout>
