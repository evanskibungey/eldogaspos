<!-- resources/views/pos/dashboard.blade.php -->
<x-app-layout>
    <!-- CSRF Token for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Enhanced Print Styles for 57mm thermal receipt -->
    <style>
        /* Hide Alpine.js elements until ready */
        [x-cloak] { 
            display: none !important;
        }
        /* Improved UI Styles */
        .product-card {
            transition: all 0.2s ease-in-out;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
            background-color: #f9fafb;
        }
        
        .image-container img {
            transition: transform 0.3s ease;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
            border-radius: 8px 8px 0 0;
        }
        
        .image-container:hover .image-overlay {
            opacity: 1;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .cart-item {
            transition: all 0.2s ease;
        }
        
        .cart-item:hover {
            background-color: #f9fafb;
        }
        
        .price-tag {
            position: relative;
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(249, 115, 22, 0.2);
        }
        
        .category-pill {
            transition: all 0.2s ease;
        }
        
        .category-pill:hover {
            transform: translateY(-1px);
        }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s;
        }
        
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }

        /* Cart specific styles */
        .cart-wrapper {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .cart-items-container {
            overflow-y: auto;
            flex-grow: 1;
            max-height: calc(100vh - 270px); /* Adjust based on header and footer height */
        }

        .cart-payment-section {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
            z-index: 10;
        }

        .empty-cart-message {
            height: auto;
            padding: 2rem 1rem;
        }
        
        /* Receipt modal animations */
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .receipt-animation {
            animation: slideDown 0.3s ease-out forwards;
        }

        /* Only apply these styles during printing */
        @media print {
            /* Hide everything except receipt content when printing */
            body * {
                visibility: hidden;
            }
            
            /* Only show the printable receipt and its children */
            .printable-receipt,
            .printable-receipt * {
                visibility: visible !important;
            }
            
            /* Position the receipt properly */
            .printable-receipt {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 57mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background-color: white !important;
                display: block !important;
            }
            
            /* Set exact paper size */
            @page {
                size: 70mm auto !important; /* Auto height allows proper length based on content */
                margin: 0mm !important;
            }
            
            /* Main container */
            .receipt-container {
                width: 72mm !important;
                margin: 0mm auto !important;
                padding: 2mm !important;
                box-sizing: border-box !important;
                font-family: 'Roboto', sans-serif !important;
                color: black !important;
                line-height: 1.1 !important; /* Tighter line spacing */
            }

            /* Company name styles */
            .company-name {
                text-align: center !important;
                font-size: 12pt !important;
                font-weight: bold !important;
                margin: 0 0 1mm 0 !important;
                text-transform: uppercase !important;
            }
            
            /* Address and contact info */
            .company-info {
                text-align: center !important;
                font-size: 8pt !important;
                line-height: 1.1 !important;
                margin: 0 0 1mm 0 !important;
            }
            
            /* Separator line */
            .separator-line {
                text-align: center !important;
                font-size: 8pt !important;
                line-height: 1 !important;
                margin: 1mm 0 !important;
            }
            
            /* Transaction info */
            .transaction-info {
                display: flex !important;
                justify-content: space-between !important;
                font-size: 8pt !important;
                line-height: 1.2 !important;
                margin-bottom: 0.5mm !important;
            }
            
            .transaction-label {
                font-weight: bold !important;
                text-align: left !important;
            }
            
            .transaction-value {
                text-align: right !important;
            }
            
            /* Section title */
            .section-title {
                text-align: center !important;
                font-size: 9pt !important;
                font-weight: bold !important;
                margin: 1mm 0 !important;
            }
            
            /* Item table */
            .items-table {
                width: 100% !important;
                font-size: 8pt !important;
                margin: 1mm 0 !important;
                border-collapse: collapse !important;
                line-height: 1.1 !important;
                table-layout: fixed !important; /* Fixed layout prevents column issues */
            }
            
            .items-header {
                font-weight: bold !important;
                font-size: 8pt !important;
                margin-bottom: 1mm !important;
                border-bottom: 1px dashed #000 !important;
            }
            
            .item-name {
                width: 42% !important;
                text-align: left !important;
                font-weight: bold !important;
                padding-bottom: 1mm !important;
                white-space: normal !important; /* Allow wrapping */
                word-break: break-word !important; /* Break long words */
            }
            
            .item-qty {
                width: 10% !important;
                text-align: center !important;
                padding-bottom: 1mm !important;
            }
            
            .item-price {
                width: 22% !important;
                text-align: right !important;
                padding-bottom: 1mm !important;
            }
            
            .item-total {
                width: 26% !important;
                text-align: right !important;
                padding-bottom: 1mm !important;
            }
            
            .serial-number {
                font-size: 7pt !important;
                font-weight: normal !important;
                font-style: italic !important;
            }
            
            /* Totals section */
            .totals-section {
                display: flex !important;
                justify-content: space-between !important;
                font-size: 8pt !important;
                line-height: 1.2 !important;
                margin-bottom: 0.5mm !important;
            }
            
            .totals-label {
                text-align: left !important;
            }
            
            .totals-value {
                text-align: right !important;
            }
            
            .grand-total {
                font-weight: bold !important;
                font-size: 10pt !important;
                margin-top: 1mm !important;
            }
            
            /* Customer section */
            .customer-section {
                margin-top: 2mm !important;
            }
            
            .customer-section-title {
                font-weight: bold !important;
                text-align: center !important;
                font-size: 9pt !important;
                margin: 1mm 0 !important;
            }
            
            .customer-info {
                display: flex !important;
                font-size: 8pt !important;
                line-height: 1.2 !important;
                margin-bottom: 0.5mm !important;
            }
            
            .customer-label {
                font-weight: bold !important;
                min-width: 12mm !important;
            }
            
            /* Footer */
            .receipt-footer {
                text-align: center !important;
                font-size: 8pt !important;
                margin-top: 3mm !important;
                line-height: 1.1 !important;
            }
            
            .thank-you-msg {
                font-weight: bold !important;
                margin-bottom: 1mm !important;
            }
            
            .store-name {
                font-size: 9pt !important;
                font-weight: bold !important;
                margin: 1mm 0 !important;
            }
        }
    </style>

    <div x-data="posSystem()" x-cloak class="flex h-screen bg-gray-50">
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-x-hidden">
            <!-- Top Navigation Bar - Enhanced UI -->
            <div class="bg-white border-b border-gray-200 px-4 py-2 sticky top-0 z-10 shadow-sm">
                <div class="flex items-center justify-between">
                    <!-- Toggle for sidebar - Will dispatch events to parent -->
                    <button @click="$dispatch('sidebar-toggle')"
                        class="text-gray-600 p-2 rounded-full hover:bg-gray-100 mr-2 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Logo and Brand - Enhanced design -->
                    <div class="flex items-center">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center w-10 h-10 shadow-sm">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="ml-2 font-bold text-xl text-gray-900">Eldo<span class="text-orange-500">Gas</span>
                            <span class="text-gray-700">POS</span></span>
                    </div>

                    <!-- Enhanced Search bar -->
                    <div class="flex-1 max-w-xl mx-auto px-4">
                        <div class="relative">
                            <input type="text" x-model="searchQuery" @input="filterProducts"
                                placeholder="Search products by name, SKU or serial number..."
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm transition-all">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Categories dropdown - Improved design -->
                    <button @click="toggleCategories"
                        class="flex items-center text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 mx-2 transition-all relative">
                        <span class="hidden md:inline font-medium">Categories</span>
                        <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                        <!-- Category indicator dot -->
                        <span x-show="currentCategory !== null" class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-orange-500"></span>
                    </button>

                    <!-- User menu - Enhanced design -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-1 focus:outline-none relative group">
                            <div
                                class="w-9 h-9 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 flex items-center justify-center text-white font-medium shadow-sm transform group-hover:scale-105 transition-transform">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <svg class="w-5 h-5 text-gray-500 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 overflow-hidden">
                            <div class="py-1">
                                <div class="px-4 py-3 border-b">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Logged in as Admin</p>
                                </div>
                                <a href="#"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500 transition">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Settings
                                    </div>
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-500 transition">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Sign out
                                        </div>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex">
                <!-- Products Grid - Enhanced with better UX -->
                <div class="flex-1 p-4 overflow-y-auto">
                    <!-- Loading Spinner - Improved -->
                    <div x-show="isLoading" class="flex justify-center items-center h-64">
                        <div class="relative">
                            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-orange-500"></div>
                            <div class="absolute top-0 left-0 right-0 bottom-0 flex items-center justify-center">
                                <div class="h-10 w-10 rounded-full bg-white shadow-sm"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Category Pills - Mobile Only -->
                    <div class="md:hidden overflow-x-auto pb-4 mb-4 flex space-x-2 -mx-2 px-2">
                        <button @click="currentCategory = null"
                            class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium category-pill shadow-sm"
                            :class="currentCategory === null ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white' :
                                'bg-white text-gray-700 border border-gray-300'">
                            All Products
                        </button>
                        @foreach ($categories ?? [] as $category)
                            <button @click="currentCategory = {{ $category->id }}"
                                class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium category-pill shadow-sm"
                                :class="currentCategory === {{ $category->id }} ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white' :
                                    'bg-white text-gray-700 border border-gray-300'">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Enhanced Category dropdown for desktop -->
                    <div x-show="showCategoryDrawer" @click.away="showCategoryDrawer = false" class="hidden md:block fixed inset-0 z-40" style="display: none;">
                        <div class="absolute inset-0 bg-black opacity-25" @click="showCategoryDrawer = false"></div>
                        <div class="absolute top-16 right-4 w-64 bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="py-2">
                                <div class="px-4 py-2 font-semibold text-gray-700 bg-gray-50 border-b">Categories</div>
                                <button @click="currentCategory = null; showCategoryDrawer = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 transition"
                                    :class="currentCategory === null ? 'bg-orange-50 text-orange-700 font-medium' : ''">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                        All Products
                                    </div>
                                </button>
                                @foreach ($categories ?? [] as $category)
                                    <button @click="currentCategory = {{ $category->id }}; showCategoryDrawer = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 transition"
                                        :class="currentCategory === {{ $category->id }} ?
                                            'bg-orange-50 text-orange-700 font-medium' : ''">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            {{ $category->name }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Current Category Display -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <template x-if="currentCategory === null">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    All Products
                                </span>
                            </template>
                            <template x-if="currentCategory !== null">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <span x-text="getCategoryName(currentCategory)"></span>
                                </span>
                            </template>
                        </h2>
                        <span class="text-sm bg-gray-100 px-3 py-1 rounded-full text-gray-600 font-medium" x-text="filteredProducts.length + ' items'"></span>
                    </div>

                    <!-- Enhanced Empty state -->
                    <div x-show="!isLoading && filteredProducts.length === 0"
                        class="flex flex-col items-center justify-center h-64 bg-white rounded-lg shadow-sm p-8">
                        <div class="w-20 h-20 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-orange-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-700 text-lg font-medium">No products found</p>
                        <p class="text-gray-500 mt-1 text-center">Try a different search term or category</p>
                        <button @click="searchQuery = ''; currentCategory = null" class="mt-4 px-4 py-2 bg-orange-100 text-orange-600 rounded-md hover:bg-orange-200 transition-colors font-medium text-sm">
                            Reset Filters
                        </button>
                    </div>

                    <!-- Enhanced Product Grid -->
                    <div x-show="!isLoading && filteredProducts.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div class="product-card bg-white rounded-lg shadow-sm border border-gray-100 flex flex-col overflow-hidden">
                                <!-- Clickable Image Container -->
                                <div class="image-container cursor-pointer" @click="addToCart(product)">
                                    <img :src="product.image" :alt="product.name"
                                        class="w-full h-52 object-contain p-4 transition-all">
                                    
                                    <!-- Stock Badge -->
                                    <div class="stock-badge"
                                        :class="product.stock > product.min_stock ? 'bg-green-100 text-green-800' : 
                                               (product.stock > 0 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')">
                                        <template x-if="product.stock > product.min_stock">
                                            <span>In Stock</span>
                                        </template>
                                        <template x-if="product.stock <= product.min_stock && product.stock > 0">
                                            <span>Low Stock</span>
                                        </template>
                                        <template x-if="product.stock <= 0">
                                            <span>Out of Stock</span>
                                        </template>
                                    </div>
                                    
                                    <!-- Image Overlay with Add to Cart Button -->
                                    <div class="image-overlay">
                                        <button class="bg-white rounded-full p-3 shadow-lg transform transition-transform hover:scale-110"
                                            :disabled="product.stock <= 0">
                                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="p-4 flex-1 flex flex-col">
                                    <!-- Category Tag -->
                                    <div class="mb-2">
                                        <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700" x-text="product.category_name"></span>
                                    </div>
                                    
                                    <!-- Product Name -->
                                    <h3 class="text-xs font-semibold text-gray-900 mb-2 leading-tight hover:text-orange-600 cursor-pointer" 
                                        @click="addToCart(product)" x-text="product.name"></h3>
                                    
                                    

                                    <div class="flex justify-between items-center mb-3 mt-auto">
                                        <span class="font-bold text-xs text-orange-600 price-tag"
                                            x-text="'KSh ' + product.price.toFixed(0)"></span>
                                        
                                        <!-- Quick Add Button -->
                                        <button @click="addToCart(product)" :disabled="product.stock <= 0"
                                            class="bg-orange-500 text-white p-2 rounded-full hover:bg-orange-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Add to Cart Button -->
                                    <button @click="addToCart(product)" :disabled="product.stock <= 0"
                                        class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-2 px-4 rounded-md text-sm font-medium flex items-center justify-center hover:from-orange-600 hover:to-orange-700 transition-all disabled:from-gray-300 disabled:to-gray-300 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Redesigned Cart Section with Fixed Height -->
                <div class="w-80 bg-white shadow-md border-l border-gray-200 cart-wrapper">
                    <!-- Cart Header -->
                    <div class="p-3 border-b flex items-center justify-between bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                        <h2 class="text-lg font-bold flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Shopping Cart
                        </h2>
                        <span x-show="cart.length > 0" 
                            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white text-xs px-2 py-1 rounded-full flex items-center">
                            <span x-text="cart.length"></span>
                            <span class="ml-1">items</span>
                        </span>
                    </div>

                    <!-- Cart Totals Summary (visible always) -->
                    <div class="p-3 bg-gray-50 border-b flex justify-between items-center text-sm">
                        <div>
                            <span class="text-gray-600">Total:</span>
                            <span x-text="'KSh ' + total.toFixed(0)" class="font-bold text-orange-600 ml-1"></span>
                        </div>
                        <span x-show="cart.length > 0" class="text-gray-600">
                            <span x-text="cart.reduce((sum, item) => sum + item.quantity, 0)"></span> units
                        </span>
                    </div>

                    <!-- Cart Items Container - Scrollable -->
                    <div class="cart-items-container p-3">
                        <!-- Empty Cart State -->
                        <template x-if="cart.length === 0">
                            <div class="empty-cart-message flex flex-col items-center justify-center text-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-700 font-medium mb-1">Your cart is empty</p>
                                <p class="text-gray-500 text-sm px-4">Add products to start a new sale</p>
                            </div>
                        </template>

                        <!-- Cart Items List -->
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="cart-item mb-3 p-3 bg-white border border-gray-200 rounded-lg shadow-sm relative">
                                <!-- Remove Button - Absolute positioned -->
                                <button @click="removeFromCart(index)"
                                    class="absolute top-2 right-2 text-red-400 hover:text-red-600 h-6 w-6 flex items-center justify-center rounded-full hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                
                                <!-- Item Header -->
                                <div class="pr-8 mb-2">
                                    <h3 x-text="item.name" class="font-medium text-gray-900 hover:text-orange-600 cursor-pointer"></h3>
                                    <div class="flex items-center mt-1 text-xs">
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 mr-2" x-text="item.category_name"></span>
                                        <span x-show="item.serial_number" class="text-gray-600" x-text="'S/N: ' + item.serial_number"></span>
                                    </div>
                                </div>
                                
                                <!-- Item Price and Quantity -->
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center space-x-1 bg-gray-100 rounded-md overflow-hidden">
                                        <button @click="updateQuantity(index, -1)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-200 focus:outline-none transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span x-text="item.quantity" class="w-8 text-center text-sm font-medium"></span>
                                        <button @click="updateQuantity(index, 1)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-200 focus:outline-none transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Price Info -->
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500" x-text="'KSh ' + item.price.toFixed(0) + ' × ' + item.quantity"></div>
                                        <div class="font-bold text-orange-600" x-text="'KSh ' + (item.price * item.quantity).toFixed(0)"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Payment Section - Always at the bottom -->
                    <div class="cart-payment-section">
                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <div class="flex space-x-2">
                                <button @click="paymentMethod = 'cash'"
                                    class="flex-1 py-2 px-3 rounded-md text-sm border-2 transition-colors flex items-center justify-center"
                                    :class="paymentMethod === 'cash' ? 'border-orange-500 text-orange-600 bg-orange-50' :
                                        'border-gray-300 text-gray-700 bg-white hover:border-gray-400'">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                    Cash
                                </button>
                                <button @click="paymentMethod = 'credit'"
                                    class="flex-1 py-2 px-3 rounded-md text-sm border-2 transition-colors flex items-center justify-center"
                                    :class="paymentMethod === 'credit' ? 'border-orange-500 text-orange-600 bg-orange-50' :
                                        'border-gray-300 text-gray-700 bg-white hover:border-gray-400'">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Credit
                                </button>
                            </div>
                        </div>

                        <!-- Customer Details (for credit) -->
                        <div x-show="paymentMethod === 'credit'" x-transition class="mb-4">
                            <div class="space-y-2">
                                <div class="relative">
                                    <input type="text" x-model="customerDetails.name" placeholder="Customer Name"
                                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="relative">
                                    <input type="text" x-model="customerDetails.phone" placeholder="Phone Number"
                                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <button @click="processSale" :disabled="!canCheckout"
                            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3 px-4 rounded-md hover:from-orange-600 hover:to-orange-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center font-medium transition-all shadow-sm">
                            <svg x-show="isProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="isProcessing ? 'Processing...' : 'Complete Sale'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Receipt Modal - Screen version (hidden during printing) - Now dismissible when clicking outside -->
        <div x-show="showReceipt" @click.self="closeReceipt"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 print:hidden"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[90vh] overflow-y-auto relative receipt-animation"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                
                <!-- Close button -->
                <button @click="closeReceipt" class="absolute top-3 right-3 z-10 text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Receipt Header -->
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            Receipt
                        </h2>
                        <div class="flex items-center">
                            <span class="text-sm bg-white text-orange-600 px-2 py-1 rounded-full font-medium">#<span x-text="receiptNumber"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Receipt Content - Enhanced Screen View -->
                <div class="p-5">
                    <div class="text-center mb-4">
                        <h3 class="font-bold text-xl text-gray-900">{{ config('app.name', 'EldoGas') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">Tel: +254 700 123456</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Receipt #</p>
                            <p class="font-medium text-gray-800" x-text="receiptNumber"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Date</p>
                            <p class="font-medium text-gray-800" x-text="(new Date()).toLocaleDateString()"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Cashier</p>
                            <p class="font-medium text-gray-800">{{ auth()->user()->name }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Payment</p>
                            <p class="font-medium text-gray-800" x-text="paymentMethod === 'cash' ? 'Cash' : 'Credit'"></p>
                        </div>
                    </div>

                    <div class="border-t border-b border-gray-200 py-4 mb-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Items
                        </h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800" x-text="item.name"></p>
                                        <p x-show="item.serial_number" class="text-xs text-gray-500" x-text="'S/N: ' + item.serial_number"></p>
                                        <p class="text-xs text-gray-600 mt-1"
                                            x-text="item.quantity + ' × KSh ' + item.price.toFixed(0)"></p>
                                    </div>
                                    <p class="font-medium text-gray-800" x-text="'KSh ' + (item.quantity * item.price).toFixed(0)">
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mb-4 bg-gray-50 p-3 rounded-lg">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Subtotal:</span>
                            <span x-text="'KSh ' + subtotal.toFixed(0)" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium">KSh 0.00</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200">
                            <span>Total:</span>
                            <span x-text="'KSh ' + total.toFixed(0)" class="text-orange-600"></span>
                        </div>
                    </div>

                    <template x-if="paymentMethod === 'credit'">
                        <div class="bg-orange-50 p-3 rounded-lg border border-orange-100 mb-4">
                            <h5 class="font-medium text-orange-800 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Customer Information
                            </h5>
                            <div class="pl-2">
                                <p class="text-sm flex items-center text-orange-700 mb-1">
                                    <span class="font-medium mr-2">Name:</span> 
                                    <span x-text="customerDetails.name"></span>
                                </p>
                                <p class="text-sm flex items-center text-orange-700">
                                    <span class="font-medium mr-2">Phone:</span> 
                                    <span x-text="customerDetails.phone"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <div class="text-center text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                        <p class="font-semibold text-gray-700">Thank you for your business!</p>
                        <p class="mt-1">Keep this receipt for any returns or exchanges.</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between p-4 bg-gray-50 rounded-b-lg border-t">
                    <button @click="printReceipt()"
                        class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-md hover:from-orange-600 hover:to-orange-700 focus:outline-none transition-all shadow-sm flex items-center">
                        <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Receipt
                    </button>
                    <button @click="closeReceipt"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none transition-colors flex items-center">
                        <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        New Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- Improved Printable Receipt Structure (unchanged for print functionality) -->
        <div x-show="showReceipt" class="printable-receipt" style="display: none;">
            <div class="receipt-container">
                <!-- Company name and info -->
                <div class="company-name">Eldogas</div>
                <div class="company-info">Tel:+254724556855</div>
                <div class="separator-line">****************************</div>
                
                <!-- Receipt details -->
                <div class="transaction-info">
                    <div class="transaction-label">RECEIPT #:</div>
                    <div class="transaction-value" x-text="receiptNumber"></div>
                </div>
                <div class="transaction-info">
                    <div class="transaction-label">DATE:</div>
                    <div class="transaction-value" x-text="new Date().toLocaleDateString('en-GB')"></div>
                </div>
                <div class="transaction-info">
                    <div class="transaction-label">TIME:</div>
                    <div class="transaction-value" x-text="new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></div>
                </div>
                <div class="transaction-info">
                    <div class="transaction-label">PAYMENT:</div>
                    <div class="transaction-value" x-text="paymentMethod === 'cash' ? 'Cash' : 'Credit'"></div>
                </div>
                <div class="transaction-info">
                    <div class="transaction-label">CASHIER:</div>
                    <div class="transaction-value">{{ substr(auth()->user()->name, 0, 10) }}</div>
                </div>
                <div class="separator-line">----------------------------</div>
                
                <!-- Items section -->
                <div class="section-title">ITEMS</div>
                <table class="items-table">
                    <tr class="items-header">
                        <td class="item-name">ITEM</td>
                        <td class="item-qty">QTY</td>
                        <td class="item-price">PRICE</td>
                        <td class="item-total">TOTAL</td>
                    </tr>
                    <template x-for="(item, index) in cart" :key="index">
                        <tr>
                            <td class="item-name">
                                <div x-text="item.name"></div>
                                <div x-show="item.serial_number" class="serial-number" x-text="'S/N:' + item.serial_number"></div>
                            </td>
                            <td class="item-qty" x-text="item.quantity"></td>
                            <td class="item-price" x-text="item.price.toFixed(0)"></td>
                            <td class="item-total" x-text="(item.quantity * item.price).toFixed(0)"></td>
                        </tr>
                    </template>
                </table>
                <div class="separator-line">----------------------------</div>
                
                <!-- Totals section -->
                <div class="totals-section">
                    <div class="totals-label">SUBTOTAL:</div>
                    <div class="totals-value" x-text="'KSH ' + subtotal.toFixed(0)"></div>
                </div>
                <div class="totals-section">
                    <div class="totals-label">TAX (0%):</div>
                    <div class="totals-value">KSH 0</div>
                </div>
                <div class="totals-section grand-total">
                    <div class="totals-label">TOTAL:</div>
                    <div class="totals-value" x-text="'KSH ' + total.toFixed(0)"></div>
                </div>
                
                <!-- Customer section for credit payments -->
                <template x-if="paymentMethod === 'credit'">
                    <div class="customer-section">
                        <div class="separator-line">----------------------------</div>
                        <div class="customer-section-title">CUSTOMER DETAILS</div>
                        <div class="customer-info">
                            <div class="customer-label">Name:</div>
                            <div x-text="customerDetails.name"></div>
                        </div>
                        <div class="customer-info">
                            <div class="customer-label">Phone:</div>
                            <div x-text="customerDetails.phone"></div>
                        </div>
                    </div>
                </template>
                
                <!-- Footer -->
                <div class="receipt-footer">
                    <div class="thank-you-msg">Thank you for your business!</div>
                    <div>Keep receipt for exchanges</div>
                    <div class="store-name">*Eldogas*</div>
                    <div x-text="new Date().toLocaleDateString('en-GB')"></div>
                </div>
            </div>
        </div>

        <!-- Enhanced Error Modal -->
        <div x-show="showError && _initialized && errorMessage" @click.self="showError = false"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 text-red-500 mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-center mb-2">Error Processing Sale</h3>
                <div class="text-gray-600 text-center mb-6 bg-red-50 p-3 rounded-lg border border-red-100"
                    x-text="errorMessage || 'An unexpected error occurred while processing your sale.'"></div>

                <button @click="showError = false"
                    class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-md hover:from-red-600 hover:to-red-700 focus:outline-none transition-all shadow-sm">
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
                    showError: false, // Explicitly set to false
                    receiptNumber: '',
                    errorMessage: '',
                    isLoading: false,
                    isProcessing: false,
                    subtotal: 0,
                    total: 0,
                    _initialized: false, // Track initialization state

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
                                (product.sku && product.sku.toLowerCase().includes(query)) ||
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
                        console.log('Initializing POS System...');
                        
                        // Ensure error modal is hidden during initialization
                        this.showError = false;
                        this.errorMessage = '';
                        
                        // Reset state
                        this.cart = [];
                        this.paymentMethod = 'cash';
                        this.customerDetails = {
                            name: '',
                            phone: ''
                        };
                        
                        this.updateTotals();
                        
                        // Mark as initialized
                        this._initialized = true;
                        
                        console.log('POS System Initialized');

                        // Dispatch event to parent layout indicating we're on the dashboard
                        window.dispatchEvent(new CustomEvent('on-dashboard'));
                        
                        // Force hide error modal after a brief delay
                        setTimeout(() => {
                            if (this.showError && !this.errorMessage) {
                                console.log('Hiding stale error modal');
                                this.showError = false;
                            }
                        }, 100);
                    },

                    toggleCategories() {
                        this.showCategoryDrawer = !this.showCategoryDrawer;
                    },

                    getCategoryName(categoryId) {
                        const categories = @json($categories ?? []);
                        const category = categories.find(c => c.id === categoryId);
                        return category ? category.name : 'Unknown Category';
                    },

                    addToCart(product) {
                        // Check if product has stock
                        if (product.stock <= 0) {
                            this.showError = true;
                            this.errorMessage = 'This product is out of stock.';
                            // Auto-close error after 3 seconds
                            setTimeout(() => {
                                this.showError = false;
                            }, 3000);
                            return;
                        }

                        const existingIndex = this.cart.findIndex(item => item.id === product.id);

                        if (existingIndex >= 0) {
                            // Check if adding one more would exceed stock
                            if (this.cart[existingIndex].quantity + 1 > product.stock) {
                                this.showError = true;
                                this.errorMessage = `Cannot add more. Only ${product.stock} available in stock.`;
                                return;
                            }

                            this.cart[existingIndex].quantity += 1;
                        } else {
                            this.cart.push({
                                ...product,
                                quantity: 1,
                                serial_number: product.serial_number || null
                            });
                        }

                        this.updateTotals();
                        
                        // Show a brief notification or animation to confirm product added
                        this.showAddedToCartNotification(product.name);
                    },
                    
                    showAddedToCartNotification(productName) {
                        // This could be enhanced with a toast notification library
                        console.log(`${productName} added to cart`);
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
                                this.showError = true;
                                this.errorMessage = `Cannot add more. Only ${product.stock} available in stock.`;
                                // Auto-close error after 3 seconds
                                setTimeout(() => {
                                    this.showError = false;
                                }, 3000);
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

                        try {
                            const response = await fetch('{{ route('pos.sales.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    cart_items: this.cart,
                                    payment_method: this.paymentMethod,
                                    customer_details: this.paymentMethod === 'credit' ? this
                                        .customerDetails : null
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                this.receiptNumber = result.receipt_number;
                                this.showReceipt = true;

                                // Debug log to verify receipt data
                                console.log('Receipt data:', {
                                    items: this.cart,
                                    total: this.total,
                                    receiptNumber: this.receiptNumber
                                });
                            } else {
                                this.errorMessage = result.message || 'Error processing sale';
                                this.showError = true;
                            }
                        } catch (error) {
                            console.error('Sale exception:', error);
                            this.errorMessage = 'Network error or server exception occurred.';
                            this.showError = true;
                            // Auto-close error after 5 seconds
                            setTimeout(() => {
                                this.showError = false;
                            }, 5000);
                        } finally {
                            this.isProcessing = false;
                        }
                    },

                    printReceipt() {
                        // Force the receipt to be visible for printing
                        this.showReceipt = true;
                        const receiptElement = document.querySelector('.printable-receipt');

                        // Make the receipt visible temporarily for printing
                        if (receiptElement) {
                            receiptElement.style.display = 'block';
                        }

                        // Debug information
                        console.log('Receipt data:', {
                            items: this.cart,
                            total: this.total,
                            receipt: receiptElement,
                            visibility: receiptElement ? window.getComputedStyle(receiptElement).display : 'not found'
                        });

                        // Give the browser more time to render before printing
                        setTimeout(() => {
                            window.print();
                            // After printing finishes (user closes dialog), continue showing the receipt on screen
                            // The CSS will handle hiding everything else during actual printing
                        }, 300);
                    },

                    closeReceipt() {
                        this.showReceipt = false;
                        this.cart = [];
                        this.updateTotals();
                        this.paymentMethod = 'cash';
                        this.customerDetails = {
                            name: '',
                            phone: ''
                        };

                        // Reset the receipt element display style
                        const receiptElement = document.querySelector('.printable-receipt');
                        if (receiptElement) {
                            receiptElement.style.display = 'none';
                        }
                    }
                }
            }
        </script>
        
        <!-- Development Error Testing Helper (Remove in Production) -->
        @if(config('app.debug'))
        <script src="{{ asset('js/pos-error-tester.js') }}"></script>
        @endif
    </div>
</x-app-layout>
