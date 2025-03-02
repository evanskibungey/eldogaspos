<!-- resources/views/admin/stock/movements/index.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('admin.stock.movements.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-input-label for="product_id" value="Product" />
                                <select name="product_id" id="product_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="type" value="Movement Type" />
                                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">All Types</option>
                                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stock In</option>
                                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stock Out</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="date_from" value="Date From" />
                                <x-text-input type="date" name="date_from" id="date_from" 
                                    value="{{ request('date_from') }}" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="date_to" value="Date To" />
                                <x-text-input type="date" name="date_to" id="date_to" 
                                    value="{{ request('date_to') }}" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button type="submit">
                                Filter Results
                            </x-primary-button>
                            <a href="{{ route('admin.stock.movements.export') }}" class="ml-4 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                Export CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock Movements Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Serial Number
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reference
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Updated By
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($movements as $movement)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $movement->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $movement->product->name }}</div>
                                    <div class="text-sm text-gray-500">SKU: {{ $movement->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $movement->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($movement->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $movement->quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $movement->serial_number ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ ucfirst(str_replace('_', ' ', $movement->reference_type)) }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $movement->notes }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $movement->creator->name ?? 'System' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $movements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>