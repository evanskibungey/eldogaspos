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
                    </form>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-blue-50 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Total Sales</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalSales }}</p>
                        <p class="text-sm text-blue-500 mt-1">For the selected period</p>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-medium text-green-800 mb-2">Total Revenue</h3>
                        <p class="text-3xl font-bold text-green-600">{{ config('settings.currency_symbol', '$') }} {{ number_format($totalRevenue, 2) }}</p>
                        <p class="text-sm text-green-500 mt-1">For the selected period</p>
                    </div>
                    
                    <div class="bg-purple-50 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-medium text-purple-800 mb-2">Total Items Sold</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $totalItems }}</p>
                        <p class="text-sm text-purple-500 mt-1">For the selected period</p>
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
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Top Products</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($topProducts as $product)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $product->total_quantity }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }} {{ number_format($product->total_revenue, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Top Categories -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Top Categories</h3>
                        <div class="h-64">
                            <canvas id="categoriesChart"></canvas>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Show/hide custom date inputs based on date range selection
        document.getElementById('date_range').addEventListener('change', function() {
            const customDateInputs = document.getElementById('custom_date_inputs');
            if (this.value === 'custom') {
                customDateInputs.style.display = 'flex';
            } else {
                customDateInputs.style.display = 'none';
            }
        });
        
        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesData = @json($salesByPeriod);
        const labels = Object.keys(salesData);
        const counts = labels.map(label => salesData[label].count);
        const totals = labels.map(label => salesData[label].total);
        const currencySymbol = '{{ config('settings.currency_symbol', '$') }}';
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Number of Sales',
                        data: counts,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Revenue (' + currencySymbol + ')',
                        data: totals,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Sales'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Revenue (' + currencySymbol + ')'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 1) {
                                    return label + currencySymbol + ' ' + context.parsed.y.toFixed(2);
                                }
                                return label + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });
        
        // Categories Chart
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categories = @json($topCategories);
        
        new Chart(categoriesCtx, {
            type: 'pie',
            data: {
                labels: categories.map(cat => cat.name),
                datasets: [{
                    data: categories.map(cat => cat.total_quantity),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} items (${percentage}%)`;
                            },
                            afterLabel: function(context) {
                                const cat = categories[context.dataIndex];
                                return `Revenue: ${currencySymbol} ${parseFloat(cat.total_revenue).toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>