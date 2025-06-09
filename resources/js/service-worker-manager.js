// Service Worker Registration and Management
class ServiceWorkerManager {
    constructor() {
        this.isSupported = 'serviceWorker' in navigator;
        this.registration = null;
        this.init();
    }

    async init() {
        if (!this.isSupported) {
            console.warn('Service Workers are not supported in this browser');
            return;
        }

        try {
            await this.registerServiceWorker();
            this.setupEventListeners();
            console.log('Service Worker Manager initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Service Worker Manager:', error);
        }
    }

    async registerServiceWorker() {
        try {
            // Get the base URL for the application
            const baseUrl = window.location.origin + (window.location.pathname.includes('/public') ? window.location.pathname.split('/public')[0] + '/public' : '');
            
            this.registration = await navigator.serviceWorker.register(baseUrl + '/sw.js', {
                scope: '/'
            });

            console.log('Service Worker registered successfully:', this.registration);

            // Check for updates
            this.registration.addEventListener('updatefound', () => {
                console.log('Service Worker update found');
                this.handleServiceWorkerUpdate();
            });

            // Check if there's already a waiting service worker
            if (this.registration.waiting) {
                this.showUpdateAvailable();
            }

        } catch (error) {
            console.error('Service Worker registration failed:', error);
        }
    }

    setupEventListeners() {
        // Listen for messages from the service worker
        navigator.serviceWorker.addEventListener('message', (event) => {
            this.handleServiceWorkerMessage(event);
        });

        // Listen for service worker state changes
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('Service Worker controller changed');
            // Reload the page to ensure the new service worker takes control
            window.location.reload();
        });
    }

    handleServiceWorkerMessage(event) {
        const { type, timestamp } = event.data;

        switch (type) {
            case 'SYNC_OFFLINE_SALES':
                console.log('Service Worker requested offline sales sync');
                this.triggerOfflineSync();
                break;
            default:
                console.log('Unknown message from service worker:', event.data);
        }
    }

    async triggerOfflineSync() {
        // Trigger sync through the offline POS manager
        if (window.offlinePOS && window.offlinePOS.startBackgroundSync) {
            try {
                await window.offlinePOS.startBackgroundSync();
                console.log('Background sync triggered by service worker');
            } catch (error) {
                console.error('Failed to trigger background sync:', error);
            }
        }
    }

    handleServiceWorkerUpdate() {
        const newWorker = this.registration.installing;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed') {
                if (navigator.serviceWorker.controller) {
                    // New update available
                    this.showUpdateAvailable();
                } else {
                    // First install
                    console.log('Service Worker installed for the first time');
                }
            }
        });
    }

    showUpdateAvailable() {
        // Show update notification to user
        const updateMessage = 'A new version of the app is available. Refresh to update?';
        
        if (confirm(updateMessage)) {
            this.skipWaiting();
        } else {
            // Show a persistent notification
            this.showUpdateBanner();
        }
    }

    showUpdateBanner() {
        // Create update banner
        const banner = document.createElement('div');
        banner.id = 'update-banner';
        banner.className = 'fixed top-0 left-0 right-0 bg-blue-600 text-white p-3 text-center z-50';
        banner.innerHTML = `
            <div class="flex items-center justify-between max-w-4xl mx-auto">
                <span>A new version is available!</span>
                <div class="space-x-2">
                    <button onclick="serviceWorkerManager.skipWaiting()" class="bg-white text-blue-600 px-3 py-1 rounded text-sm">
                        Update Now
                    </button>
                    <button onclick="document.getElementById('update-banner').remove()" class="text-blue-200 hover:text-white">
                        ×
                    </button>
                </div>
            </div>
        `;

        // Remove existing banner
        const existingBanner = document.getElementById('update-banner');
        if (existingBanner) {
            existingBanner.remove();
        }

        document.body.prepend(banner);
    }

    skipWaiting() {
        if (this.registration && this.registration.waiting) {
            this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
    }

    async unregister() {
        if (this.registration) {
            const result = await this.registration.unregister();
            console.log('Service Worker unregistered:', result);
            return result;
        }
        return false;
    }

    // Request background sync (for offline sales)
    async requestBackgroundSync() {
        if (this.registration && this.registration.sync) {
            try {
                await this.registration.sync.register('offline-sales-sync');
                console.log('Background sync registered');
            } catch (error) {
                console.error('Background sync registration failed:', error);
            }
        }
    }

    // Get registration info
    getRegistrationInfo() {
        if (!this.registration) {
            return null;
        }

        return {
            scope: this.registration.scope,
            active: !!this.registration.active,
            installing: !!this.registration.installing,
            waiting: !!this.registration.waiting,
            updateViaCache: this.registration.updateViaCache
        };
    }
}

// Initialize the service worker manager
let serviceWorkerManager;

// Only initialize if offline mode is enabled
if (window.offlineModeEnabled) {
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            serviceWorkerManager = new ServiceWorkerManager();
            window.serviceWorkerManager = serviceWorkerManager;
        });
    } else {
        serviceWorkerManager = new ServiceWorkerManager();
        window.serviceWorkerManager = serviceWorkerManager;
    }
}

// Export for global access
window.ServiceWorkerManager = ServiceWorkerManager;