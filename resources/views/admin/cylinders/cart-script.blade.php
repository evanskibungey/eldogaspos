<script>
const cart = [];
const storeRoute = '{{ route($storeRoute) }}';
const quickCreateUrl = '{{ $quickCreateUrl }}';
let currentProductForBrand = null;
let lastSelectedBrand = null;

const brandModal = document.getElementById('brand-modal');
const brandConfirm = document.getElementById('brand-confirm');
const brandCancel = document.getElementById('brand-cancel');
const brandModalClose = document.getElementById('brand-modal-close');
const totalQuantityInput = document.getElementById('total-quantity');
const brandInputsContainer = document.getElementById('brand-inputs-container');
const autoFillBrands = document.getElementById('auto-fill-brands');

function generateBrandInputs(quantity) {
    brandInputsContainer.innerHTML = '';
    for (let i = 1; i <= quantity; i++) {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-2 sm:p-2.5 bg-white rounded-lg border-2 border-green-200 hover:border-green-400 transition-all';
        div.innerHTML = `
            <div class="flex-shrink-0 w-6 h-6 sm:w-7 sm:h-7 bg-green-500 text-white rounded-lg flex items-center justify-center font-bold text-xs sm:text-sm">${i}</div>
            <div class="flex-1 min-w-0">
                <input type="text" class="brand-input w-full px-2 sm:px-3 py-1.5 sm:py-2 border-2 border-gray-200 rounded-lg text-xs sm:text-sm font-medium focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="Brand..." data-index="${i-1}">
            </div>
        `;
        brandInputsContainer.appendChild(div);
    }
}

totalQuantityInput.addEventListener('input', function() {
    const quantity = parseInt(this.value) || 1;
    if (quantity > 0 && quantity <= (currentProductForBrand?.stock || 999)) {
        generateBrandInputs(quantity);
    } else if (quantity > currentProductForBrand?.stock) {
        alert(`Maximum available: ${currentProductForBrand.stock}`);
        this.value = currentProductForBrand.stock;
        generateBrandInputs(currentProductForBrand.stock);
    }
});

// Add increase/decrease quantity button handlers
document.getElementById('increase-quantity')?.addEventListener('click', function() {
    const currentQty = parseInt(totalQuantityInput.value) || 1;
    const maxStock = currentProductForBrand?.stock || 999;
    if (currentQty < maxStock) {
        totalQuantityInput.value = currentQty + 1;
        generateBrandInputs(currentQty + 1);
    } else {
        alert(`Maximum available: ${maxStock}`);
    }
});

document.getElementById('decrease-quantity')?.addEventListener('click', function() {
    const currentQty = parseInt(totalQuantityInput.value) || 1;
    if (currentQty > 1) {
        totalQuantityInput.value = currentQty - 1;
        generateBrandInputs(currentQty - 1);
    }
});

document.querySelectorAll('.quick-brand').forEach(btn => {
    btn.addEventListener('click', function() {
        const brand = this.textContent.trim();
        lastSelectedBrand = brand;
        const inputs = document.querySelectorAll('.brand-input');
        const focusedInput = document.activeElement;
        if (focusedInput && focusedInput.classList.contains('brand-input')) {
            focusedInput.value = brand;
        } else {
            const emptyInput = Array.from(inputs).find(input => !input.value);
            if (emptyInput) {
                emptyInput.value = brand;
                emptyInput.focus();
            }
        }
    });
});

autoFillBrands.addEventListener('click', function() {
    const brand = lastSelectedBrand || prompt('Enter brand to auto-fill:');
    if (brand) {
        document.querySelectorAll('.brand-input').forEach(input => {
            if (!input.value) input.value = brand;
        });
    }
});

brandCancel.addEventListener('click', () => { brandModal.classList.add('hidden'); currentProductForBrand = null; });
brandModalClose.addEventListener('click', () => { brandModal.classList.add('hidden'); currentProductForBrand = null; });

brandConfirm.addEventListener('click', () => {
    const brandInputs = document.querySelectorAll('.brand-input');
    const brands = Array.from(brandInputs).map(input => input.value.trim()).filter(b => b);
    if (brands.length !== brandInputs.length) {
        alert('Please enter a brand for all units');
        return;
    }
    if (currentProductForBrand) addProductsToCart(currentProductForBrand, brands);
    brandModal.classList.add('hidden');
    currentProductForBrand = null;
});

// Make entire product card clickable
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // Don't trigger if clicking on the icon itself (though it doesn't matter now)
        currentProductForBrand = {
            id: this.dataset.id,
            name: this.dataset.name,
            price: parseFloat(this.dataset.price),
            stock: parseInt(this.dataset.stock)
        };
        document.getElementById('modal-product-name').textContent = currentProductForBrand.name;
        document.getElementById('modal-product-price').textContent = `Price: KSh ${currentProductForBrand.price.toLocaleString()} per unit`;
        document.getElementById('modal-product-stock').textContent = `Available Stock: ${currentProductForBrand.stock} units`;
        totalQuantityInput.value = 1;
        totalQuantityInput.max = currentProductForBrand.stock;
        generateBrandInputs(1);
        brandModal.classList.remove('hidden');
        totalQuantityInput.focus();
    });
});

function addProductsToCart(productData, brands) {
    const brandCounts = brands.reduce((acc, brand) => {
        acc[brand] = (acc[brand] || 0) + 1;
        return acc;
    }, {});
    Object.entries(brandCounts).forEach(([brand, quantity]) => {
        const existing = cart.find(item => item.id === productData.id && item.brand === brand);
        if (existing) {
            const newTotal = existing.quantity + quantity;
            if (newTotal <= productData.stock) existing.quantity = newTotal;
            else { alert(`Insufficient stock for ${productData.name} - ${brand}`); return; }
        } else {
            cart.push({ id: productData.id, name: productData.name, price: productData.price, stock: productData.stock, brand: brand, quantity: quantity });
        }
    });
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm text-center py-8">No items</p>';
        updateTotals();
        return;
    }
    container.innerHTML = cart.map((item, index) => `
        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <p class="font-semibold text-sm text-gray-900">${item.name}</p>
                    <p class="text-xs text-orange-600 font-medium">${item.brand}</p>
                    <p class="text-xs text-gray-600">KSh ${item.price.toLocaleString()} each</p>
                </div>
                <button onclick="removeItem(${index})" class="text-red-600 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQty(${index}, -1)" class="px-3 py-1 bg-white border-2 border-gray-300 rounded hover:bg-orange-50 hover:border-orange-500 transition-all">-</button>
                <span class="w-12 text-center font-bold text-gray-900">${item.quantity}</span>
                <button onclick="updateQty(${index}, 1)" class="px-3 py-1 bg-white border-2 border-gray-300 rounded hover:bg-orange-50 hover:border-orange-500 transition-all">+</button>
                <span class="ml-auto font-semibold text-orange-600">KSh ${(item.price * item.quantity).toLocaleString()}</span>
            </div>
        </div>
    `).join('');
    updateTotals();
}

function updateQty(index, delta) {
    const item = cart[index];
    const newQty = item.quantity + delta;
    if (newQty <= 0) cart.splice(index, 1);
    else if (newQty <= item.stock) item.quantity = newQty;
    else { alert('Insufficient stock'); return; }
    renderCart();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const deposit = parseFloat(document.getElementById('deposit')?.value || 0);
    const total = subtotal + deposit;
    document.getElementById('subtotal').textContent = 'KSh ' + subtotal.toLocaleString();
    document.getElementById('total').textContent = 'KSh ' + total.toLocaleString();
}

document.querySelectorAll('input[name="transaction_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const depositWrapper = document.getElementById('deposit-wrapper');
        depositWrapper.classList.toggle('hidden', this.value !== 'advance_collection');
        updateTotals();
    });
});

document.getElementById('deposit')?.addEventListener('input', updateTotals);

const customerSearch = document.getElementById('customer_search');
const customerDropdown = document.getElementById('customer_dropdown');
const newCustomerForm = document.getElementById('new_customer_form');

customerSearch.addEventListener('focus', () => { customerDropdown.classList.remove('hidden'); newCustomerForm.classList.add('hidden'); });
customerSearch.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.customer-option').forEach(opt => {
        opt.style.display = opt.dataset.search.includes(query) ? 'block' : 'none';
    });
});

document.querySelectorAll('.customer-option').forEach(opt => {
    opt.addEventListener('click', function() {
        const id = this.dataset.id;
        if (id === 'new') {
            customerSearch.value = '';
            newCustomerForm.classList.remove('hidden');
            customerDropdown.classList.add('hidden');
            document.getElementById('customer_id').value = '';
            document.getElementById('new_name').focus();
        } else {
            customerSearch.value = this.dataset.name + ' - ' + this.dataset.phone;
            document.getElementById('customer_id').value = id;
            document.getElementById('customer_name').value = this.dataset.name;
            document.getElementById('customer_phone').value = this.dataset.phone;
            newCustomerForm.classList.add('hidden');
            customerDropdown.classList.add('hidden');
        }
    });
});

document.addEventListener('click', (e) => {
    if (!customerSearch.contains(e.target) && !customerDropdown.contains(e.target) && !newCustomerForm.contains(e.target)) {
        customerDropdown.classList.add('hidden');
    }
});

document.getElementById('create_customer_btn').addEventListener('click', async function() {
    const name = document.getElementById('new_name').value.trim();
    const phone = document.getElementById('new_phone').value.trim();
    if (!name || !phone) { alert('Please enter both name and phone number'); return; }
    this.disabled = true;
    this.textContent = 'Creating...';
    try {
        const response = await fetch(quickCreateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ name, phone })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('customer_id').value = data.customer.id;
            document.getElementById('customer_name').value = data.customer.name;
            document.getElementById('customer_phone').value = data.customer.phone;
            customerSearch.value = data.customer.name + ' - ' + data.customer.phone;
            newCustomerForm.classList.add('hidden');
            alert('Customer created successfully!');
        } else alert(data.message || 'Failed to create customer');
    } catch (error) { alert('Network error. Please try again.'); }
    finally { this.disabled = false; this.textContent = 'Create & Select Customer'; }
});

document.getElementById('submit-btn').addEventListener('click', async function() {
    if (cart.length === 0) { alert('Please add at least one product'); return; }
    const customerId = document.getElementById('customer_id').value;
    const newName = document.getElementById('new_name')?.value;
    const newPhone = document.getElementById('new_phone')?.value;
    if (!customerId && (!newName || !newPhone)) { alert('Please select or add a customer'); return; }
    const transactionType = document.querySelector('input[name="transaction_type"]:checked').value;
    const paymentStatus = document.querySelector('input[name="payment"]:checked').value;
    const depositAmount = document.getElementById('deposit')?.value || 0;
    const notes = document.getElementById('notes').value;
    if (transactionType === 'advance_collection' && (!depositAmount || parseFloat(depositAmount) <= 0)) {
        alert('Deposit amount is required for advance collection');
        document.getElementById('deposit').focus();
        return;
    }
    const data = {
        transaction_type: transactionType,
        payment_status: paymentStatus,
        deposit_amount: depositAmount,
        notes: notes,
        items: cart.map(item => ({ product_id: item.id, brand: item.brand, quantity: item.quantity })),
        _token: '{{ csrf_token() }}'
    };
    if (customerId) data.customer_id = customerId;
    else { data.customer_name = newName; data.customer_phone = newPhone; }
    this.disabled = true;
    this.textContent = 'Creating...';
    try {
        const response = await fetch(storeRoute, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok) {
            const transactionId = result.transaction_id;
            const baseUrl = '{{ $isPosContext ? url("/pos/cylinders") : url("/admin/cylinders") }}';
            const receiptUrl = baseUrl + '/' + transactionId + '/receipt';
            window.location.href = receiptUrl;
        } else {
            console.error('Server error:', result);
            const errorMsg = result.message || (result.errors ? JSON.stringify(result.errors) : 'Failed to create transaction');
            alert('Error: ' + errorMsg);
            this.disabled = false;
            this.textContent = 'Create Transaction';
        }
    } catch (error) {
        console.error('Network error:', error);
        alert('Network error: ' + error.message + '. Please check console for details.');
        this.disabled = false;
        this.textContent = 'Create Transaction';
    }
});
</script>
