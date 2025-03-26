<x-app-layout>
    <!-- Enhanced Print Styles -->
    <style>
        /* Regular page styles */
        .receipt-link {
            color: #FF6900;
            text-decoration: underline;
            cursor: pointer;
        }
        
        /* Print-specific styles */
        @media print {
            /* Hide everything by default */
            body * {
                visibility: hidden;
            }
            
            /* Only show the receipt content */
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
            
            /* Hide normal page elements */
            .normal-view {
                display: none !important;
            }
        }
    </style>

    <div class="py-6 bg-gray-50 normal-view">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Sale Details
                            </h2>
                            <p class="text-sm text-gray-500 mt-1 ml-8">Receipt: {{ $sale->receipt_number }}</p>
                        </div>
                        <div class="flex flex-wrap mt-4 md:mt-0 gap-2">
                            <a href="{{ route('pos.sales.history') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors duration-200 flex items-center shadow-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to History
                            </a>
                            <button onclick="printReceipt()" class="px-4 py-2 bg-[#0077B5] text-white rounded-md hover:bg-blue-700 transition-colors duration-200 flex items-center shadow-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Receipt
                            </button>
                            
                            @if($sale->status == 'completed')
                                <form action="{{ route('pos.sales.void', $sale) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to void this sale? This action cannot be undone.');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200 flex items-center shadow-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Void Sale
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Sale Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Receipt Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-sm font-medium text-gray-500">Receipt Number:</div>
                                <div class="text-sm text-gray-900 font-semibold">{{ $sale->receipt_number }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Date:</div>
                                <div class="text-sm text-gray-900">{{ $sale->created_at->format('M d, Y h:i A') }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Cashier:</div>
                                <div class="text-sm text-gray-900">{{ $sale->user->name }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Payment Method:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_method == 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst($sale->payment_method) }}
                                    </span>
                                </div>
                                
                                <div class="text-sm font-medium text-gray-500">Payment Status:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_status == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($sale->payment_status) }}
                                    </span>
                                </div>
                                
                                <div class="text-sm font-medium text-gray-500">Status:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Customer Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-sm font-medium text-gray-500">Name:</div>
                                <div class="text-sm text-gray-900 font-semibold">{{ $sale->customer->name }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Phone:</div>
                                <div class="text-sm text-gray-900">{{ $sale->customer->phone }}</div>
                                
                                @if($sale->payment_method == 'credit')
                                    <div class="text-sm font-medium text-gray-500">Credit Limit:</div>
                                    <div class="text-sm text-gray-900">{{ $currencySymbol }}{{ number_format($sale->customer->credit_limit, 2) }}</div>
                                    
                                    <div class="text-sm font-medium text-gray-500">Current Balance:</div>
                                    <div class="text-sm text-gray-900">{{ $currencySymbol }}{{ number_format($sale->customer->balance, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sale Items -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                            <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Sale Items
                        </h3>
                        <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Serial Number
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Unit Price
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sale->items as $item)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $item->product->name }}
                                                <div class="text-xs text-gray-500">SKU: {{ $item->product->sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item->serial_number ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item->quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $currencySymbol }}{{ number_format($item->subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 text-base font-bold text-gray-900">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Receipt - Hidden on screen but visible when printing -->
    <div class="printable-receipt" style="display: none;">
        <div class="receipt-container">
            <!-- Company name and info -->
            <div class="company-name">{{ config('app.name', 'Eldogas') }}</div>
            <div class="company-info">
                @if(isset($companyAddress) && $companyAddress)
                    {{ $companyAddress }}<br>
                @endif
                Tel: {{ $companyPhone ?? '+254724556855' }}
            </div>
            <div class="separator-line">****************************</div>
            
            <!-- Receipt details -->
            <div class="transaction-info">
                <div class="transaction-label">RECEIPT #:</div>
                <div class="transaction-value">{{ $sale->receipt_number }}</div>
            </div>
            <div class="transaction-info">
                <div class="transaction-label">DATE:</div>
                <div class="transaction-value">{{ $sale->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="transaction-info">
                <div class="transaction-label">TIME:</div>
                <div class="transaction-value">{{ $sale->created_at->format('H:i') }}</div>
            </div>
            <div class="transaction-info">
                <div class="transaction-label">PAYMENT:</div>
                <div class="transaction-value">{{ ucfirst($sale->payment_method) }}</div>
            </div>
            <div class="transaction-info">
                <div class="transaction-label">CASHIER:</div>
                <div class="transaction-value">{{ substr($sale->user->name, 0, 10) }}</div>
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
                @foreach($sale->items as $item)
                    <tr>
                        <td class="item-name">
                            <div>{{ $item->product->name }}</div>
                            @if($item->serial_number)
                                <div class="serial-number">S/N: {{ $item->serial_number }}</div>
                            @endif
                        </td>
                        <td class="item-qty">{{ $item->quantity }}</td>
                        <td class="item-price">{{ number_format($item->unit_price, 0) }}</td>
                        <td class="item-total">{{ number_format($item->subtotal, 0) }}</td>
                    </tr>
                @endforeach
            </table>
            <div class="separator-line">----------------------------</div>
            
            <!-- Totals section -->
            <div class="totals-section">
                <div class="totals-label">SUBTOTAL:</div>
                <div class="totals-value">KSH {{ number_format($sale->total_amount, 0) }}</div>
            </div>
            <div class="totals-section">
                <div class="totals-label">TAX (0%):</div>
                <div class="totals-value">KSH 0</div>
            </div>
            <div class="totals-section grand-total">
                <div class="totals-label">TOTAL:</div>
                <div class="totals-value">KSH {{ number_format($sale->total_amount, 0) }}</div>
            </div>
            
            <!-- Customer section for credit payments -->
            @if($sale->payment_method == 'credit')
                <div class="customer-section">
                    <div class="separator-line">----------------------------</div>
                    <div class="customer-section-title">CUSTOMER DETAILS</div>
                    <div class="customer-info">
                        <div class="customer-label">Name:</div>
                        <div>{{ $sale->customer->name }}</div>
                    </div>
                    <div class="customer-info">
                        <div class="customer-label">Phone:</div>
                        <div>{{ $sale->customer->phone }}</div>
                    </div>
                </div>
            @endif
            
            <!-- Footer -->
            <div class="receipt-footer">
                <div class="thank-you-msg">Thank you for your business!</div>
                <div>Keep receipt for exchanges</div>
                <div class="store-name">*{{ config('app.name', 'Eldogas') }}*</div>
                <div>{{ date('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    <script>
        function printReceipt() {
            // Make the receipt visible before printing
            document.querySelector('.printable-receipt').style.display = 'block';
            
            // Print the page (CSS will hide everything except the receipt)
            window.print();
            
            // Wait for print dialog to close, then hide the receipt again
            setTimeout(function() {
                document.querySelector('.printable-receipt').style.display = 'none';
            }, 500);
        }
    </script>
</x-app-layout>