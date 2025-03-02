<!-- resources/views/pos/dashboard.blade.php -->
<x-app-layout>
    <!-- CSRF Token for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <div x-data="posSystem()" class="flex h-screen bg-gray-50 overflow-hidden">
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation Bar -->
            <div class="bg-white border-b border-orange-500 px-6 py-3 sticky top-0 z-10 shadow-sm">
                <div class="flex items-center justify-between">
                    <!-- Logo and Brand -->
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-2 font-bold text-xl text-gray-900">Eldo<span class="text-orange-500">Gas</span> <span class="font-light">POS</span></span>
                        </div>
                    </div>
                    
                    <!-- Search and Category Selector -->
                    <div class="flex-1 max-w-3xl mx-auto px-4">
                        <div class="relative">
                            <input type="text" 
                                x-model="searchQuery" 
                                @input="filterProducts"
                                placeholder="Search products by name or SKU..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- User Menu and Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Category Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-gray-700 px-3 py-2 rounded hover:bg-gray-100">
                                <svg class="w-5 h-5 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                </svg>
                                <span>Categories</span>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-50"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 style="display: none;">
                                <div class="py-1 max-h-64 overflow-y-auto">
                                    <button @click="currentCategory = null; open = false" 
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700"
                                            :class="currentCategory === null ? 'bg-orange-50 text-orange-700 font-medium' : ''">
                                        All Products
                                    </button>
                                    @foreach($categories ?? [] as $category)
                                    <button @click="currentCategory = {{ $category->id }}; open = false" 
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700"
                                            :class="currentCategory === {{ $category->id }} ? 'bg-orange-50 text-orange-700 font-medium' : ''">
                                        {{ $category->name }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden md:inline text-gray-700">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 style="display: none;">
                                <div class="py-1">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Sign out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Products Grid -->
                <div class="flex-1 p-6 overflow-y-auto">
                    <div x-show="isLoading" class="flex justify-center items-center h-full">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
                    </div>
                    
                    <!-- Category Pills - Mobile Only -->
                    <div class="md:hidden overflow-x-auto pb-4 mb-4 flex space-x-2 -mx-2 px-2">
                        <button @click="currentCategory = null" 
                                class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition-colors"
                                :class="currentCategory === null ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 border border-gray-300'">
                            All Products
                        </button>
                        @foreach($categories ?? [] as $category)
                        <button @click="currentCategory = {{ $category->id }}" 
                                class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition-colors"
                                :class="currentCategory === {{ $category->id }} ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 border border-gray-300'">
                            {{ $category->name }}
                        </button>
                        @endforeach
                    </div>
                    
                    <!-- Current Category Display -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <template x-if="currentCategory === null">All Products</template>
                            <template x-if="currentCategory !== null">
                                <span x-text="getCategoryName(currentCategory)"></span>
                            </template>
                        </h2>
                        <span class="text-sm text-gray-500" x-text="filteredProducts.length + ' items'"></span>
                    </div>
                    
                    <div x-show="!isLoading && filteredProducts.length === 0" class="flex flex-col items-center justify-center h-64">
                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-500 text-lg">No products found</p>
                        <p class="text-gray-400">Try a different search term or category</p>
                    </div>
                    
                    <!-- Improved Product Grid with Fewer Columns -->
                    <div x-show="!isLoading && filteredProducts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col">
                                <div class="relative overflow-hidden">
                                    <!-- Product Image - Square format with improved spacing -->
                                    <div class="bg-gray-100 aspect-square relative p-4">
                                        <img :src="product.image" 
                                             :alt="product.name"
                                             class="absolute inset-0 w-full h-full object-contain p-4">
                                    </div>
                                    
                                    <!-- Stock Badges -->
                                    <div x-show="product.stock <= 0" 
                                         class="absolute top-0 left-0 bg-red-500 text-white text-xs px-3 py-1 m-3 rounded-full font-medium shadow-sm">
                                        Out of Stock
                                    </div>
                                    <div x-show="product.stock > 0 && product.stock <= product.min_stock" 
                                         class="absolute top-0 left-0 bg-orange-500 text-white text-xs px-3 py-1 m-3 rounded-full font-medium shadow-sm">
                                        Low Stock
                                    </div>
                                </div>
                                
                                <!-- Product Info - Improved spacing -->
                                <div class="p-4 flex-grow flex flex-col">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="product.name"></h3>
                                    <div class="mt-auto">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="font-bold text-lg text-orange-500" x-text="'ksh' + product.price.toFixed(2)"></span>
                                           
                                        </div>
                                        
                                        <!-- Add to Cart Button -->
                                        <button @click="addToCart(product)"
                                                :disabled="product.stock <= 0"
                                                class="w-full bg-orange-500 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center justify-center transition-colors hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                           
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Cart Section -->
                <div class="w-80 bg-white shadow-xl flex flex-col border-l border-gray-200">
                    <div class="p-3 border-b bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Cart
                            <span x-show="cart.length > 0" 
                                  class="ml-2 bg-orange-500 text-white text-xs px-2 py-1 rounded-full" 
                                  x-text="cart.length"></span>
                        </h2>
                    </div>

                    <!-- Cart Items -->
                    <div class="flex-1 p-3 overflow-y-auto">
                        <template x-if="cart.length === 0">
                            <div class="flex flex-col items-center justify-center h-48 text-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-gray-500">Your cart is empty</p>
                                <p class="text-gray-400 text-sm mt-1">Add products to start a sale</p>
                            </div>
                        </template>

                        <template x-for="(item, index) in cart" :key="index">
                            <div class="mb-2 p-2 bg-white border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex justify-between mb-1">
                                    <div class="pr-2">
                                        <h3 x-text="item.name" class="font-semibold text-gray-900 text-sm line-clamp-1"></h3>
                                        <div class="text-xs text-gray-500" x-text="'S/N: ' + item.serial_number"></div>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 h-5 w-5 flex items-center justify-center rounded-full hover:bg-red-50">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-1 bg-gray-100 rounded-md">
                                        <button @click="updateQuantity(index, -1)" 
                                                class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-gray-700 focus:outline-none">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <span x-text="item.quantity" class="w-6 text-center text-xs font-medium"></span>
                                        <button @click="updateQuantity(index, 1)"
                                                class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-gray-700 focus:outline-none">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500" x-text="'ksh' + item.price.toFixed(2)"></div>
                                        <div class="font-bold text-orange-500 text-sm" x-text="'ksh' + (item.price * item.quantity).toFixed(2)"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Payment Section -->
                    <div class="p-3 border-t bg-gray-50">
                        <div class="mb-3 bg-white p-3 rounded-lg shadow-sm">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Subtotal:</span>
                                <span x-text="'ksh' + subtotal.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Tax:</span>
                                <span>ksh0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-base pt-2 border-t text-gray-900">
                                <span>Total:</span>
                                <span x-text="'ksh' + total.toFixed(2)" class="text-orange-500"></span>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-3">
                            <div class="flex space-x-2">
                                <label class="flex items-center justify-center p-2 bg-white border rounded-lg cursor-pointer hover:border-orange-500 transition-colors flex-1"
                                       :class="paymentMethod === 'cash' ? 'border-orange-500 ring-1 ring-orange-200' : 'border-gray-300'">
                                    <input type="radio" x-model="paymentMethod" value="cash" class="sr-only">
                                    <svg class="w-4 h-4 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span class="text-sm">Cash</span>
                                </label>
                                <label class="flex items-center justify-center p-2 bg-white border rounded-lg cursor-pointer hover:border-orange-500 transition-colors flex-1"
                                       :class="paymentMethod === 'credit' ? 'border-orange-500 ring-1 ring-orange-200' : 'border-gray-300'">
                                    <input type="radio" x-model="paymentMethod" value="credit" class="sr-only">
                                    <svg class="w-4 h-4 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span class="text-sm">Credit</span>
                                </label>
                            </div>
                        </div>

                        <!-- Customer Details (for credit) -->
                        <div x-show="paymentMethod === 'credit'" x-transition class="mb-3">
                            <div class="space-y-2">
                                <input type="text" 
                                       x-model="customerDetails.name" 
                                       placeholder="Customer Name"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                                <input type="text" 
                                       x-model="customerDetails.phone" 
                                       placeholder="Phone Number"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <button @click="processSale" 
                                :disabled="!canCheckout"
                                class="w-full bg-orange-500 text-white py-2 px-4 rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-orange-400 flex items-center justify-center font-medium shadow-sm">
                            <svg x-show="isProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isProcessing ? 'Processing...' : 'Complete Sale'" class="text-sm"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div x-show="showReceipt" 
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto relative print:shadow-none print:max-h-none print:w-full print:max-w-full print:rounded-none"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                
                <!-- Receipt Header - Orange Banner -->
                <div class="bg-orange-500 text-white p-4 rounded-t-xl flex items-center justify-between sticky top-0 z-10 print:bg-white print:text-black print:p-0 print:border-b-2 print:border-black">
                    <div class="print:w-full print:text-center">
                        <h2 class="text-xl font-bold print:text-2xl">{{ config('app.name', 'EldoGas') }} Receipt</h2>
                        <p class="text-sm text-orange-100 print:text-black print:font-normal print:text-base">#<span x-text="receiptNumber"></span></p>
                    </div>
                    <div class="bg-white rounded-full p-1 text-orange-500 print:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Receipt Details -->
                <div class="p-4 print:p-0 print:mt-4">
                    <!-- Store and Transaction Info -->
                    <div class="flex justify-between text-sm mb-4 border-b border-gray-200 pb-3 print:mb-6">
                        <div>
                            <p class="font-bold text-gray-900">123 Main Street, Eldoret</p>
                            <p class="text-gray-500">Tel: +254 700 123456</p>
                            <p class="text-gray-500">{{ auth()->user()->name }} • <span x-text="(new Date()).toLocaleDateString() + ' ' + (new Date()).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span></p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded-full text-xs font-medium print:border print:border-orange-500" 
                                  x-text="paymentMethod === 'cash' ? 'Cash Payment' : 'Credit Payment'"></span>
                        </div>
                    </div>
                    
                    <!-- Items Table - Professional for printing -->
                    <div class="mb-4 print:mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2 print:text-base print:font-bold">Purchased Items</h4>
                        <div class="border border-gray-200 rounded-lg overflow-hidden print:border-collapse">
                            <table class="w-full text-sm print:text-base">
                                <thead class="bg-gray-50 print:border-b-2 print:border-gray-300">
                                    <tr class="text-left text-gray-500 print:text-black">
                                        <th class="px-3 py-2 font-medium">Item</th>
                                        <th class="px-2 py-2 font-medium text-center w-10">Qty</th>
                                        <th class="px-3 py-2 font-medium text-right w-20">Unit</th>
                                        <th class="px-3 py-2 font-medium text-right w-24">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in cart" :key="index">
                                        <tr class="border-t border-gray-100 print:border-t print:border-gray-200">
                                            <td class="px-3 py-2">
                                                <div x-text="item.name" class="font-medium text-gray-900 line-clamp-1"></div>
                                                <div class="text-xs text-gray-500 print:text-gray-600" x-text="'S/N: ' + item.serial_number"></div>
                                            </td>
                                            <td class="px-2 py-2 text-center" x-text="item.quantity"></td>
                                            <td class="px-3 py-2 text-right" x-text="'ksh' + item.price.toFixed(2)"></td>
                                            <td class="px-3 py-2 text-right font-medium print:font-bold" x-text="'ksh' + (item.price * item.quantity).toFixed(2)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Totals - Compact -->
                    <div class="mb-4 bg-gray-50 rounded-lg p-3 print:bg-white print:border-t-2 print:border-black print:p-0 print:pt-2 print:mb-6">
                        <div class="flex justify-between text-sm mb-1 print:text-base">
                            <span class="text-gray-600 print:text-gray-700">Subtotal:</span>
                            <span x-text="'ksh' + subtotal.toFixed(2)" class="print:font-medium"></span>
                        </div>
                        <div class="flex justify-between text-sm mb-1 print:text-base">
                            <span class="text-gray-600 print:text-gray-700">Tax:</span>
                            <span class="print:font-medium">ksh0.00</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2 print:text-base">
                            <span class="text-gray-600 print:text-gray-700">Discount:</span>
                            <span class="print:font-medium">ksh0.00</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-300 print:text-xl print:pt-3 print:border-t-2">
                            <span>Total:</span>
                            <span class="text-orange-500 print:text-black" x-text="'ksh' + total.toFixed(2)"></span>
                        </div>
                    </div>
                    
                    <!-- Customer Info (for credit) - Compact -->
                    <template x-if="paymentMethod === 'credit'">
                        <div class="mb-4 bg-orange-50 rounded-lg p-3 border border-orange-100 print:bg-white print:border print:border-gray-300 print:rounded-none print:p-4 print:mb-6">
                            <h5 class="text-sm font-medium text-orange-800 mb-1 print:text-black print:text-base">Customer Information</h5>
                            <div class="flex justify-between print:block">
                                <p class="text-sm text-orange-700 print:text-black print:text-base print:font-medium" x-text="customerDetails.name"></p>
                                <p class="text-sm text-orange-600 print:text-gray-600 print:text-base" x-text="customerDetails.phone"></p>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Thank You Message - Professional for printing -->
                    <div class="text-center text-xs text-gray-500 mb-2 print:text-base print:font-medium print:mb-6 print:mt-8">
                        <p>Thank you for your business!</p>
                        <p class="mt-1 print:mt-2">Keep this receipt for any returns or exchanges.</p>
                    </div>
                </div>
                
                <!-- Action Buttons - Fixed at Bottom - Hidden for Print -->
                <div class="bg-gray-50 p-3 rounded-b-xl border-t border-gray-200 flex space-x-2 sticky bottom-0 print:hidden">
                    <button @click="printReceipt()" 
                            class="flex-1 flex items-center justify-center bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 shadow-sm font-medium">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>
                    <button @click="closeReceipt" 
                            class="flex-1 bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 font-medium">
                        New Sale
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Error Modal -->
        <div x-show="showError" 
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                 <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 text-red-500 mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Error Processing Sale</h3>
                <div class="text-gray-600 text-center mb-6" x-text="errorMessage || 'An unexpected error occurred while processing your sale.'"></div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                    <h4 class="font-medium text-gray-700 mb-2">Troubleshooting Tips:</h4>
                    <ul class="text-sm text-gray-600 space-y-1 pl-5 list-disc">
                        <li>Check if all products have sufficient stock</li>
                        <li>Verify customer details if using credit payment</li>
                        <li>Ensure all serial numbers are valid</li>
                        <li>Try refreshing the page if the issue persists</li>
                    </ul>
                </div>
                
                <button @click="showError = false" 
                        class="w-full bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Close
                </button>
            </div>
        </div>
        
        <!-- Alpine.js Script -->
        <script>
            function posSystem() {
                return {
                    // State Variables
                    cart: [],
                    searchQuery: '',
                    currentCategory: null,
                    showCategoryDrawer: false,
                    paymentMethod: 'cash',
                    customerDetails: {
                        name: '',
                        phone: ''
                    },
                    showReceipt: false,
                    showError: false,
                    receiptNumber: '',
                    errorMessage: '',
                    isLoading: false,
                    isProcessing: false,
                    subtotal: 0,
                    total: 0,
                    
                    // Products Data
                    allProducts: @json($products),
                    
                    // Computed Properties
                    get filteredProducts() {
                        let products = this.allProducts;
                        
                        // Filter by category if selected
                        if (this.currentCategory !== null) {
                            products = products.filter(p => p.category_id === this.currentCategory);
                        }
                        
                        // Filter by search query if present
                        if (this.searchQuery.trim() !== '') {
                            const query = this.searchQuery.toLowerCase();
                            products = products.filter(product => 
                                product.name.toLowerCase().includes(query) || 
                                product.sku.toLowerCase().includes(query) ||
                                (product.serial_number && product.serial_number.toLowerCase().includes(query))
                            );
                        }
                        
                        return products;
                    },
                    
                    get canCheckout() {
                        if (this.cart.length === 0 || this.isProcessing) return false;
                        
                        // Only require customer details for credit payment
                        if (this.paymentMethod === 'credit') {
                            return this.customerDetails.name.trim() !== '' && 
                                   this.customerDetails.phone.trim() !== '';
                        }
                        
                        // For cash payments, no customer details required
                        return true;
                    },

                    // Methods
                    init() {
                        this.updateTotals();
                        console.log('POS System Initialized');
                        console.log('Products loaded:', this.allProducts.length);
                    },
                    
                    getCategoryName(categoryId) {
                        const categories = @json($categories ?? []);
                        const category = categories.find(c => c.id === categoryId);
                        return category ? category.name : 'Unknown Category';
                    },
                    
                    toggleCategories() {
                        this.showCategoryDrawer = !this.showCategoryDrawer;
                    },

                    addToCart(product) {
                        // Check if product has stock
                        if (product.stock <= 0) {
                            alert('This product is out of stock.');
                            return;
                        }
                        
                        console.log('Adding to cart:', product);
                        
                        const existingIndex = this.cart.findIndex(item => item.id === product.id);
                        
                        if (existingIndex >= 0) {
                            // Check if adding one more would exceed stock
                            if (this.cart[existingIndex].quantity + 1 > product.stock) {
                                alert(`Cannot add more. Only ${product.stock} in stock.`);
                                return;
                            }
                            
                            this.cart[existingIndex].quantity += 1;
                        } else {
                            this.cart.push({ 
                                ...product, 
                                quantity: 1 
                            });
                        }
                        
                        this.updateTotals();
                    },

                    removeFromCart(index) {
                        this.cart.splice(index, 1);
                        this.updateTotals();
                    },

                    updateQuantity(index, change) {
                        const item = this.cart[index];
                        if (!item) return;
                        
                        const newQuantity = item.quantity + change;
                        
                        // Find the product to check stock
                        const product = this.allProducts.find(p => p.id === item.id);
                        
                        if (newQuantity > 0 && product) {
                            if (newQuantity <= product.stock) {
                                item.quantity = newQuantity;
                            } else {
                                alert(`Cannot add more. Only ${product.stock} in stock.`);
                            }
                        } else if (newQuantity <= 0) {
                            this.removeFromCart(index);
                        }
                        
                        this.updateTotals();
                    },

                    updateTotals() {
                        this.subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                        // For now, total is same as subtotal (no tax or discount)
                        this.total = this.subtotal;
                    },

                    async processSale() {
                        if (!this.canCheckout) return;
                        
                        this.isProcessing = true;
                        console.log('Processing sale...');
                        console.log('Cart items:', this.cart);
                        console.log('Payment method:', this.paymentMethod);
                        
                        try {
                            const response = await fetch('{{ route("pos.sales.store") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    cart_items: this.cart,
                                    payment_method: this.paymentMethod,
                                    customer_details: this.paymentMethod === 'credit' ? this.customerDetails : null
                                })
                            });

                            const result = await response.json();
                            console.log('Sale response:', result);
                            
                            if (result.success) {
                                this.receiptNumber = result.receipt_number;
                                this.showReceipt = true;
                            } else {
                                this.errorMessage = result.message || 'Error processing sale';
                                this.showError = true;
                                console.error('Sale error:', this.errorMessage);
                            }
                        } catch (error) {
                            console.error('Sale exception:', error);
                            this.errorMessage = 'Network error or server exception occurred.';
                            this.showError = true;
                        } finally {
                            this.isProcessing = false;
                        }
                    },

                    printReceipt() {
                        window.print();
                        // Close the receipt modal after printing
                        this.closeReceipt();
                    },

                    closeReceipt() {
                        this.showReceipt = false;
                        this.cart = [];
                        this.updateTotals();
                        this.paymentMethod = 'cash';
                        this.customerDetails = { name: '', phone: '' };
                    }
                }
            }
        </script>
    </div>
</x-app-layout>