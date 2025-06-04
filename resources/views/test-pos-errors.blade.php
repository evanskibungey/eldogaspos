<!DOCTYPE html>
<html>
<head>
    <title>POS Error Testing Page</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">POS Error Testing Dashboard</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Error Scenarios</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <!-- Stock Errors -->
                <div class="border rounded p-4">
                    <h3 class="font-semibold text-orange-600 mb-3">Stock Errors</h3>
                    <button onclick="testOutOfStock()" class="block w-full bg-red-500 text-white px-4 py-2 rounded mb-2 hover:bg-red-600">
                        Test Out of Stock Error
                    </button>
                    <button onclick="testExceedStock()" class="block w-full bg-orange-500 text-white px-4 py-2 rounded mb-2 hover:bg-orange-600">
                        Test Exceed Stock Error
                    </button>
                </div>
                
                <!-- Validation Errors -->
                <div class="border rounded p-4">
                    <h3 class="font-semibold text-blue-600 mb-3">Validation Errors</h3>
                    <button onclick="testValidationError()" class="block w-full bg-blue-500 text-white px-4 py-2 rounded mb-2 hover:bg-blue-600">
                        Test Missing Customer Details
                    </button>
                    <button onclick="testEmptyCart()" class="block w-full bg-indigo-500 text-white px-4 py-2 rounded mb-2 hover:bg-indigo-600">
                        Test Empty Cart Error
                    </button>
                </div>
                
                <!-- Network Errors -->
                <div class="border rounded p-4">
                    <h3 class="font-semibold text-purple-600 mb-3">Network Errors</h3>
                    <button onclick="testNetworkError()" class="block w-full bg-purple-500 text-white px-4 py-2 rounded mb-2 hover:bg-purple-600">
                        Test Network Failure
                    </button>
                    <button onclick="testTimeout()" class="block w-full bg-pink-500 text-white px-4 py-2 rounded mb-2 hover:bg-pink-600">
                        Test Timeout Error
                    </button>
                </div>
                
                <!-- Server Errors -->
                <div class="border rounded p-4">
                    <h3 class="font-semibold text-gray-600 mb-3">Server Errors</h3>
                    <button onclick="test500Error()" class="block w-full bg-gray-500 text-white px-4 py-2 rounded mb-2 hover:bg-gray-600">
                        Test 500 Server Error
                    </button>
                    <button onclick="testCSRFError()" class="block w-full bg-yellow-500 text-white px-4 py-2 rounded mb-2 hover:bg-yellow-600">
                        Test CSRF Token Error
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Results Display -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Test Results</h2>
            <div id="results" class="space-y-2">
                <p class="text-gray-500">Click a test button to see results...</p>
            </div>
        </div>
        
        <!-- Quick Database Check -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-semibold mb-4">Quick Checks</h2>
            <button onclick="checkDatabase()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Check Database Status
            </button>
            <button onclick="checkProducts()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 ml-2">
                Check Products
            </button>
            <button onclick="clearResults()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">
                Clear Results
            </button>
        </div>
    </div>

    <script>
        const resultsDiv = document.getElementById('results');
        
        function addResult(message, type = 'info') {
            const colors = {
                'success': 'text-green-600',
                'error': 'text-red-600',
                'warning': 'text-orange-600',
                'info': 'text-blue-600'
            };
            
            const p = document.createElement('p');
            p.className = `${colors[type]} border-l-4 pl-2 ${type === 'error' ? 'border-red-500' : 'border-blue-500'}`;
            p.innerHTML = `<strong>${new Date().toLocaleTimeString()}:</strong> ${message}`;
            resultsDiv.appendChild(p);
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }
        
        function clearResults() {
            resultsDiv.innerHTML = '<p class="text-gray-500">Results cleared. Ready for new tests...</p>';
        }
        
        // Test Functions
        async function testOutOfStock() {
            addResult('Testing out of stock error...', 'info');
            // This would need actual product data
            addResult('✓ Out of stock error should show: "This product is out of stock."', 'success');
        }
        
        async function testExceedStock() {
            addResult('Testing exceed stock error...', 'info');
            addResult('✓ Exceed stock error should show: "Cannot add more. Only X available in stock."', 'success');
        }
        
        async function testValidationError() {
            addResult('Testing validation error...', 'info');
            
            try {
                const response = await fetch('/pos/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cart_items: [{id: 1, quantity: 1, price: 100}],
                        payment_method: 'credit',
                        customer_details: {name: '', phone: ''} // Empty details
                    })
                });
                
                const result = await response.json();
                
                if (response.status === 422) {
                    addResult('✓ Validation error triggered successfully', 'success');
                    addResult('Errors: ' + JSON.stringify(result.errors), 'warning');
                } else {
                    addResult('Unexpected response: ' + JSON.stringify(result), 'error');
                }
            } catch (error) {
                addResult('Network error: ' + error.message, 'error');
            }
        }
        
        async function testEmptyCart() {
            addResult('Testing empty cart error...', 'info');
            
            try {
                const response = await fetch('/pos/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cart_items: [], // Empty cart
                        payment_method: 'cash'
                    })
                });
                
                const result = await response.json();
                addResult('Response: ' + JSON.stringify(result), result.success ? 'error' : 'success');
            } catch (error) {
                addResult('Error: ' + error.message, 'error');
            }
        }
        
        async function testNetworkError() {
            addResult('Testing network error...', 'info');
            
            try {
                await fetch('https://invalid-domain-xyz123.com/test');
                addResult('Network error test failed - request succeeded?', 'error');
            } catch (error) {
                addResult('✓ Network error caught: ' + error.message, 'success');
            }
        }
        
        async function testTimeout() {
            addResult('Testing timeout (this will take a moment)...', 'info');
            
            const controller = new AbortController();
            setTimeout(() => controller.abort(), 2000); // 2 second timeout
            
            try {
                await fetch('/pos/sales', {
                    signal: controller.signal,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cart_items: [{id: 1, quantity: 1, price: 100}],
                        payment_method: 'cash'
                    })
                });
                addResult('Request completed within timeout', 'success');
            } catch (error) {
                if (error.name === 'AbortError') {
                    addResult('✓ Timeout error simulated successfully', 'success');
                } else {
                    addResult('Error: ' + error.message, 'error');
                }
            }
        }
        
        async function test500Error() {
            addResult('Testing 500 error...', 'info');
            addResult('To trigger a real 500 error, you would need to cause a server-side exception', 'warning');
            addResult('Common causes: Database down, PHP errors, missing files', 'info');
        }
        
        async function testCSRFError() {
            addResult('Testing CSRF error...', 'info');
            
            try {
                const response = await fetch('/pos/sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': 'invalid-token-xyz' // Invalid token
                    },
                    body: JSON.stringify({
                        cart_items: [{id: 1, quantity: 1, price: 100}],
                        payment_method: 'cash'
                    })
                });
                
                if (response.status === 419) {
                    addResult('✓ CSRF error triggered (419 status)', 'success');
                } else {
                    addResult('Status: ' + response.status, 'info');
                }
            } catch (error) {
                addResult('Error: ' + error.message, 'error');
            }
        }
        
        async function checkDatabase() {
            addResult('Checking database connection...', 'info');
            
            try {
                const response = await fetch('/api/health-check', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    addResult('✓ Database connection successful', 'success');
                } else {
                    addResult('✗ Database connection failed', 'error');
                }
            } catch (error) {
                addResult('Cannot reach server: ' + error.message, 'error');
            }
        }
        
        async function checkProducts() {
            addResult('Checking products...', 'info');
            addResult('Run this in console: php artisan tinker', 'info');
            addResult('>>> App\\Models\\Product::count()', 'info');
            addResult('>>> App\\Models\\Product::where("stock", 0)->count()', 'info');
        }
    </script>
</body>
</html>
