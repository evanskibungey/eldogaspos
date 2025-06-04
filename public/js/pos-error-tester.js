// POS Error Testing Helper
// Copy and paste this entire script into your browser console while on the POS dashboard

window.posErrorTester = {
    // Get Alpine component
    getPos: function() {
        return Alpine.$data(document.querySelector('[x-data="posSystem()"]'));
    },
    
    // Test out of stock error
    testOutOfStock: function() {
        console.log('🔴 Testing: Out of Stock Error');
        const pos = this.getPos();
        pos.showError = true;
        pos.errorMessage = 'This product is out of stock.';
        console.log('✅ Error should appear and auto-close in 3 seconds');
        setTimeout(() => { pos.showError = false; }, 3000);
    },
    
    // Test exceed stock error
    testExceedStock: function() {
        console.log('🟠 Testing: Exceed Stock Error');
        const pos = this.getPos();
        pos.showError = true;
        pos.errorMessage = 'Cannot add more. Only 5 available in stock.';
        console.log('✅ Error should appear and auto-close in 3 seconds');
        setTimeout(() => { pos.showError = false; }, 3000);
    },
    
    // Test network error
    testNetworkError: function() {
        console.log('🔵 Testing: Network Error');
        const pos = this.getPos();
        pos.showError = true;
        pos.errorMessage = 'Network error or server exception occurred.';
        console.log('✅ Error should appear and auto-close in 5 seconds');
        setTimeout(() => { pos.showError = false; }, 5000);
    },
    
    // Test validation error
    testValidationError: function() {
        console.log('🟣 Testing: Validation Error');
        fetch('/pos/sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                cart_items: [{id: 1, quantity: 1, price: 100}],
                payment_method: 'credit',
                customer_details: {name: '', phone: ''}
            })
        })
        .then(r => r.json())
        .then(result => {
            console.log('📋 Server Response:', result);
            if (result.errors) {
                console.log('✅ Validation errors received:', result.errors);
            }
        })
        .catch(error => {
            console.error('❌ Request failed:', error);
        });
    },
    
    // Test empty cart
    testEmptyCart: function() {
        console.log('🟡 Testing: Empty Cart Error');
        fetch('/pos/sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                cart_items: [],
                payment_method: 'cash'
            })
        })
        .then(r => r.json())
        .then(result => {
            console.log('📋 Server Response:', result);
        });
    },
    
    // Show current state
    showState: function() {
        const pos = this.getPos();
        console.log('📊 Current POS State:');
        console.log('- Show Error:', pos.showError);
        console.log('- Error Message:', pos.errorMessage);
        console.log('- Cart Items:', pos.cart.length);
        console.log('- Payment Method:', pos.paymentMethod);
        console.log('- Is Processing:', pos.isProcessing);
        console.log('- Initialized:', pos._initialized);
    },
    
    // Clear error
    clearError: function() {
        const pos = this.getPos();
        pos.showError = false;
        pos.errorMessage = '';
        console.log('🧹 Error cleared');
    },
    
    // Add test product to cart
    addTestProduct: function() {
        const pos = this.getPos();
        const testProduct = {
            id: 999,
            name: 'TEST PRODUCT',
            category_id: 1,
            category_name: 'Test',
            price: 100,
            stock: 5,
            min_stock: 1
        };
        pos.cart.push({
            ...testProduct,
            quantity: 1
        });
        pos.updateTotals();
        console.log('🛒 Test product added to cart');
    },
    
    // Show help
    help: function() {
        console.log(`
🧪 POS Error Testing Helper Commands:
====================================
posErrorTester.testOutOfStock()    - Test out of stock error
posErrorTester.testExceedStock()   - Test exceed stock error  
posErrorTester.testNetworkError()  - Test network error
posErrorTester.testValidationError() - Test validation error
posErrorTester.testEmptyCart()     - Test empty cart error
posErrorTester.showState()         - Show current POS state
posErrorTester.clearError()        - Clear any visible error
posErrorTester.addTestProduct()    - Add test product to cart
posErrorTester.runAllTests()       - Run all tests in sequence
posErrorTester.help()              - Show this help message
        `);
    },
    
    // Run all tests
    runAllTests: async function() {
        console.log('🚀 Running all error tests...\n');
        
        // Test 1
        this.testOutOfStock();
        await new Promise(resolve => setTimeout(resolve, 4000));
        
        // Test 2
        this.testExceedStock();
        await new Promise(resolve => setTimeout(resolve, 4000));
        
        // Test 3
        this.testNetworkError();
        await new Promise(resolve => setTimeout(resolve, 6000));
        
        // Test 4
        this.testValidationError();
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Test 5
        this.testEmptyCart();
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        console.log('\n✅ All tests completed!');
        this.showState();
    }
};

// Initialize
console.log('✨ POS Error Tester Loaded!');
console.log('Type: posErrorTester.help() for available commands');
console.log('Type: posErrorTester.runAllTests() to run all tests');

// Auto-clear any existing errors on load
posErrorTester.clearError();
