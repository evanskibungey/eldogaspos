<!-- resources/views/admin/reports/users.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-semibold mb-6">Cashier Performance Reports</h1>
                
                <!-- Date Range Filters -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <form action="{{ route('admin.reports.users') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                        </div>
                        
                        <div>
                            <a href="{{ route('admin.reports.users.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export to CSV
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Performance Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Sales Count Chart -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Sales Count by Cashier</h3>
                        <div class="h-80">
                            <canvas id="salesCountChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Revenue by Cashier</h3>
                        <div class="h-80">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Comparison -->
                <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Cashier Performance Comparison</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Count</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Items Sold</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Per Sale</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($cashierPerformance as $id => $performance)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $performance['name'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $performance['sales_count'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">$ {{ number_format($performance['sales_total'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $performance['items_sold'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">$ {{ number_format($performance['average_per_sale'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Performance Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Items Sold Chart -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Items Sold by Cashier</h3>
                        <div class="h-80">
                            <canvas id="itemsSoldChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Average Sale Value Chart -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Average Sale Value by Cashier</h3>
                        <div class="h-80">
                            <canvas id="averageSaleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data from cashier performance
        const cashierPerformance = @json($cashierPerformance);
        const cashiers = [];
        const salesCounts = [];
        const revenueTotals = [];
        const itemsSold = [];
        const averageSales = [];
        
        // We need to convert the object to arrays for Chart.js
        Object.keys(cashierPerformance).forEach(id => {
            const data = cashierPerformance[id];
            cashiers.push(data.name);
            salesCounts.push(data.sales_count);
            revenueTotals.push(data.sales_total);
            itemsSold.push(data.items_sold);
            averageSales.push(data.average_per_sale);
        });
        
        // Random color generator for charts
        function getRandomColors(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const color = `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`;
                colors.push(color);
            }
            return colors;
        }
        
        const barColors = getRandomColors(cashiers.length);
        
        // Sales Count Chart
        new Chart(document.getElementById('salesCountChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cashiers,
                datasets: [{
                    label: 'Number of Sales',
                    data: salesCounts,
                    backgroundColor: barColors,
                    borderColor: barColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Count'
                        }
                    }
                }
            }
        });
        
        // Revenue Chart
        new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cashiers,
                datasets: [{
                    label: 'Total Revenue ($)',
                    data: revenueTotals,
                    backgroundColor: barColors,
                    borderColor: barColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    }
                }
            }
        });
        
        // Items Sold Chart
        new Chart(document.getElementById('itemsSoldChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cashiers,
                datasets: [{
                    label: 'Items Sold',
                    data: itemsSold,
                    backgroundColor: barColors,
                    borderColor: barColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Items Count'
                        }
                    }
                }
            }
        });
        
        // Average Sale Value Chart
        new Chart(document.getElementById('averageSaleChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cashiers,
                datasets: [{
                    label: 'Average Sale Value ($)',
                    data: averageSales,
                    backgroundColor: barColors,
                    borderColor: barColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Average Sale Value ($)'
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>