/**
 * Offline POS Manager
 * Handles offline sales processing, local storage, and synchronization
 */

class OfflinePOSManager {
    constructor() {
        this.dbName = 'eldogas_pos_offline';
        this.dbVersion = 1;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this.syncQueue = [];
        
        // Initialize the system
        this.init();
        this.setupEventListeners();
    }

    /**
     * Initialize the offline POS system
     */
    async init() {
        try {
            await this.initIndexedDB();
            await this.loadOfflineData();
            this.updateConnectionStatus();
            
            // Start background sync if online
            if (this.isOnline) {
                this.startBackgroundSync();
            }
            
            console.log('Offline POS Manager initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Offline POS Manager:', error);
        }
    }

    /**
     * Initialize IndexedDB for local storage
     */
    async initIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create offline_sales store
                if (!db.objectStoreNames.contains('offline_sales')) {
                    const salesStore = db.createObjectStore('offline_sales', { 
                        keyPath: 'local_id', 
                        autoIncrement: true 
                    });
                    salesStore.createIndex('receipt_number', 'receipt_number', { unique: true });
                    salesStore.createIndex('sync_status', 'sync_status', { unique: false });
                    salesStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Create offline_products store (for inventory management)
                if (!db.objectStoreNames.contains('offline_products')) {
                    const productsStore = db.createObjectStore('offline_products', { 
                        keyPath: 'id' 
                    });
                    productsStore.createIndex('sku', 'sku', { unique: false });
                    productsStore.createIndex('category_id', 'category_id', { unique: false });
                }

                // Create offline_customers store
                if (!db.objectStoreNames.contains('offline_customers')) {
                    const customersStore = db.createObjectStore('offline_customers', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    customersStore.createIndex('phone', 'phone', { unique: false });
                }

                // Create sync_queue store
                if (!db.objectStoreNames.contains('sync_queue')) {
                    const syncStore = db.createObjectStore('sync_queue', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    syncStore.createIndex('operation_type', 'operation_type', { unique: false });
                    syncStore.createIndex('priority', 'priority', { unique: false });
                }

                // Create app_state store for storing app configuration
                if (!db.objectStoreNames.contains('app_state')) {
                    db.createObjectStore('app_state', { keyPath: 'key' });
                }
            };
        });
    }

    /**
     * Setup event listeners for online/offline detection
     */
    setupEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateConnectionStatus();
            this.handleConnectionRestored();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateConnectionStatus();
            this.handleConnectionLost();
        });

        // Setup periodic sync check
        setInterval(() => {
            if (this.isOnline && !this.syncInProgress) {
                this.checkAndSync();
            }
        }, 30000); // Check every 30 seconds
    }

    /**
     * Update connection status in the UI
     */
    updateConnectionStatus() {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.className = this.isOnline 
                ? 'connection-status online' 
                : 'connection-status offline';
            statusElement.textContent = this.isOnline ? 'Online' : 'Offline';
        }

        // Dispatch custom event for Alpine.js components
        window.dispatchEvent(new CustomEvent('connection-status-changed', {
            detail: { isOnline: this.isOnline }
        }));
    }

    /**
     * Handle connection restored
     */
    async handleConnectionRestored() {
        console.log('Connection restored - starting sync');
        await this.startBackgroundSync();
        
        // Show notification
        this.showNotification('Connection restored. Syncing data...', 'success');
    }

    /**
     * Handle connection lost
     */
    handleConnectionLost() {
        console.log('Connection lost - switching to offline mode');
        this.showNotification('Connection lost. Working offline.', 'warning');
    }

    /**
     * Process sale offline
     */
    async processSaleOffline(saleData) {
        try {
            // Generate local receipt number
            const receiptNumber = await this.generateOfflineReceiptNumber();
            
            // Prepare sale record
            const offlineSale = {
                receipt_number: receiptNumber,
                user_id: saleData.user_id,
                customer_data: saleData.customer_details || null,
                total_amount: saleData.total_amount,
                payment_method: saleData.payment_method,
                payment_status: saleData.payment_method === 'cash' ? 'paid' : 'pending',
                status: 'completed',
                items: saleData.cart_items,
                sync_status: 'pending',
                created_at: new Date().toISOString(),
                offline_created: true
            };

            // Store sale locally
            const localId = await this.storeSaleLocally(offlineSale);
            
            // Update local product stock
            await this.updateLocalStock(saleData.cart_items);
            
            // Add to sync queue
            await this.addToSyncQueue({
                operation_type: 'create_sale',
                data: offlineSale,
                local_id: localId,
                priority: 1
            });

            console.log('Sale processed offline successfully:', receiptNumber);
            
            return {
                success: true,
                receipt_number: receiptNumber,
                local_id: localId,
                offline_mode: true
            };

        } catch (error) {
            console.error('Error processing offline sale:', error);
            throw new Error('Failed to process offline sale: ' + error.message);
        }
    }

    /**
     * Store sale in local database
     */
    async storeSaleLocally(saleData) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offline_sales'], 'readwrite');
            const store = transaction.objectStore('offline_sales');
            
            const request = store.add(saleData);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Update local product stock
     */
    async updateLocalStock(cartItems) {
        const transaction = this.db.transaction(['offline_products'], 'readwrite');
        const store = transaction.objectStore('offline_products');

        for (const item of cartItems) {
            try {
                const product = await this.getLocalProduct(item.id);
                if (product && product.stock >= item.quantity) {
                    product.stock -= item.quantity;
                    await this.updateLocalProduct(product);
                }
            } catch (error) {
                console.warn(`Failed to update stock for product ${item.id}:`, error);
            }
        }
    }

    /**
     * Get local product data
     */
    async getLocalProduct(productId) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offline_products'], 'readonly');
            const store = transaction.objectStore('offline_products');
            const request = store.get(productId);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Update local product
     */
    async updateLocalProduct(product) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offline_products'], 'readwrite');
            const store = transaction.objectStore('offline_products');
            const request = store.put(product);
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generate offline receipt number
     */
    async generateOfflineReceiptNumber() {
        const date = new Date();
        const dateStr = date.toISOString().slice(0, 10).replace(/-/g, '');
        const timeStr = Date.now().toString().slice(-6);
        return `OFF-${dateStr}-${timeStr}`;
    }

    /**
     * Add operation to sync queue
     */
    async addToSyncQueue(operation) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['sync_queue'], 'readwrite');
            const store = transaction.objectStore('sync_queue');
            
            const queueItem = {
                ...operation,
                created_at: new Date().toISOString(),
                attempts: 0,
                max_attempts: 3
            };
            
            const request = store.add(queueItem);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Start background synchronization
     */
    async startBackgroundSync() {
        if (this.syncInProgress || !this.isOnline) {
            return;
        }

        this.syncInProgress = true;
        
        try {
            await this.syncPendingOperations();
        } catch (error) {
            console.error('Background sync failed:', error);
        } finally {
            this.syncInProgress = false;
        }
    }

    /**
     * Sync pending operations
     */
    async syncPendingOperations() {
        const pendingOperations = await this.getPendingOperations();
        
        if (pendingOperations.length === 0) {
            return;
        }

        console.log(`Syncing ${pendingOperations.length} pending operations`);
        
        for (const operation of pendingOperations) {
            try {
                await this.syncOperation(operation);
                await this.markOperationSynced(operation.id);
                
                // Small delay between operations
                await new Promise(resolve => setTimeout(resolve, 100));
                
            } catch (error) {
                console.error('Failed to sync operation:', operation, error);
                await this.handleSyncError(operation, error);
            }
        }
        
        this.showNotification('Data synchronized successfully', 'success');
    }

    /**
     * Get pending sync operations
     */
    async getPendingOperations() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['sync_queue'], 'readonly');
            const store = transaction.objectStore('sync_queue');
            const request = store.getAll();
            
            request.onsuccess = () => {
                const operations = request.result.filter(op => op.attempts < op.max_attempts);
                resolve(operations.sort((a, b) => a.priority - b.priority));
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Sync individual operation
     */
    async syncOperation(operation) {
        switch (operation.operation_type) {
            case 'create_sale':
                return await this.syncSaleToServer(operation);
            case 'update_product_stock':
                return await this.syncStockToServer(operation);
            case 'create_customer':
                return await this.syncCustomerToServer(operation);
            default:
                throw new Error(`Unknown operation type: ${operation.operation_type}`);
        }
    }

    /**
     * Sync sale to server
     */
    async syncSaleToServer(operation) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const response = await fetch('/api/v1/offline/sync-sale', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Offline-Sync': 'true'
            },
            body: JSON.stringify({
                cart_items: operation.data.items,
                payment_method: operation.data.payment_method,
                customer_details: operation.data.customer_data,
                offline_receipt_number: operation.data.receipt_number,
                offline_created_at: operation.data.created_at
            })
        });

        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Server rejected the sale');
        }

        // Update local sale with server ID
        await this.updateLocalSaleWithServerId(operation.local_id, result.sale_id);
        
        return result;
    }

    /**
     * Update local sale with server ID
     */
    async updateLocalSaleWithServerId(localId, serverId) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offline_sales'], 'readwrite');
            const store = transaction.objectStore('offline_sales');
            
            const getRequest = store.get(localId);
            getRequest.onsuccess = () => {
                const sale = getRequest.result;
                if (sale) {
                    sale.server_id = serverId;
                    sale.sync_status = 'synced';
                    sale.synced_at = new Date().toISOString();
                    
                    const putRequest = store.put(sale);
                    putRequest.onsuccess = () => resolve();
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    reject(new Error('Sale not found'));
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Mark operation as synced
     */
    async markOperationSynced(operationId) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['sync_queue'], 'readwrite');
            const store = transaction.objectStore('sync_queue');
            const request = store.delete(operationId);
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Handle sync error
     */
    async handleSyncError(operation, error) {
        operation.attempts += 1;
        operation.last_error = error.message;
        operation.last_attempt = new Date().toISOString();

        if (operation.attempts >= operation.max_attempts) {
            // Move to failed operations or handle differently
            console.error('Operation failed after max attempts:', operation);
            return;
        }

        // Update the operation in the queue
        const transaction = this.db.transaction(['sync_queue'], 'readwrite');
        const store = transaction.objectStore('sync_queue');
        store.put(operation);
    }

    /**
     * Load offline data into memory
     */
    async loadOfflineData() {
        try {
            // Load products for offline use
            await this.loadProductsOffline();
            
            // Load recent sales for reference
            await this.loadRecentSalesOffline();
            
        } catch (error) {
            console.error('Error loading offline data:', error);
        }
    }

    /**
     * Load products for offline use
     */
    async loadProductsOffline() {
        if (!this.isOnline) {
            return; // Skip if offline during initialization
        }

        try {
            const response = await fetch('/api/v1/offline/products', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (response.ok) {
                const products = await response.json();
                await this.storeProductsLocally(products);
            }
        } catch (error) {
            console.warn('Failed to load products for offline use:', error);
        }
    }

    /**
     * Store products locally
     */
    async storeProductsLocally(products) {
        const transaction = this.db.transaction(['offline_products'], 'readwrite');
        const store = transaction.objectStore('offline_products');

        for (const product of products) {
            try {
                await new Promise((resolve, reject) => {
                    const request = store.put(product);
                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                });
            } catch (error) {
                console.warn('Failed to store product locally:', product.id, error);
            }
        }
    }

    /**
     * Check and sync data
     */
    async checkAndSync() {
        if (!this.isOnline || this.syncInProgress) {
            return;
        }

        const pendingCount = await this.getPendingOperationsCount();
        if (pendingCount > 0) {
            await this.startBackgroundSync();
        }
    }

    /**
     * Get pending operations count
     */
    async getPendingOperationsCount() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['sync_queue'], 'readonly');
            const store = transaction.objectStore('sync_queue');
            const request = store.count();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Create or update notification element
        let notification = document.getElementById('offline-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'offline-notification';
            notification.className = 'offline-notification';
            document.body.appendChild(notification);
        }

        notification.className = `offline-notification ${type}`;
        notification.textContent = message;
        notification.style.display = 'block';

        // Auto-hide after 3 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    /**
     * Get offline sales summary
     */
    async getOfflineSalesSummary() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offline_sales'], 'readonly');
            const store = transaction.objectStore('offline_sales');
            const request = store.getAll();
            
            request.onsuccess = () => {
                const sales = request.result;
                const summary = {
                    total_sales: sales.length,
                    pending_sync: sales.filter(s => s.sync_status === 'pending').length,
                    synced: sales.filter(s => s.sync_status === 'synced').length,
                    total_amount: sales.reduce((sum, sale) => sum + sale.total_amount, 0)
                };
                resolve(summary);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear synced offline data (cleanup)
     */
    async clearSyncedData(olderThanDays = 7) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - olderThanDays);
        
        const transaction = this.db.transaction(['offline_sales'], 'readwrite');
        const store = transaction.objectStore('offline_sales');
        const index = store.index('created_at');
        
        const request = index.openCursor(IDBKeyRange.upperBound(cutoffDate.toISOString()));
        
        request.onsuccess = (event) => {
            const cursor = event.target.result;
            if (cursor) {
                if (cursor.value.sync_status === 'synced') {
                    cursor.delete();
                }
                cursor.continue();
            }
        };
    }

    async loadRecentSalesOffline() {
        // Placeholder for loading recent sales if needed
        console.log('Loading recent sales for offline reference...');
    }
}

// Export for use in other modules
window.OfflinePOSManager = OfflinePOSManager;