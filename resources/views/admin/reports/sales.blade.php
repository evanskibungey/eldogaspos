<!-- resources/views/admin/reports/sales.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-semibold mb-6">Sales Reports</h1>
                
                <!-- Date Range Filter -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <form action="{{ route('admin.reports.sales') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label for="date_range" class="block text-sm font-medium text-gray-700">Date Range</label>
                            <select id="date_range" name="date_range" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                                <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                                <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>
                        
                        <div id="custom_date_inputs" class="flex gap-4" style="{{ $dateRange == 'custom' ? '' : 'display: none;' }}">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Methods</option>
                                <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="credit" {{ request('payment_method') == 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="cashier" class="block text-sm font-medium text-gray-700">Cashier</label>
                            <select id="cashier" name="cashier" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Cashiers</option>
                                @foreach($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" {{ request('cashier') == $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                        </div>
                        
                        <div>
                            <a href="{{ route('admin.reports.sales.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export to CSV
                            </a>
                        </div>
                        
                        <div>
                            <a href="{{ route('admin.reports.sales.export-detailed', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a4 4 0 01-4-4V5a4 4 0 014-4h10a4 4 0 014 4v14a4 4 0 01-4 4z" />
                                </svg>
                                Detailed Export
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-blue-800 mb-1">Total Sales</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ $totalSales }}</p>
                        <p class="text-xs text-blue-500 mt-1">Transactions</p>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-green-800 mb-1">Total Revenue</h3>
                        <p class="text-2xl font-bold text-green-600">{{ config('settings.currency_symbol', '$') }}{{ number_format($totalRevenue, 2) }}</p>
                        <p class="text-xs text-green-500 mt-1">Gross Revenue</p>
                    </div>
                    
                    <div class="bg-orange-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-orange-800 mb-1">Total Cost</h3>
                        <p class="text-2xl font-bold text-orange-600">{{ config('settings.currency_symbol', '$') }}{{ number_format($totalCost, 2) }}</p>
                        <p class="text-xs text-orange-500 mt-1">Cost of Goods</p>
                    </div>
                    
                    <div class="bg-emerald-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-emerald-800 mb-1">Net Profit</h3>
                        <p class="text-2xl font-bold text-emerald-600">{{ config('settings.currency_symbol', '$') }}{{ number_format($totalProfit, 2) }}</p>
                        <p class="text-xs text-emerald-500 mt-1">Gross Profit</p>
                    </div>
                    
                    <div class="bg-indigo-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-indigo-800 mb-1">Profit Margin</h3>
                        <p class="text-2xl font-bold text-indigo-600">{{ number_format($profitMargin, 1) }}%</p>
                        <p class="text-xs text-indigo-500 mt-1">Profit/Revenue</p>
                    </div>
                    
                    <div class="bg-purple-50 rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm font-medium text-purple-800 mb-1">Items Sold</h3>
                        <p class="text-2xl font-bold text-purple-600">{{ $totalItems }}</p>
                        <p class="text-xs text-purple-500 mt-1">Total Units</p>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Sales Trend</h3>
                    <div class="h-80">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Top Products and Categories -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Top Products -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Top Products by Quantity</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($topProducts as $product)
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $product->total_quantity }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }}{{ number_format($product->total_revenue, 2) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }}{{ number_format($product->total_profit, 2) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->profit_margin >= 20 ? 'bg-green-100 text-green-800' : ($product->profit_margin >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ number_format($product->profit_margin, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Top Categories -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Top Categories</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($topCategories as $category)
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $category->name }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $category->total_quantity }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }}{{ number_format($category->total_revenue, 2) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }}{{ number_format($category->total_profit, 2) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $category->profit_margin >= 20 ? 'bg-green-100 text-green-800' : ($category->profit_margin >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ number_format($category->profit_margin, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Table -->
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Sales Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($sales as $sale)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sale->receipt_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->customer ? $sale->customer->name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->user ? $sale->user->name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_method == 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($sale->payment_method) }}
                                            </span>
                                            <span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_status == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($sale->payment_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }} {{ number_format($sale->total_amount, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <a href="{{ route('pos.sales.show', $sale) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <!-- Chart.js with fallback loading -->
    <script>
        // Load Chart.js with fallback
        function loadChartJS() {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js';
                script.onload = () => {
                    console.log('Chart.js loaded successfully');
                    resolve();
                };
                script.onerror = () => {
                    console.log('Primary CDN failed, trying fallback...');
                    const fallbackScript = document.createElement('script');
                    fallbackScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js';
                    fallbackScript.onload = () => {
                        console.log('Chart.js loaded from fallback CDN');
                        resolve();
                    };
                    fallbackScript.onerror = () => {
                        console.error('Failed to load Chart.js from both CDNs');
                        reject(new Error('Failed to load Chart.js'));
                    };
                    document.head.appendChild(fallbackScript);
                };
                document.head.appendChild(script);
            });
        }
        
        // Show/hide custom date inputs
        document.getElementById('date_range').addEventListener('change', function() {
            const customDateInputs = document.getElementById('custom_date_inputs');
            if (this.value === 'custom') {
                customDateInputs.style.display = 'flex';
            } else {
                customDateInputs.style.display = 'none';
            }
        });
        
        // Initialize chart
        async function initChart() {
            try {
                // Load Chart.js first
                await loadChartJS();
                
                const canvas = document.getElementById('salesChart');
                if (!canvas) {
                    console.error('Chart canvas not found');
                    return;
                }
                
                const salesData = @json($salesByPeriod ?? []);
                console.log('Sales data received:', salesData);
                
                // Prepare chart data
                let chartLabels = [];
                let chartCounts = [];
                let chartTotals = [];
                
                if (salesData && typeof salesData === 'object') {
                    chartLabels = Object.keys(salesData);
                    chartCounts = chartLabels.map(label => {
                        const item = salesData[label];
                        return (item && typeof item === 'object') ? (parseInt(item.count) || 0) : 0;
                    });
                    chartTotals = chartLabels.map(label => {
                        const item = salesData[label];
                        return (item && typeof item === 'object') ? (parseFloat(item.total) || 0) : 0;
                    });
                }
                
                console.log('Chart labels:', chartLabels);
                console.log('Chart counts:', chartCounts);
                console.log('Chart totals:', chartTotals);
                
                const currencySymbol = '{{ config("settings.currency_symbol", "$") }}';
                
                // Create chart
                const chart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: chartLabels.length > 0 ? chartLabels : ['No Data'],
                        datasets: chartLabels.length > 0 ? [
                            {
                                label: 'Sales Count',
                                data: chartCounts,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                yAxisID: 'y'
                            },
                            {
                                label: `Revenue (${currencySymbol})`,
                                data: chartTotals,
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                yAxisID: 'y1'
                            }
                        ] : [{
                            label: 'No Data',
                            data: [0],
                            borderColor: 'rgb(156, 163, 175)',
                            backgroundColor: 'rgba(156, 163, 175, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: chartLabels.length > 0 ? {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Sales Count'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: `Revenue (${currencySymbol})`
                                }
                            }
                        } : {
                            y: {
                                beginAtZero: true,
                                max: 1
                            }
                        },
                        plugins: {
                            title: {
                                display: chartLabels.length === 0,
                                text: 'No sales data available for selected period'
                            },
                            legend: {
                                display: chartLabels.length > 0
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully');
                
            } catch (error) {
                console.error('Error initializing chart:', error);
                
                // Show error message
                const canvas = document.getElementById('salesChart');
                if (canvas) {
                    const container = canvas.parentElement;
                    container.innerHTML = `
                        <div class="flex items-center justify-center h-full bg-red-50 border-2 border-dashed border-red-200 rounded-lg">
                            <div class="text-center text-red-600">
                                <p class="text-lg font-medium">Chart Error</p>
                                <p class="text-sm">${error.message}</p>
                                <p class="text-xs mt-2">Please refresh the page or check console for details</p>
                            </div>
                        </div>
                    `;
                }
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChart);
        } else {
            initChart();
        }
    </script>
    @endpush
</x-app-layout>