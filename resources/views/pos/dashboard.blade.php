<!-- resources/views/pos/dashboard.blade.php - Enhanced with Offline Capability -->
<x-app-layout>
    <!-- CSRF Token for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if(config('offline.enabled'))
    <!-- Offline Mode Styles -->
    <link rel="stylesheet" href="{{ asset('css/offline.css') }}">
    @endif

    <!-- Enhanced Print Styles for 57mm thermal receipt -->
    <style>
        /* Hide Alpine.js elements until ready */
        [x-cloak] { 
            display: none !important;
        }
        /* ============================================
           PROFESSIONAL PRODUCT CARD DESIGN
           ============================================ */
        
        /* Product Grid Optimization */
        .product-grid {
            display: grid;
            gap: 1rem;
            padding: 1rem;
        }

        /* Responsive grid for POS screens */
        @media (min-width: 640px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .product-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 1280px) {
            .product-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (min-width: 1536px) {
            .product-grid { grid-template-columns: repeat(5, 1fr); }
        }

        /* Professional Product Card */
        .product-card {
            position: relative;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            /*
                A tile is a button, not text. Without this an impatient second
                tap highlights the product name instead of doing nothing, which
                is what it looked like when the old double-click "did nothing".
            */
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -8px rgba(249, 115, 22, 0.2);
            border-color: #fb923c;
        }

        /* Out of stock overlay */
        .product-card.out-of-stock {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .product-card.out-of-stock::after {
            content: 'OUT OF STOCK';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            background: rgba(239, 68, 68, 0.95);
            color: white;
            padding: 0.5rem 2rem;
            font-weight: 700;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            border-radius: 4px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Stock Level Display Styles */
        /* Image Section - Optimized */
        .product-image-wrapper {
            position: relative;
            width: 100%;
            padding-top: 85%; /* Optimized aspect ratio for POS */
            background: linear-gradient(to bottom, #f9fafb, #ffffff);
            overflow: hidden;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 1rem;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        /* Stock Badge - Modern & Sleek Design */
        .stock-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 700;
            z-index: 5;
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border: 1px solid;
        }

        .stock-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .stock-badge.high {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stock-badge.low {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .stock-badge.out {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .stock-icon {
            width: 0.75rem;
            height: 0.75rem;
            flex-shrink: 0;
        }

        .stock-text {
            white-space: nowrap;
            font-size: 0.625rem;
            line-height: 1;
        }

        /* Offline Badge */
        .offline-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: rgba(99, 102, 241, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            z-index: 5;
            backdrop-filter: blur(8px);
        }

        /* Content Section - Clean Layout */
        .product-content {
            padding: 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-grow: 1;
        }

        /* Product Name */
        .product-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            line-height: 1.3;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.275em;
        }

        .product-card:hover .product-name {
            color: #f97316;
        }

        /* Price and Action Row */
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 0.5rem;
            border-top: 1px solid #f3f4f6;
        }

        .product-price {
            font-size: 1.125rem;
            font-weight: 700;
            color: #f97316;
            letter-spacing: -0.025em;
        }

        /* Add to Cart Button - Simplified */
        .add-to-cart-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
        }

        .add-to-cart-btn:hover:not(:disabled) {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
        }

        .add-to-cart-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        .add-to-cart-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            box-shadow: none;
        }

        .add-icon {
            width: 1.25rem;
            height: 1.25rem;
            stroke-width: 2.5;
        }

        /* Quantity stepper - replaces the add-to-cart button on each tile. */
        .qty-stepper {
            display: flex;
            align-items: center;
            gap: 0.125rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.125rem;
        }

        .qty-btn {
            width: 1.75rem;
            height: 1.75rem;
            border: none;
            border-radius: 0.375rem;
            background: #fff;
            color: #374151;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color .12s, color .12s;
        }

        .qty-btn:hover:not(:disabled) { background: #f97316; color: #fff; }
        .qty-btn:disabled { opacity: .35; cursor: not-allowed; }

        /*
            The tile's two explicit actions: Pick-up (book out to a rider) and
            Print (sell and print the receipt). Side by side and equal width -
            neither is the primary action, and a thumb should not have to aim.
        */
        .tile-actions {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.4rem;
        }
        .tile-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            flex: 1 1 0;
            min-width: 0;
            padding: 0.4rem 0.35rem;
            border: 1px dashed #c4b5fd;
            border-radius: 0.5rem;
            background: #f5f3ff;
            color: #6d28d9;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color .12s, color .12s, border-color .12s;
        }
        .tile-btn svg { width: .9rem; height: .9rem; flex-shrink: 0; }
        .tile-btn:hover:not(:disabled) { background: #7c3aed; border-color: #7c3aed; color: #fff; }
        .tile-btn:disabled { opacity: .35; cursor: not-allowed; }

        /*
            Rider picker. Scoped CSS rather than utility classes, like the
            confirm dialog: it must render correctly on a build that has not
            been regenerated since these classes were added.
        */
        .rider-modal__head {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
            padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb;
        }
        .rider-modal__close {
            border: none; background: #f3f4f6; color: #6b7280; width: 2rem; height: 2rem;
            border-radius: 9999px; font-size: 1.25rem; line-height: 1; cursor: pointer;
        }
        .rider-modal__close:hover { background: #e5e7eb; color: #111827; }
        .rider-modal__empty { padding: 2rem 1.25rem; text-align: center; color: #6b7280; font-size: .875rem; }
        .rider-list { max-height: 60vh; overflow-y: auto; padding: .5rem; }
        .rider-item { margin-bottom: .35rem; }

        .rider-row {
            display: flex; align-items: stretch; width: 100%;
            border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff;
            overflow: hidden;
            transition: border-color .12s, background-color .12s;
        }
        .rider-row:hover { border-color: #7c3aed; }
        .rider-row.is-busy { border-color: #7c3aed; background: #faf5ff; }

        /* The row itself assigns; the icon beside it opens the number field. */
        .rider-row__main {
            display: flex; align-items: center; gap: .75rem; flex: 1; min-width: 0;
            padding: .65rem .75rem; border: none; background: transparent;
            text-align: left; cursor: pointer; font: inherit;
            transition: background-color .12s;
        }
        .rider-row__main:hover:not(:disabled) { background: #faf5ff; }
        .rider-row__main:disabled { cursor: wait; opacity: .7; }

        .rider-row__num {
            flex-shrink: 0; width: 3rem; border: none; border-left: 1px solid #e5e7eb;
            background: #fafafa; color: #9ca3af; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background-color .12s, color .12s;
        }
        .rider-row__num svg { width: 1.1rem; height: 1.1rem; }
        .rider-row__num:hover:not(:disabled) { background: #ede9fe; color: #6d28d9; }
        .rider-row__num.is-active { background: #7c3aed; color: #fff; border-left-color: #7c3aed; }
        .rider-row__num:disabled { opacity: .4; cursor: not-allowed; }

        .rider-cust {
            padding: .6rem .75rem .7rem;
            border: 1px solid #ddd6fe; border-top: none;
            border-radius: 0 0 .75rem .75rem; background: #faf5ff;
            margin: -.35rem .5rem 0;
        }
        .rider-cust__row { display: flex; gap: .4rem; }
        .rider-cust__input {
            flex: 1; min-width: 0; padding: .45rem .6rem;
            border: 1px solid #c4b5fd; border-radius: .5rem;
            font-size: .85rem; background: #fff; color: #111827;
        }
        .rider-cust__input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,.18); }
        .rider-cust__send {
            flex-shrink: 0; padding: .45rem .9rem; border: none; border-radius: .5rem;
            background: #7c3aed; color: #fff; font-size: .8rem; font-weight: 700; cursor: pointer;
        }
        .rider-cust__send:hover:not(:disabled) { background: #6d28d9; }
        .rider-cust__send:disabled { opacity: .6; cursor: wait; }
        .rider-cust__hint { margin-top: .35rem; font-size: .7rem; color: #7c6f9a; }
        .rider-cust__error { margin-top: .35rem; font-size: .72rem; font-weight: 600; color: #b91c1c; }
        .rider-row__avatar {
            flex-shrink: 0; width: 2.25rem; height: 2.25rem; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            background: #ede9fe; color: #6d28d9; font-weight: 800;
        }
        .rider-row.is-out .rider-row__avatar { background: #fef3c7; color: #b45309; }
        .rider-row__body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .rider-row__name { font-weight: 700; color: #111827; font-size: .9rem; }
        .rider-row__phone { color: #6b7280; font-size: .75rem; }
        .rider-row__pill {
            flex-shrink: 0; padding: .2rem .6rem; border-radius: 9999px;
            font-size: .7rem; font-weight: 700; white-space: nowrap;
        }
        .rider-row__pill.free { background: #dcfce7; color: #15803d; }
        .rider-row__pill.out { background: #fef3c7; color: #b45309; }
        .rider-modal__foot {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .75rem 1.25rem; border-top: 1px solid #e5e7eb;
            background: #f9fafb; color: #6b7280; font-size: .75rem;
        }
        .rider-modal__foot a { color: #ea580c; font-weight: 700; white-space: nowrap; }
        .rider-modal__foot a:hover { text-decoration: underline; }
        .rider-modal__foot-icon {
            display: inline-block; width: .8rem; height: .8rem;
            vertical-align: -1px; color: #7c3aed;
        }
        .rider-modal__done { padding: 2rem 1.25rem; text-align: center; }
        .rider-modal__tick {
            width: 3.5rem; height: 3.5rem; margin: 0 auto 1rem; border-radius: 9999px;
            background: #dcfce7; color: #15803d; font-size: 1.75rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .rider-modal__done h4 { font-size: 1.125rem; font-weight: 800; color: #111827; }
        .rider-modal__done .ref { margin: .25rem 0 .5rem; font-family: ui-monospace, monospace; font-weight: 700; color: #6d28d9; }
        .rider-modal__done p { color: #6b7280; font-size: .875rem; }
        .rider-modal__done p.cust {
            margin-top: .4rem; font-weight: 700; color: #6d28d9; font-size: .8rem;
        }
        .rider-modal__done button {
            margin-top: 1.25rem; width: 100%; padding: .6rem 1rem; border: none; border-radius: .5rem;
            background: #7c3aed; color: #fff; font-weight: 700; cursor: pointer;
        }
        .rider-modal__done button:hover { background: #6d28d9; }

        .qty-value {
            min-width: 1.5rem;
            text-align: center;
            font-size: .875rem;
            font-weight: 700;
            color: #111827;
        }

        /*
            While a sale is in flight the tile is dimmed and stops taking
            pointer events, so an impatient second tap cannot queue another
            sale. The idempotency key is the real guarantee; this is the part
            the cashier can see.
        */
        .product-card.is-selling {
            opacity: .55;
            pointer-events: none;
            outline: 2px solid #f97316;
            outline-offset: -2px;
        }
        
        /* Cart Item Styles */
        .cart-item {
            transition: all 0.2s ease;
        }
        
        .cart-item:hover {
            background-color: #f9fafb;
        }
        
        /* Category Pills */
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

    </style>

    <!-- Set offline mode configuration and data -->
    <script>
        window.offlineModeEnabled = {{ config('offline.enabled') ? 'true' : 'false' }};
        window.posCategories = @json($categories ?? []);
        window.cylinderStats = @json($cylinderStats ?? ['active_drop_offs' => 0, 'active_advance_collections' => 0]);
        window.authUserId = {{ auth()->id() }};
    </script>
    
    {{--
        public/js/pos-system.js is deliberately NOT loaded.

        It declares enhancedPosSystem() at global scope, but this file declares
        it again further down, so the inline version always overwrote it before
        Alpine evaluated x-data. The file was 614 lines downloaded on every POS
        load and never executed - and its stale copy still called
        /api/v1/customers and /api/v1/products/{id}/stock, neither of which
        exists. Editing it looked like it should work, which is worse than it
        simply being absent.
    --}}

    <div x-data="enhancedPosSystem()" x-cloak class="flex h-screen bg-gray-50">
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-x-hidden">
            <!-- Top Navigation Bar - Enhanced with Offline Status -->
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
                        <span class="ml-2 font-bold text-xl text-gray-900">Eldo<span class="text-orange-500">Gas</span></span>
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

                    <!-- Connection Status & Sync Indicator -->
                    @if(config('offline.enabled'))
                    <div class="flex items-center space-x-3 mr-3">
                        <!-- Connection Status -->
                        <div id="connection-status" 
                             class="connection-status px-3 py-1.5 rounded-full text-sm font-medium flex items-center"
                             :class="isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            <div class="w-2 h-2 rounded-full mr-2"
                                 :class="isOnline ? 'bg-green-500' : 'bg-red-500'">
                            </div>
                            <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                        </div>

                        <!-- Enhanced Sync Status Button - Always Visible -->
                        <button @click="toggleSyncStatus" 
                                class="relative inline-flex items-center px-3 py-1.5 rounded-md transition-all duration-200 font-medium text-sm"
                                :class="pendingSyncCount > 0 ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                title="View sync status and pending operations">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Sync</span>
                            <span x-show="pendingSyncCount > 0" 
                                  class="ml-1.5 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-500 text-white"
                                  x-text="pendingSyncCount">
                            </span>
                        </button>
                    </div>
                    @endif

                    <!-- Quick Stats - Replacing old dropdown -->
                    <div class="flex items-center space-x-3">
                        <!-- Cylinder Management Quick Access -->
                        <a href="{{ route('pos.cylinders.index') }}" class="hidden md:flex items-center text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="font-medium">Cylinders</span>
                            @if($cylinderStats['active_drop_offs'] + $cylinderStats['active_advance_collections'] > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded-full bg-purple-500 text-white">
                                    {{ $cylinderStats['active_drop_offs'] + $cylinderStats['active_advance_collections'] }}
                                </span>
                            @endif
                        </a>
                        
                        <!-- Rider Cylinder Management Quick Access -->
                        <a href="{{ route('pos.riders.index') }}" class="hidden md:flex items-center text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                            <span class="font-medium">Riders</span>
                        </a>

                        {{--
                            Today's sales.

                            Green rather than purple on purpose: the other two
                            badges count stock on hand, this one counts money
                            taken. Same colour would read as a third stock
                            figure at a glance.

                            The count is live - see todaySalesCount. A number
                            rendered once at page load would be wrong by the
                            second sale, because this screen never reloads.
                        --}}
                        <a href="{{ route('pos.sales.history') }}"
                           class="hidden md:flex items-center text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 transition-all"
                           :title="'Today: KSh ' + todaySalesAmountFormatted">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">Sales</span>
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded-full bg-green-500 text-white"
                                  x-text="todaySalesCount"></span>
                        </a>

                        <!-- Inventory Overview -->
                        <a href="{{ route('admin.products.index') }}" class="hidden md:flex items-center text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="font-medium">Inventory</span>
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded-full bg-purple-500 text-white" x-text="totalInventoryStock">
                            </span>
                        </a>
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

            @if(config('offline.enabled'))
            <!-- Offline Mode Indicator -->
            <div x-show="!isOnline" class="offline-mode-indicator">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="font-medium">Offline Mode Active</span>
                </div>
            </div>
            @endif

            @if(config('offline.enabled'))
            <!-- Sync Status Panel -->
            <div x-show="showSyncStatus" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 @click.away="showSyncStatus = false"
                 class="absolute top-14 right-32 z-50 bg-white rounded-lg shadow-xl border border-gray-200 w-80">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Sync Status
                        </h3>
                        <button @click="showSyncStatus = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-600">Connection Status</span>
                            <span class="text-sm font-semibold flex items-center"
                                  :class="isOnline ? 'text-green-600' : 'text-red-600'">
                                <div class="w-2 h-2 rounded-full mr-1.5"
                                     :class="isOnline ? 'bg-green-500' : 'bg-red-500'">
                                </div>
                                <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-600">Pending Sync</span>
                            <span class="text-sm font-semibold"
                                  :class="pendingSyncCount > 0 ? 'text-orange-600' : 'text-gray-700'"
                                  x-text="pendingSyncCount + ' items'"></span>
                        </div>
                        
                        <template x-if="offlineSalesSummary">
                            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium text-gray-600">Offline Sales</span>
                                <span class="text-sm font-semibold text-gray-700" 
                                      x-text="offlineSalesSummary.total_sales + ' total'"></span>
                            </div>
                        </template>
                        
                        <div class="pt-3 border-t border-gray-200">
                            <button @click="forceSyncNow" 
                                    :disabled="!isOnline || pendingSyncCount === 0"
                                    class="w-full px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center"
                                    :class="isOnline && pendingSyncCount > 0 ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span x-text="isOnline && pendingSyncCount > 0 ? 'Sync Now' : (isOnline ? 'Nothing to Sync' : 'Cannot Sync (Offline)')"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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

                    @if(config('offline.enabled'))
                    <!-- Offline Stock Warning -->
                    <div x-show="!isOnline" class="offline-stock-warning">
                        <div class="offline-stock-warning-content">
                            <svg class="offline-stock-warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="offline-stock-warning-text">
                                <strong>Offline Mode:</strong> Stock levels shown may not reflect recent changes. 
                                Inventory will be updated when connection is restored.
                            </div>
                        </div>
                    </div>
                    @endif

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

                    <!-- Professional Product Grid -->
                    <div x-show="!isLoading && filteredProducts.length > 0" class="product-grid">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div :id="'product-' + product.id" 
                                 class="product-card" 
                                 :class="{ 'out-of-stock': product.stock <= 0, 'is-selling': sellingId === product.id }"
                                 @click="sellOnClick(product)">
                                
                                @if(config('offline.enabled'))
                                <!-- Offline Badge -->
                                <div x-show="!isOnline" class="offline-badge">Offline</div>
                                @endif
                                
                                <!-- Stock Badge - Modern Design -->
                                <div class="stock-badge"
                                     :class="{
                                         'high': product.stock > product.min_stock,
                                         'low': product.stock <= product.min_stock && product.stock > 0,
                                         'out': product.stock <= 0
                                     }">
                                    <template x-if="product.stock > product.min_stock">
                                        <span class="flex items-center gap-1">
                                            <svg class="stock-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="stock-text" x-text="product.stock"></span>
                                        </span>
                                    </template>
                                    <template x-if="product.stock <= product.min_stock && product.stock > 0">
                                        <span class="flex items-center gap-1">
                                            <svg class="stock-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="stock-text" x-text="product.stock"></span>
                                        </span>
                                    </template>
                                    <template x-if="product.stock <= 0">
                                        <span class="flex items-center gap-1">
                                            <svg class="stock-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="stock-text">0</span>
                                        </span>
                                    </template>
                                </div>

                                <!-- Image -->
                                <div class="product-image-wrapper">
                                    <img :src="product.image" :alt="product.name" class="product-image">
                                </div>

                                <!-- Content -->
                                <div class="product-content">
                                    <!-- Product Name -->
                                    <h3 class="product-name" x-text="product.name"></h3>
                                    
                                    <!-- Footer with Price and Add Button -->
                                    <div class="product-footer">
                                        <span class="product-price" x-text="'KSh ' + product.price.toFixed(0)"></span>

                                        {{--
                                            Quantity stepper. The .stop matters: without it a tap
                                            on + would sell the product.
                                        --}}
                                        <div class="qty-stepper" @click.stop>
                                            <button type="button" class="qty-btn"
                                                    @click.stop="adjustQty(product, -1)"
                                                    :disabled="qtyFor(product.id) <= 1 || product.stock <= 0"
                                                    aria-label="Decrease quantity">&minus;</button>
                                            <span class="qty-value" x-text="qtyFor(product.id)"></span>
                                            <button type="button" class="qty-btn"
                                                    @click.stop="adjustQty(product, 1)"
                                                    :disabled="qtyFor(product.id) >= product.stock || product.stock <= 0"
                                                    aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    {{--
                                        The two explicit actions. .stop on the wrapper keeps every
                                        tap in here off the tile, which would otherwise sell.

                                        Print replaces what used to be a double-click on the tile.
                                        A double-click selected the product name instead of firing
                                        reliably, and an invisible gesture is a poor way to reach
                                        the only action that puts ink on paper.
                                    --}}
                                    <div class="tile-actions" @click.stop>
                                        <button type="button" class="tile-btn"
                                                @click="openRiderPicker(product)"
                                                :disabled="product.stock <= 0 || sellingId !== null">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            </svg>
                                            Pick-up
                                        </button>

                                        <button type="button" class="tile-btn"
                                                @click="sellAndPrint(product)"
                                                :disabled="product.stock <= 0 || sellingId !== null">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            <span x-text="sellingId === product.id ? 'Printing…' : 'Print'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>


        {{--
            Rider picker.

            Opened by the Pick-up button on a tile. Tapping a rider books the
            tile's quantity out to them on the spot - there is no confirm step,
            matching one-tap selling. The list is fetched fresh on every open so
            availability reflects allocations made at other tills since the
            page loaded, not a stale snapshot.
        --}}
        <div x-show="showRiderModal && _initialized" @click.self="closeRiderPicker()"
             @keydown.escape.window="closeRiderPicker()"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">

                <div class="rider-modal__head">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Pick-up</h3>
                        <p class="text-sm text-gray-600"
                           x-text="riderProduct ? qtyFor(riderProduct.id) + ' x ' + riderProduct.name : ''"></p>
                    </div>
                    <button type="button" class="rider-modal__close" @click="closeRiderPicker()" aria-label="Close">&times;</button>
                </div>

                <template x-if="riderStep === 'pick'">
                    <div>
                        <div x-show="ridersLoading" class="rider-modal__empty">Loading riders&hellip;</div>

                        <div x-show="!ridersLoading && riders.length === 0" class="rider-modal__empty">
                            No active riders yet.
                            <a href="{{ route('pos.riders.index') }}" class="text-orange-600 font-semibold hover:underline">Add a rider</a>
                        </div>

                        <div x-show="!ridersLoading && riders.length > 0" class="rider-list">
                            <template x-for="rider in riders" :key="rider.id">
                                <div class="rider-item">
                                    {{--
                                        Two separate buttons, not one nested in the other:
                                        tapping the row assigns immediately, tapping the
                                        phone icon opens the optional customer number
                                        first. A button inside a button is invalid markup
                                        and browsers resolve the click unpredictably.
                                    --}}
                                    <div class="rider-row"
                                         :class="{ 'is-out': rider.is_out, 'is-busy': allocatingId === rider.id }">
                                        <button type="button" class="rider-row__main"
                                                :disabled="allocatingId !== null"
                                                @click="assignRider(rider)">
                                            <span class="rider-row__avatar" x-text="rider.name.charAt(0).toUpperCase()"></span>
                                            <span class="rider-row__body">
                                                <span class="rider-row__name" x-text="rider.name"></span>
                                                <span class="rider-row__phone" x-text="rider.phone"></span>
                                            </span>
                                            <span class="rider-row__pill" :class="rider.is_out ? 'out' : 'free'"
                                                  x-text="allocatingId === rider.id ? 'Assigning…' : rider.availability"></span>
                                        </button>

                                        <button type="button" class="rider-row__num"
                                                :class="{ 'is-active': customerForRiderId === rider.id }"
                                                :disabled="allocatingId !== null"
                                                @click.stop="toggleCustomerFor(rider)"
                                                :aria-label="'Add customer number for ' + rider.name"
                                                title="Add customer number (optional)">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{--
                                        Optional step. Empty and Send is the same as tapping
                                        the row - the number is a convenience for the rider,
                                        never a gate on the pick-up.
                                    --}}
                                    <div class="rider-cust" :data-rider="rider.id"
                                         x-show="customerForRiderId === rider.id"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0">
                                        <div class="rider-cust__row">
                                            <input type="tel" inputmode="tel" class="rider-cust__input"
                                                   x-model="customerPhone"
                                                   :disabled="allocatingId !== null"
                                                   placeholder="Customer phone e.g. 0722884226"
                                                   @keydown.enter.prevent="assignRider(rider, customerPhone)"
                                                   @keydown.escape.stop="closeCustomerFor()">
                                            <button type="button" class="rider-cust__send"
                                                    :disabled="allocatingId !== null"
                                                    @click="assignRider(rider, customerPhone)"
                                                    x-text="allocatingId === rider.id ? 'Sending…' : 'Send'"></button>
                                        </div>
                                        <p class="rider-cust__hint" x-show="!customerError">
                                            The rider gets this number in the pick-up text. Leave blank to skip.
                                        </p>
                                        <p class="rider-cust__error" x-show="customerError" x-text="customerError"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="rider-modal__foot">
                            <span>Tap a rider to assign, or <svg class="rider-modal__foot-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> to add the customer's number first.</span>
                            <a href="{{ route('pos.riders.index') }}">Manage riders</a>
                        </div>
                    </div>
                </template>

                <template x-if="riderStep === 'done' && riderResult">
                    <div class="rider-modal__done">
                        <div class="rider-modal__tick">&#10003;</div>
                        <h4 x-text="'Allocated to ' + riderResult.rider.name"></h4>
                        <p class="ref" x-text="riderResult.reference_number"></p>
                        <p x-text="riderResult.duplicate
                                ? 'This pick-up was already recorded.'
                                : 'The rider has been sent the order by SMS.'"></p>
                        <p class="cust" x-show="riderResult.customer_phone"
                           x-text="'Customer ' + riderResult.customer_phone + ' included'"></p>
                        <button type="button" @click="closeRiderPicker()">Done</button>
                    </div>
                </template>
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

        <!-- Alpine.js Enhanced Script with Conditional Offline Support -->
        
        @if(config('offline.enabled', false))
        <!-- Offline Support Script (Only loaded in production/when enabled) -->
        <script src="{{ asset('js/offline-pos.js') }}"></script>
        @endif
        
        <script>
            // Make categories available globally
            window.posCategories = @json($categories ?? []);
            
            // Offline mode configuration from Laravel config
            window.offlineModeEnabled = @json(config('offline.enabled', false));
            window.offlineConfig = @json(config('offline', []));
            
            // Enhanced POS System with Conditional Offline Capability
            function enhancedPosSystem() {
                return {
                    searchQuery: '',
                    currentCategory: null,
                    showCategoryDrawer: false,
                    showError: false,
                    errorMessage: '',
                    isLoading: false,
                    _initialized: false,

                    /*
                        Quick-sale state. There is no cart: a tap on a tile is
                        the whole transaction.

                        quantities  product id -> how many the next tap sells
                        sellingId   the tile with a sale in flight, so the UI
                                    can block a second tap on it
                        lastSale    the sale the server recorded, which is what
                                    the printable receipt renders
                        lastSaleAt  when that sale started, used to tell the
                                    second half of a double-click apart from a
                                    deliberate second sale
                    */
                    quantities: {},
                    sellingId: null,
                    lastSale: null,
                    lastSaleProductId: null,
                    lastSaleAt: 0,
                    pendingPrint: false,

                    /*
                        Today's takings, for the header badge. Seeded from the
                        server at page load and incremented per sale, because
                        the POS screen does not reload between sales - a static
                        figure would be stale by the second one.

                        Voided sales are excluded server-side; a void happens on
                        another screen, so this catches up on the next load.
                    */
                    todaySalesCount: {{ (int) ($salesStats['count'] ?? 0) }},
                    todaySalesAmount: {{ (float) ($salesStats['amount'] ?? 0) }},

                    /*
                        Rider pick-up
                        showRiderModal  the picker is open
                        riderProduct    the tile it was opened from
                        riders          active riders with live availability
                        allocatingId    rider whose assignment is in flight
                        riderStep       'pick' while choosing, 'done' afterwards
                        riderResult     what the server recorded, for the done step
                    */
                    showRiderModal: false,
                    riderProduct: null,
                    riders: [],
                    ridersLoading: false,
                    allocatingId: null,
                    riderStep: 'pick',
                    riderResult: null,
                    riderCloseTimer: null,

                    /*
                        Optional customer number
                        customerForRiderId  which rider's number field is open
                        customerPhone       what has been typed into it
                        customerError       server's reason for refusing it
                    */
                    customerForRiderId: null,
                    customerPhone: '',
                    customerError: '',

                    // Two clicks inside this window are one double-click. The
                    // browser's own dblclick threshold is around 500ms; staying
                    // just under it means a deliberate second sale is still
                    // possible without the printer firing.
                    doubleClickWindow: 450,
                    
                    // Offline-related state variables - Conditional based on config
                    isOnline: window.offlineModeEnabled ? navigator.onLine : true,
                    offlineManager: null,
                    showOfflineStatus: false,
                    pendingSyncCount: 0,
                    offlineSalesSummary: null,
                    showSyncStatus: false,
                    offlineModeEnabled: window.offlineModeEnabled,

                    // Products data
                    allProducts: @json($products ?? []),
                    
                    // Inventory card data
                    inventorySearch: '',
                    filteredInventoryList: [],

                    // Computed properties
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

                    // Thousands-separated, for the header tooltip.
                    get todaySalesAmountFormatted() {
                        return Number(this.todaySalesAmount).toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    },

                    get totalInventoryStock() {
                        return this.allProducts.reduce((total, product) => {
                            const stock = parseInt(product.stock, 10) || 0;
                            return total + stock;
                        }, 0);
                    },


                    get connectionStatusText() {
                        if (!this.offlineModeEnabled) {
                            return 'Online (Offline Mode Disabled)';
                        }
                        
                        if (this.isOnline) {
                            return this.pendingSyncCount > 0 
                                ? `Online (${this.pendingSyncCount} pending)` 
                                : 'Online';
                        }
                        return 'Offline Mode';
                    },

                    // Filter inventory list for floating card
                    filterInventoryList() {
                        if (!this.inventorySearch || this.inventorySearch.trim() === '') {
                            this.filteredInventoryList = this.allProducts;
                        } else {
                            const query = this.inventorySearch.toLowerCase();
                            this.filteredInventoryList = this.allProducts.filter(product =>
                                product.name.toLowerCase().includes(query) ||
                                (product.sku && product.sku.toLowerCase().includes(query))
                            );
                        }
                    },

                    // Scroll to product in main grid
                    scrollToProduct(productId) {
                        const productCards = document.querySelectorAll('.product-card');
                        const productCard = Array.from(productCards).find(card => {
                            const productData = this.filteredProducts.find(p => p.id === productId);
                            return productData && card.querySelector('[x-text="product.name"]')?.textContent === productData.name;
                        });
                        
                        if (productCard) {
                            productCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            productCard.classList.add('ring-2', 'ring-orange-500');
                            setTimeout(() => {
                                productCard.classList.remove('ring-2', 'ring-orange-500');
                            }, 2000);
                        }
                    },

                    // Initialize the POS system with conditional offline support
                    async init() {
                        console.log('Initializing POS System...');
                        console.log('Offline mode enabled:', this.offlineModeEnabled);
                        
                        try {
                            if (this.offlineModeEnabled) {
                                // Full offline initialization for production
                                console.log('Initializing with offline support...');
                                await this.initializeOfflineSupport();
                            } else {
                                // Simplified initialization for development
                                console.log('Initializing without offline support (development mode)...');
                                this.setupBasicConnectionMonitoring();
                            }

                            // Nothing to reset: quick-sale holds no cart, and
                            // per-product quantities default to 1 on first read.

                            // Initialize inventory list
                            this.filteredInventoryList = this.allProducts;
                            
                            this._initialized = true;
                            console.log('POS System initialized successfully');

                        } catch (error) {
                            console.error('Failed to initialize POS System:', error);
                            this.showError = true;
                            this.errorMessage = 'Failed to initialize POS system: ' + error.message;
                        }
                    },

                    // Initialize full offline support for production
                    async initializeOfflineSupport() {
                        try {
                            // Wait for offline manager to be available
                            await this.waitForOfflineManager();
                            
                            // Setup connection monitoring
                            this.setupConnectionMonitoring();
                            
                            // Update sync status
                            await this.updateSyncStatus();
                            
                            console.log('Offline support initialized successfully');
                        } catch (error) {
                            console.warn('Offline support initialization failed:', error);
                            // Fall back to basic monitoring if offline setup fails
                            this.setupBasicConnectionMonitoring();
                        }
                    },

                    // Wait for offline manager to be available
                    async waitForOfflineManager() {
                        return new Promise((resolve, reject) => {
                            let attempts = 0;
                            const maxAttempts = 50; // 5 seconds timeout
                            
                            const checkManager = () => {
                                attempts++;
                                
                                if (window.offlinePOS && window.offlinePOS.db) {
                                    this.offlineManager = window.offlinePOS;
                                    resolve();
                                } else if (attempts >= maxAttempts) {
                                    reject(new Error('Offline manager failed to initialize'));
                                } else {
                                    setTimeout(checkManager, 100);
                                }
                            };
                            
                            checkManager();
                        });
                    },

                    // Setup full connection monitoring with offline support
                    setupConnectionMonitoring() {
                        // Listen for connection status changes
                        window.addEventListener('online', () => {
                            this.isOnline = true;
                            console.log('Connection restored');
                            this.updateSyncStatus();
                        });
                        
                        window.addEventListener('offline', () => {
                            this.isOnline = false;
                            console.log('Connection lost - switching to offline mode');
                        });
                        
                        // Listen for custom connection events
                        window.addEventListener('connection-status-changed', (event) => {
                            this.isOnline = event.detail.isOnline;
                            this.updateSyncStatus();
                        });

                        // Update sync status periodically
                        setInterval(() => {
                            if (this.offlineManager) {
                                this.updateSyncStatus();
                            }
                        }, 30000); // Every 30 seconds
                    },

                    // Setup basic connection monitoring for local development
                    setupBasicConnectionMonitoring() {
                        // Simple connection monitoring
                        window.addEventListener('online', () => {
                            this.isOnline = true;
                            console.log('Connection restored');
                        });
                        
                        window.addEventListener('offline', () => {
                            this.isOnline = false;
                            console.log('Connection lost');
                        });
                        
                        // For local development, always stay online
                        this.isOnline = true;
                    },

                    // Customer Management Methods
                    //
                    // Searching happens on the server. This used to fetch
                    // a versioned customers path that does not exist, since the
                    // API is not versioned - so the list was always empty and a
                    // cashier could never pick an existing customer for a
                    // credit sale. The API routes would not have worked either:
                    // they sit behind auth:sanctum, and this page is
                    // session-authenticated. This web route is the one that
                    // shares the session.





                    // Add newly created customer to the local customer list

                    // Refresh customer list from server

                    // Enhanced sale processing with conditional offline support

                    // Process sale online

                    // Process sale offline (only available when offline mode is enabled)

                    // Update sync status (conditional based on offline mode)
                    async updateSyncStatus() {
                        if (!this.offlineModeEnabled || !this.offlineManager) {
                            // No-op for development mode
                            this.pendingSyncCount = 0;
                            this.offlineSalesSummary = null;
                            return;
                        }

                        try {
                            this.pendingSyncCount = await this.offlineManager.getPendingOperationsCount();
                            this.offlineSalesSummary = await this.offlineManager.getOfflineSalesSummary();
                        } catch (error) {
                            console.error('Error updating sync status:', error);
                        }
                    },

                    // Toggle sync status display
                    toggleSyncStatus() {
                        if (!this.offlineModeEnabled) {
                            console.log('Sync status not available in development mode');
                            return;
                        }
                        
                        this.showSyncStatus = !this.showSyncStatus;
                    },

                    // Force sync now (conditional based on offline mode)
                    async forceSyncNow() {
                        if (!this.offlineModeEnabled) {
                            console.log('Sync not available in development mode');
                            return;
                        }
                        
                        if (!this.isOnline || !this.offlineManager) {
                            this.showNotification('Cannot sync while offline', 'error');
                            return;
                        }

                        try {
                            this.showNotification('Starting synchronization...', 'info');
                            await this.offlineManager.startBackgroundSync();
                            await this.updateSyncStatus();
                            this.showNotification('Synchronization completed', 'success');
                        } catch (error) {
                            console.error('Manual sync failed:', error);
                            this.showNotification('Synchronization failed: ' + error.message, 'error');
                        }
                    },

                    // Show notification (enhanced for offline mode)
                    showNotification(message, type = 'info') {
                        if (this.offlineModeEnabled && this.offlineManager) {
                            this.offlineManager.showNotification(message, type);
                        } else {
                            // Fallback notification for development
                            console.log(`${type.toUpperCase()}: ${message}`);
                        }
                    },

                    // Copy the server's authoritative per-line figures onto the
                    // cart and the product grid.
                    //
                    // The stock levels come back from the same locked read that
                    // performed the deduction, so the terminal reflects
                    // reservations and any concurrent sale rather than a local
                    // guess. Falls back to local arithmetic if the server did
                    // not send line data.

                    /* ------------------------------------------------------
                       Quick sale - one tap is the whole transaction
                       ------------------------------------------------------ */

                    qtyFor(productId) {
                        return this.quantities[productId] || 1;
                    },

                    adjustQty(product, delta) {
                        const next = this.qtyFor(product.id) + delta;
                        if (next < 1 || next > product.stock) return;
                        this.quantities[product.id] = next;
                    },

                    /**
                     * A tap on a tile sells it.
                     *
                     * The repeat guard is pure double-sale protection: a tile is
                     * a big target with no confirm step behind it, so an
                     * impatient second tap must not take money twice. Anyone who
                     * genuinely wants two uses the stepper.
                     */
                    sellOnClick(product) {
                        if (product.stock <= 0 || this.sellingId !== null) return;

                        const isRepeatTap = this.lastSaleProductId === product.id
                            && (Date.now() - this.lastSaleAt) < this.doubleClickWindow;

                        if (isRepeatTap) return;

                        this.quickSell(product);
                    },

                    /**
                     * Sell this tile and print the receipt, in one press.
                     *
                     * Replaces double-clicking the tile. quickSell() already
                     * prints when pendingPrint is set, so this only has to raise
                     * the flag and hand over - which also means the print waits
                     * for the SERVER's receipt rather than racing it.
                     */
                    sellAndPrint(product) {
                        if (product.stock <= 0 || this.sellingId !== null) return;

                        this.pendingPrint = true;
                        this.quickSell(product);
                    },

                    async quickSell(product) {
                        const quantity = this.qtyFor(product.id);

                        this.sellingId = product.id;
                        this.lastSaleProductId = product.id;
                        this.lastSaleAt = Date.now();

                        try {
                            const response = await fetch('{{ route('pos.sales.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                },
                                body: JSON.stringify({
                                    cart_items: [{ id: product.id, quantity: quantity }],
                                    payment_method: 'cash',
                                    // Generated per attempt. If this request is
                                    // retried, or arrives twice, the server hands
                                    // back the sale it already made instead of
                                    // making another.
                                    idempotency_key: this.newIdempotencyKey()
                                })
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Could not complete the sale.');
                            }

                            this.lastSale = Object.assign({}, result.receipt_data, {
                                receipt_number: result.receipt_number,
                                order_number: result.order_number,
                                // What printLastSale() fetches the receipt by.
                                sale_id: result.sale_id
                            });

                            this.applyStockFromServer((result.receipt_data && result.receipt_data.items) || []);
                            this.quantities[product.id] = 1;

                            // Only a sale that was actually made counts. A
                            // replayed idempotency key returns the existing
                            // sale, and counting it would inflate the day.
                            if (!result.duplicate) {
                                this.todaySalesCount += 1;
                                this.todaySalesAmount += Number(
                                    (result.receipt_data && result.receipt_data.total) || 0
                                );
                            }

                            this.showNotification(
                                quantity + ' x ' + product.name + ' sold - ' + result.receipt_number,
                                'success'
                            );

                            if (this.pendingPrint) {
                                this.pendingPrint = false;
                                this.printLastSale();
                            }
                        } catch (error) {
                            console.error('Quick sale failed:', error);
                            this.pendingPrint = false;
                            this.errorMessage = error.message || 'Could not complete the sale.';
                            this.showError = true;
                        } finally {
                            this.sellingId = null;
                        }
                    },

                    newIdempotencyKey() {
                        if (window.crypto && window.crypto.randomUUID) {
                            return window.crypto.randomUUID();
                        }
                        return 'k-' + Date.now() + '-' + Math.random().toString(16).slice(2);
                    },

                    /* ------------------------------------------------------
                       Pick-up - book the tile out to a rider instead
                       ------------------------------------------------------ */

                    openRiderPicker(product) {
                        if (product.stock <= 0 || this.sellingId !== null) return;

                        clearTimeout(this.riderCloseTimer);
                        this.riderProduct = product;
                        this.riderStep = 'pick';
                        this.riderResult = null;
                        this.closeCustomerFor();
                        this.showRiderModal = true;
                        this.loadRiders();
                    },

                    closeRiderPicker() {
                        if (this.allocatingId !== null) return;   // let the request land first

                        clearTimeout(this.riderCloseTimer);
                        this.showRiderModal = false;
                        this.riderProduct = null;
                        this.riderResult = null;
                        this.riderStep = 'pick';
                        this.closeCustomerFor();
                    },

                    /**
                     * Open the optional customer-number field against one rider,
                     * or shut it if it is already that rider's.
                     *
                     * Only ever one open at a time: two fields on screen would
                     * make it ambiguous which rider Send belongs to.
                     */
                    toggleCustomerFor(rider) {
                        if (this.allocatingId !== null) return;

                        if (this.customerForRiderId === rider.id) {
                            this.closeCustomerFor();
                            return;
                        }

                        this.customerForRiderId = rider.id;
                        this.customerPhone = '';
                        this.customerError = '';

                        // Addressed by rider id: every row has this element,
                        // x-show only hides the others, so a bare selector
                        // would focus the first rider's field every time.
                        this.$nextTick(() => {
                            const field = document.querySelector(
                                '.rider-cust[data-rider="' + rider.id + '"] .rider-cust__input'
                            );
                            if (field) field.focus();
                        });
                    },

                    closeCustomerFor() {
                        this.customerForRiderId = null;
                        this.customerPhone = '';
                        this.customerError = '';
                    },

                    async loadRiders() {
                        this.ridersLoading = true;

                        try {
                            const response = await fetch('{{ route('pos.riders.available') }}', {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            const riders = await response.json();

                            // Free riders first. A busy one can still take more,
                            // but should not be the first thing a thumb lands on.
                            this.riders = (Array.isArray(riders) ? riders : []).sort((a, b) =>
                                (a.open_allocations - b.open_allocations) || a.name.localeCompare(b.name)
                            );
                        } catch (error) {
                            console.error('Could not load riders:', error);
                            this.riders = [];
                        } finally {
                            this.ridersLoading = false;
                        }
                    },

                    /**
                     * One tap assigns. The server reserves the cylinders, texts
                     * the rider and reports the stock level from the same locked
                     * read, so the tile drops by exactly what was booked out.
                     */
                    async assignRider(rider, customerPhone = null) {
                        if (!this.riderProduct || this.allocatingId !== null) return;

                        const product = this.riderProduct;
                        const quantity = this.qtyFor(product.id);

                        // Opening the field and leaving it empty is not an error
                        // - it is the ordinary pick-up, typed into by accident.
                        const phone = (customerPhone || '').trim();

                        this.allocatingId = rider.id;
                        this.customerError = '';

                        try {
                            const response = await fetch('{{ route('pos.riders.allocate') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                },
                                body: JSON.stringify({
                                    rider_id: rider.id,
                                    items: [{ product_id: product.id, quantity: quantity }],
                                    customer_phone: phone || null,
                                    // Same guard as a quick sale: a retried or
                                    // doubled request gets the allocation already
                                    // made, not a second one.
                                    idempotency_key: this.newIdempotencyKey()
                                })
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                const failure = new Error(result.message || 'Could not allocate to this rider.');
                                failure.errorType = result.error_type || null;
                                throw failure;
                            }

                            this.applyStockFromServer(result.items || []);
                            this.quantities[product.id] = 1;
                            this.closeCustomerFor();

                            this.riderResult = result;
                            this.riderStep = 'done';
                            this.showNotification(
                                quantity + ' x ' + product.name + ' allocated to ' + rider.name + ' - ' + result.reference_number,
                                'success'
                            );
                        } catch (error) {
                            console.error('Rider allocation failed:', error);
                            this.allocatingId = null;

                            // A number that cannot be dialled is a typo to fix,
                            // not a failed pick-up. Keep the field open with
                            // what they typed still in it; throwing the modal
                            // away would lose the rider choice as well.
                            if (error.errorType === 'invalid_customer_phone') {
                                this.customerForRiderId = rider.id;
                                this.customerError = error.message;
                                return;
                            }

                            this.closeRiderPicker();
                            this.errorMessage = error.message || 'Could not allocate to this rider.';
                            this.showError = true;
                            return;
                        } finally {
                            this.allocatingId = null;
                        }

                        // Long enough to read the reference, short enough not
                        // to get in the way of the next customer.
                        this.riderCloseTimer = setTimeout(() => this.closeRiderPicker(), 3000);
                    },

                    /*
                        Stock figures come from the same locked read that performed
                        the deduction, so they already account for other tills and
                        for cylinders reserved against drop-offs. Subtracting
                        locally would not.
                    */
                    applyStockFromServer(lines) {
                        for (const line of lines) {
                            const product = this.allProducts.find(p => p.id === line.id);
                            if (product && typeof line.stock_after === 'number') {
                                product.stock = line.stock_after;
                                product.out_of_stock = line.stock_after <= 0;
                            }
                        }
                    },

                    /**
                     * Print with no preview dialog: reveal the hidden receipt,
                     * print, hide it again. Alpine needs a tick to render the
                     * template before the browser snapshots the page.
                     */
                    printLastSale() {
                        const saleId = this.lastSale && this.lastSale.sale_id;
                        if (!saleId) return;

                        // One iframe, reused. A till stays open all day, so
                        // creating a node per sale would leak one per receipt.
                        let frame = document.getElementById('receiptFrame');

                        if (!frame) {
                            frame = document.createElement('iframe');
                            frame.id = 'receiptFrame';
                            frame.setAttribute('aria-hidden', 'true');
                            frame.setAttribute('tabindex', '-1');
                            frame.style.position = 'fixed';
                            frame.style.left = '-9999px';
                            frame.style.width = '0';
                            frame.style.height = '0';
                            frame.style.border = '0';
                            document.body.appendChild(frame);
                        }

                        /*
                            The receipt page prints ITSELF via ?autoprint=1,
                            rather than the parent reaching into the frame. Only
                            that page paginates, so a receipt is one 57mm slip
                            instead of the whole dashboard across two sheets.

                            The timestamp defeats the cache: re-printing the
                            same sale would otherwise reuse the loaded document
                            and never fire load again.
                        */
                        frame.src = '{{ url('pos/sales') }}/' + saleId
                            + '/receipt?autoprint=1&t=' + Date.now();
                    },

                    // Category management (existing methods)
                    toggleCategories() {
                        this.showCategoryDrawer = !this.showCategoryDrawer;
                    },

                    getCategoryName(categoryId) {
                        const categories = window.posCategories || [];
                        const category = categories.find(c => c.id === categoryId);
                        return category ? category.name : 'Unknown Category';
                    },

                    // Handle customer mode changes

                    /**
                     * Credit has to be owed by somebody, so the walk-in option is
                     * not offered there. Switching to credit while on walk-in
                     * moves the cashier to the customer picker rather than
                     * leaving a hidden, unselectable mode active.
                     */
                }
            }
        </script>
        
        <!-- Development Error Testing Helper (Remove in Production) -->
        @if(config('app.debug'))
        <script src="{{ asset('js/pos-error-tester.js') }}"></script>
        @endif
    </div>
</x-app-layout>
