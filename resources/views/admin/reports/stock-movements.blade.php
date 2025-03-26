<!-- resources/views/admin/reports/stock-movements.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-semibold mb-6">Stock Movements Report</h1>
                
                <!-- Filter Section -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <form action="{{ route('admin.reports.stock-movements') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Movement Type</label>
                            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Types</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Stock In</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Stock Out</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="product" class="block text-sm font-medium text-gray-700">Product</label>
                            <select id="product" name="product" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                        </div>
                        
                        <div>
                            <a href="{{ route('admin.reports.stock-movements.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Total Movements</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $movements->total() }}</p>
                        <p class="text-sm text-blue-500 mt-1">For the selected period</p>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-medium text-green-800 mb-2">Stock In</h3>
                        <p class="text-3xl font-bold text-green-600">
                            {{ $movements->where('type', 'in')->count() }}
                        </p>
                        <p class="text-sm text-green-500 mt-1">Items added to inventory</p>
                    </div>
                    
                    <div class="bg-red-50 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-medium text-red-800 mb-2">Stock Out</h3>
                        <p class="text-3xl font-bold text-red-600">
                            {{ $movements->where('type', 'out')->count() }}
                        </p>
                        <p class="text-sm text-red-500 mt-1">Items removed from inventory</p>
                    </div>
                </div>
                
                <!-- Movement Trends Chart -->
                <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Stock Movement Trends</h3>
                    <div class="h-80">
                        <canvas id="movementChart"></canvas>
                    </div>
                </div>
                
                <!-- Stock Movements Table -->
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Stock Movements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($movements as $movement)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $movement->product ? $movement->product->name : 'Unknown Product' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $movement->type == 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $movement->quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst($movement->reference_type) }} #{{ $movement->reference_id }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $movement->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $movements->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare data for movement trend chart
            // Group movements by date and type
            const movementsData = [];
            
            // Get date range
            const startDate = new Date('{{ $startDate->format('Y-m-d') }}');
            const endDate = new Date('{{ $endDate->format('Y-m-d') }}');
            
            // Prepare dates array for chart
            const dateLabels = [];
            const stockInData = [];
            const stockOutData = [];
            
            // Group movements by date and type
            const movementsByDate = {};
            
            // Initialize all dates in range with zeros
            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                const dateStr = currentDate.toISOString().split('T')[0];
                dateLabels.push(dateStr);
                movementsByDate[dateStr] = { in: 0, out: 0 };
                
                // Move to next day
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            // Group movements for chart
            @foreach($movements as $movement)
                const movementDate = '{{ $movement->created_at->format('Y-m-d') }}';
                const type = '{{ $movement->type }}';
                const quantity = {{ $movement->quantity }};
                
                if (movementsByDate[movementDate]) {
                    movementsByDate[movementDate][type] += quantity;
                }
            @endforeach
            
            // Prepare data arrays for chart
            dateLabels.forEach(date => {
                stockInData.push(movementsByDate[date].in);
                stockOutData.push(movementsByDate[date].out);
            });
            
            // Create stock movement chart
            const ctx = document.getElementById('movementChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dateLabels,
                    datasets: [
                        {
                            label: 'Stock In',
                            data: stockInData,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Stock Out',
                            data: stockOutData,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false,
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            stacked: false,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>