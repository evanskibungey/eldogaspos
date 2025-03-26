<x-app-layout>
    <div class="py-8 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-7 h-7 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $companyName }} - Daily Sales Report
                        </h1>
                    </div>
                    
                    <div class="mt-4 md:mt-0 flex flex-wrap items-center gap-2">
                        <form action="{{ route('pos.reports.daily') }}" method="GET" class="flex items-center space-x-2">
                            <div class="flex flex-col">
                                <label for="date" class="text-xs text-gray-500 mb-1">Select Date</label>
                                <input type="date" id="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" 
                                    class="rounded-md shadow-sm border-gray-300 focus:border-[#FF6900] focus:ring focus:ring-orange-200 focus:ring-opacity-50 transition-colors">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#0077B5] border border-transparent rounded-md font-medium text-xs text-white tracking-widest hover:bg-blue-700 transition-colors duration-200 shadow-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                View
                            </button>
                        </form>
                        
                        <a href="{{ route('pos.reports.export', ['date' => $date->format('Y-m-d')]) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-medium text-xs text-gray-700 tracking-widest hover:bg-gray-300 transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </a>
                        
                        <a href="{{ route('pos.reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-xs text-gray-700 tracking-widest hover:bg-gray-200 transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Reports
                        </a>
                    </div>
                </div>
                
                <!-- Date Display -->
                <div class="mb-8 text-center bg-gray-50 py-3 px-4 rounded-lg shadow-sm border border-gray-100">
                    <h2 class="text-xl font-medium text-gray-700">
                        Sales for <span class="text-[#FF6900] font-semibold">{{ $date->format('l, F j, Y') }}</span>
                    </h2>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 shadow-sm border border-blue-200">
                        <h3 class="text-lg font-medium text-blue-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            Sales Count
                        </h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalSales }}</p>
                        <p class="text-sm text-blue-500 mt-1">Total sales</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-5 shadow-sm border border-green-200">
                        <h3 class="text-lg font-medium text-green-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Revenue
                        </h3>
                        <p class="text-3xl font-bold text-green-600">{{ $currencySymbol }} {{ number_format($totalRevenue, 2) }}</p>
                        <p class="text-sm text-green-500 mt-1">Total revenue</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-5 shadow-sm border border-purple-200">
                        <h3 class="text-lg font-medium text-purple-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Items Sold
                        </h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $totalItems }}</p>
                        <p class="text-sm text-purple-500 mt-1">Total items sold</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-5 shadow-sm border border-orange-200">
                        <h3 class="text-lg font-medium text-[#FF6900] mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Average Sale
                        </h3>
                        <p class="text-3xl font-bold text-[#FF6900]">{{ $currencySymbol }} {{ number_format($averageSaleValue, 2) }}</p>
                        <p class="text-sm text-orange-500 mt-1">Average value per sale</p>
                    </div>
                </div>
                
                <!-- Payment Method Breakdown -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                        Payment Methods
                    </h3>
                    <div class="flex flex-wrap gap-4">
                        @if(isset($paymentMethodBreakdown['cash']))
                            <div class="bg-green-50 rounded-lg px-4 py-3 flex-1 min-w-[200px] border border-green-200 shadow-sm">
                                <h4 class="text-md font-medium text-green-800 mb-1 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                    Cash
                                </h4>
                                <p class="text-2xl font-bold text-green-600">{{ $currencySymbol }} {{ number_format($paymentMethodBreakdown['cash']['total'], 2) }}</p>
                                <p class="text-sm text-green-500">{{ $paymentMethodBreakdown['cash']['count'] }} transactions</p>
                            </div>
                        @endif
                        
                        @if(isset($paymentMethodBreakdown['credit']))
                            <div class="bg-blue-50 rounded-lg px-4 py-3 flex-1 min-w-[200px] border border-blue-200 shadow-sm">
                                <h4 class="text-md font-medium text-blue-800 mb-1 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Credit
                                </h4>
                                <p class="text-2xl font-bold text-blue-600">{{ $currencySymbol }} {{ number_format($paymentMethodBreakdown['credit']['total'], 2) }}</p>
                                <p class="text-sm text-blue-500">{{ $paymentMethodBreakdown['credit']['count'] }} transactions</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                        Sales by Hour
                    </h3>
                    <div class="h-64">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Transactions
                    </h3>
                    
                    @if($sales->count() > 0)
                        <div class="overflow-x-auto rounded-lg border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sales as $sale)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->created_at->format('H:i:s') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sale->receipt_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_method == 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ ucfirst($sale->payment_method) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->items->sum('quantity') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $currencySymbol }} {{ number_format($sale->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                <a href="{{ route('pos.sales.show', $sale) }}" class="text-[#0077B5] hover:text-blue-900 flex items-center justify-end">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $totalItems }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">{{ $currencySymbol }} {{ number_format($totalRevenue, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-100">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No sales recorded</h3>
                            <p class="mt-2 text-base text-gray-500">There are no sales recorded for this date.</p>
                            <div class="mt-6">
                                <a href="{{ route('pos.dashboard') }}" class="inline-flex items-center px-5 py-3 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#FF6900] hover:bg-orange-700 focus:outline-none transition-colors duration-200">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Create New Sale
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for hourly sales chart
        const salesByHour = @json($salesByHour);
        const hours = [];
        const counts = [];
        const totals = [];
        
        // Initialize all 24 hours with zeros
        for (let i = 0; i < 24; i++) {
            hours.push(i + ':00');
            counts.push(0);
            totals.push(0);
        }
        
        // Fill in actual data where we have it
        salesByHour.forEach(sale => {
            counts[sale.hour] = sale.count;
            totals[sale.hour] = sale.total;
        });
        
        // Create sales chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: hours,
                datasets: [
                    {
                        label: 'Number of Sales',
                        data: counts,
                        backgroundColor: 'rgba(0, 119, 181, 0.5)', // #0077B5 with opacity
                        borderColor: '#0077B5',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue ({{ $currencySymbol }})',
                        data: totals,
                        backgroundColor: 'rgba(255, 105, 0, 0.5)', // #FF6900 with opacity
                        borderColor: '#FF6900',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Sales'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Revenue ({{ $currencySymbol }})'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw;
                                if (context.datasetIndex === 1) { // Revenue dataset
                                    return label + ': {{ $currencySymbol }} ' + value.toFixed(2);
                                }
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>