// Enhanced POS System with Offline Support
function enhancedPosSystem() {
    return {
        // State management
        products: [],
        filteredProducts: [],
        cart: [],
        categories: window.posCategories || [],
        searchQuery: '',
        currentCategory: null,
        paymentMethod: 'cash',
        customerDetails: { name: '', phone: '' },
        isLoading: false,
        isProcessing: false,
        showReceipt: false,
        receiptNumber: '',
        subtotal: 0,
        total: 0,
        showCategoryDrawer: false,
        cylinderStats: window.cylinderStats || { active_drop_offs: 0, active_advance_collections: 0 },
        
        // Customer management
        customers: [],
        filteredCustomers: [],
        selectedCustomer: null,
        customerMode: 'existing',
        customerSearch: '',
        customersLoaded: false,
        loadingCustomers: false,
        recentlyAddedCustomerId: null,

        // Offline status
        isOnline: navigator.onLine,
        pendingSyncCount: 0,
        showSyncStatus: false,
        offlineSalesSummary: null,
        
        // Initialize component
        init() {
            console.log('Initializing enhanced POS system...');
            
            // Load initial data
            this.loadProducts();
            
            // Only setup offline handlers if offline mode is enabled
            if (window.offlineModeEnabled) {
                this.setupOfflineHandlers();
                this.checkSyncStatus();
            }
            
            // Setup event listeners
            this.$watch('searchQuery', () => this.filterProducts());
            this.$watch('currentCategory', () => this.filterProducts());
            
            // Listen for connection status changes only if offline mode is enabled
            if (window.offlineModeEnabled) {
                window.addEventListener('connection-status-changed', (event) => {
                    this.isOnline = event.detail.isOnline;
                    if (this.isOnline) {
                        this.checkSyncStatus();
                    }
                });

                // Listen for offline sync updates
                window.addEventListener('offline-sync-update', () => {
                    this.checkSyncStatus();
                });
            }
            
            console.log('POS system initialized successfully');
        },

        // Setup offline handlers
        setupOfflineHandlers() {
            // Check if offline POS manager is available
            if (window.offlinePOS) {
                // Update sync status periodically
                setInterval(() => {
                    this.checkSyncStatus();
                }, 30000); // Every 30 seconds
            } else {
                console.log('Offline POS manager not available - running in online-only mode');
            }
        },

        // Check sync status
        async checkSyncStatus() {
            if (!window.offlineModeEnabled) return;
            
            try {
                if (window.offlinePOS && typeof window.offlinePOS.getPendingOperationsCount === 'function') {
                    // Get pending operations count
                    this.pendingSyncCount = await window.offlinePOS.getPendingOperationsCount();
                    
                    // Get offline sales summary
                    this.offlineSalesSummary = await window.offlinePOS.getOfflineSalesSummary();
                }
                
                // If online, check server sync status
                if (this.isOnline) {
                    const response = await fetch('/api/v1/offline/sync-status', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        // Update UI with server sync status if needed
                    }
                }
            } catch (error) {
                console.error('Error checking sync status:', error);
            }
        },

        // Force sync now
        async forceSyncNow() {
            if (!window.offlineModeEnabled || !this.isOnline || this.pendingSyncCount === 0) {
                return;
            }
            
            try {
                if (window.offlinePOS) {
                    await window.offlinePOS.startBackgroundSync();
                    this.showNotification('Synchronization started', 'success');
                    
                    // Update sync status after a delay
                    setTimeout(() => {
                        this.checkSyncStatus();
                    }, 2000);
                }
            } catch (error) {
                console.error('Error forcing sync:', error);
                this.showNotification('Sync failed. Please try again.', 'error');
            }
        },

        // Load products
        async loadProducts() {
            this.isLoading = true;
            try {
                // First, try to get products from offline storage if available
                if (window.offlineModeEnabled && window.offlinePOS && !this.isOnline) {
                    const offlineProducts = await this.getOfflineProducts();
                    if (offlineProducts && offlineProducts.length > 0) {
                        this.products = offlineProducts;
                        this.filterProducts();
                        return;
                    }
                }
                
                // Otherwise, fetch from server
                const response = await fetch('/pos/products', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.products = data;
                    this.filterProducts();
                    
                    // Store products offline for future use
                    if (window.offlineModeEnabled && window.offlinePOS && this.isOnline) {
                        await window.offlinePOS.storeProductsLocally(data);
                    }
                }
            } catch (error) {
                console.error('Error loading products:', error);
                // Try to load from offline storage as fallback
                if (window.offlineModeEnabled && window.offlinePOS) {
                    const offlineProducts = await this.getOfflineProducts();
                    if (offlineProducts && offlineProducts.length > 0) {
                        this.products = offlineProducts;
                        this.filterProducts();
                    }
                }
            } finally {
                this.isLoading = false;
            }
        },

        // Get offline products
        async getOfflineProducts() {
            try {
                if (window.offlinePOS && window.offlinePOS.db) {
                    return new Promise((resolve, reject) => {
                        const transaction = window.offlinePOS.db.transaction(['offline_products'], 'readonly');
                        const store = transaction.objectStore('offline_products');
                        const request = store.getAll();
                        
                        request.onsuccess = () => resolve(request.result);
                        request.onerror = () => reject(request.error);
                    });
                }
            } catch (error) {
                console.error('Error getting offline products:', error);
                return [];
            }
        },

        // Filter products
        filterProducts() {
            let filtered = this.products;
            
            // Filter by category
            if (this.currentCategory !== null) {
                filtered = filtered.filter(p => p.category_id === this.currentCategory);
            }
            
            // Filter by search query
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(p => 
                    p.name.toLowerCase().includes(query) ||
                    (p.sku && p.sku.toLowerCase().includes(query)) ||
                    (p.serial_number && p.serial_number.toLowerCase().includes(query))
                );
            }
            
            this.filteredProducts = filtered;
        },

        // Add to cart
        addToCart(product) {
            const existingItem = this.cart.find(item => item.id === product.id);
            
            if (existingItem) {
                if (existingItem.quantity < product.stock) {
                    existingItem.quantity++;
                } else {
                    this.showNotification('Insufficient stock', 'error');
                }
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: 1,
                    stock: product.stock,
                    category_name: product.category_name,
                    serial_number: product.serial_number
                });
            }
            
            this.calculateTotal();
        },

        // Update quantity
        updateQuantity(index, change) {
            const item = this.cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                this.removeFromCart(index);
            } else if (newQuantity <= item.stock) {
                item.quantity = newQuantity;
                this.calculateTotal();
            } else {
                this.showNotification('Insufficient stock', 'error');
            }
        },

        // Remove from cart
        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.calculateTotal();
        },

        // Calculate total
        calculateTotal() {
            this.subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            this.total = this.subtotal; // Add tax calculation if needed
        },

        // Process sale
        async processSale() {
            if (!this.canCheckout) {
                return;
            }
            
            this.isProcessing = true;
            
            try {
                const saleData = {
                    cart_items: this.cart.map(item => ({
                        id: item.id,
                        quantity: item.quantity,
                        price: item.price,
                        serial_number: item.serial_number
                    })),
                    payment_method: this.paymentMethod,
                    total_amount: this.total,
                    user_id: window.authUserId // This is already set in blade template
                };
                
                // Add customer details for credit sales
                if (this.paymentMethod === 'credit') {
                    if (this.customerMode === 'existing' && this.selectedCustomer) {
                        saleData.customer_details = {
                            id: this.selectedCustomer.id,
                            name: this.selectedCustomer.name,
                            phone: this.selectedCustomer.phone
                        };
                    } else {
                        saleData.customer_details = this.customerDetails;
                    }
                }
                
                let result;
                
                // Check if we're online or offline mode is disabled
                if (this.isOnline || !window.offlineModeEnabled) {
                    // Process sale online
                    const response = await fetch('/pos/process-sale', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(saleData)
                    });
                    
                    if (response.ok) {
                        result = await response.json();
                        this.receiptNumber = result.receipt_number;
                    } else {
                        throw new Error('Failed to process sale');
                    }
                } else {
                    // Process sale offline
                    if (window.offlinePOS && typeof window.offlinePOS.processSaleOffline === 'function') {
                    result = await window.offlinePOS.processSaleOffline(saleData);
                        this.receiptNumber = result.receipt_number;
                        
                        // Update sync status
                        this.checkSyncStatus();
                        
                        this.showNotification('Sale processed offline. Will sync when online.', 'info');
                    } else {
                        throw new Error('Offline mode not available');
                    }
                }
                
                // Show receipt
                this.showReceipt = true;
                
                // Reset cart
                this.resetCart();
                
            } catch (error) {
                console.error('Error processing sale:', error);
                this.showNotification('Failed to process sale. Please try again.', 'error');
            } finally {
                this.isProcessing = false;
            }
        },

        // Reset cart
        resetCart() {
            this.cart = [];
            this.paymentMethod = 'cash';
            this.customerDetails = { name: '', phone: '' };
            this.selectedCustomer = null;
            this.customerMode = 'existing';
            this.calculateTotal();
        },

        // Close receipt
        closeReceipt() {
            this.showReceipt = false;
            this.receiptNumber = '';
        },

        // Print receipt
        printReceipt() {
            window.print();
        },

        // Toggle categories
        toggleCategories() {
            this.showCategoryDrawer = !this.showCategoryDrawer;
        },

        // Toggle sync status
        toggleSyncStatus() {
            this.showSyncStatus = !this.showSyncStatus;
            if (this.showSyncStatus) {
                this.checkSyncStatus();
            }
        },

        // Get category name
        getCategoryName(categoryId) {
            const category = this.categories.find(c => c.id === categoryId);
            return category ? category.name : 'Unknown';
        },

        // Load customers
        async loadCustomers() {
            if (this.customersLoaded || this.loadingCustomers) {
                return;
            }
            
            this.loadingCustomers = true;
            
            try {
                const response = await fetch('/api/v1/customers', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.customers = data;
                    this.filteredCustomers = data;
                    this.customersLoaded = true;
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            } finally {
                this.loadingCustomers = false;
            }
        },

        // Search customers
        searchCustomers() {
            if (!this.customerSearch) {
                this.filteredCustomers = this.customers;
                return;
            }
            
            const query = this.customerSearch.toLowerCase();
            this.filteredCustomers = this.customers.filter(c => 
                c.name.toLowerCase().includes(query) ||
                c.phone.includes(query)
            );
        },

        // Select customer
        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.customerDetails = {
                name: customer.name,
                phone: customer.phone
            };
        },

        // Handle customer mode change
        handleCustomerModeChange(mode) {
            if (mode === 'new') {
                this.selectedCustomer = null;
                this.customerDetails = { name: '', phone: '' };
            }
        },

        // Refresh customer list
        async refreshCustomerList() {
            this.customersLoaded = false;
            await this.loadCustomers();
        },

        // Show notification
        showNotification(message, type = 'info') {
            // Create a toast notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'error' ? 'bg-red-500 text-white' : 
                type === 'success' ? 'bg-green-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        },

        // Computed properties
        get canCheckout() {
            if (this.cart.length === 0) return false;
            if (this.isProcessing) return false;
            
            if (this.paymentMethod === 'credit') {
                if (this.customerMode === 'existing') {
                    return this.selectedCustomer !== null;
                } else {
                    return this.customerDetails.name && this.customerDetails.phone;
                }
            }
            
            return true;
        }
    };
}
