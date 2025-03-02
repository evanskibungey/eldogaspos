<!-- resources/views/admin/reports/inventory.blade.php -->
<x-app-layout>
    <div class="py-8 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-7 h-7 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Eldo Gas - Inventory Reports
                        </h1>
                        <p class="text-sm text-gray-500 mt-1 ml-9">
                            Manage and monitor your product inventory
                        </p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Inventory Filters
                    </h3>
                    <form action="{{ route('admin.reports.inventory') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="category" name="category" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#FF6900] focus:ring focus:ring-orange-200 focus:ring-opacity-50 transition-colors">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="stock_status" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                            <select id="stock_status" name="stock_status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#FF6900] focus:ring focus:ring-orange-200 focus:ring-opacity-50 transition-colors">
                                <option value="">All Stock Status</option>
                                <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                                <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Out of Stock</option>
                                <option value="in" {{ request('stock_status') == 'in' ? 'selected' : '' }}>In Stock</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#0077B5] hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Apply Filters
                            </button>
                            
                            <a href="{{ route('admin.reports.inventory.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF6900] transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export to CSV
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-5 shadow-sm border border-blue-200">
                        <h3 class="text-lg font-medium text-blue-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Total Products
                        </h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalProducts }}</p>
                        <p class="text-sm text-blue-500 mt-1">Items in inventory</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-5 shadow-sm border border-green-200">
                        <h3 class="text-lg font-medium text-green-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Inventory Value
                        </h3>
                        <p class="text-3xl font-bold text-green-600">{{ config('settings.currency_symbol', '$') }} {{ number_format($totalValue, 2) }}</p>
                        <p class="text-sm text-green-500 mt-1">Total stock value</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-5 shadow-sm border border-yellow-200">
                        <h3 class="text-lg font-medium text-yellow-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Low Stock Items
                        </h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $lowStockCount }}</p>
                        <p class="text-sm text-yellow-500 mt-1">Below minimum threshold</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-5 shadow-sm border border-red-200">
                        <h3 class="text-lg font-medium text-red-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Out of Stock
                        </h3>
                        <p class="text-3xl font-bold text-red-600">{{ $outOfStockCount }}</p>
                        <p class="text-sm text-red-500 mt-1">Items with zero stock</p>
                    </div>
                </div>
                
                <!-- Stock Distribution Chart -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                        Stock Distribution by Category
                    </h3>
                    <div class="h-64">
                        <canvas id="stockDistributionChart"></canvas>
                    </div>
                </div>
                
                <!-- Products Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Products Inventory
                    </h3>
                    
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Min Stock</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($products as $product)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->category ? $product->category->name : 'Uncategorized' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->sku }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }} {{ number_format($product->price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->stock <= 0 ? 'bg-red-100 text-red-800' : ($product->stock <= $product->min_stock ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                                {{ $product->stock }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ $product->min_stock }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ config('settings.currency_symbol', '$') }} {{ number_format($product->stock * $product->price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @if($product->stock <= 0)
                                                <span class="px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Out of Stock
                                                </span>
                                            @elseif($product->stock <= $product->min_stock)
                                                <span class="px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    In Stock
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">Total Inventory Value:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ config('settings.currency_symbol', '$') }} {{ number_format($totalValue, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    @if(method_exists($products, 'links'))
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for stock distribution chart
        const products = @json($products);
        const currencySymbol = '{{ config('settings.currency_symbol', '$') }}';
        
        // Group products by category
        const categoriesData = {};
        products.forEach(product => {
            const categoryName = product.category ? product.category.name : 'Uncategorized';
            if (!categoriesData[categoryName]) {
                categoriesData[categoryName] = {
                    totalItems: 0,
                    totalValue: 0,
                    lowStock: 0,
                    outOfStock: 0
                };
            }
            
            categoriesData[categoryName].totalItems += 1;
            categoriesData[categoryName].totalValue += product.stock * product.price;
            
            if (product.stock <= 0) {
                categoriesData[categoryName].outOfStock += 1;
            } else if (product.stock <= product.min_stock) {
                categoriesData[categoryName].lowStock += 1;
            }
        });
        
        // Prepare data for chart
        const categories = Object.keys(categoriesData);
        const totalItems = categories.map(cat => categoriesData[cat].totalItems);
        const totalValues = categories.map(cat => categoriesData[cat].totalValue);
        
        // Create chart
        const ctx = document.getElementById('stockDistributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'Number of Products',
                        data: totalItems,
                        backgroundColor: 'rgba(0, 119, 181, 0.5)', // #0077B5 with opacity
                        borderColor: '#0077B5',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Inventory Value (' + currencySymbol + ')',
                        data: totalValues,
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
                    y: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Products'
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
                            text: 'Inventory Value (' + currencySymbol + ')'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw;
                                if (context.datasetIndex === 1) { // Value dataset
                                    return label + ': ' + currencySymbol + ' ' + value.toFixed(2);
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