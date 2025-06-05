// Service Worker for Offline POS Functionality
const CACHE_NAME = 'eldogas-pos-v1';
const STATIC_CACHE_URLS = [
    '/',
    '/pos/dashboard',
    '/resources/css/app.css',
    '/resources/js/app.js',
    '/resources/js/offline/OfflinePOSManager.js',
    '/images/placeholder.jpg'
];

// Install event - cache static resources
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching static resources');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .catch(error => {
                console.error('Service Worker: Failed to cache static resources', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
    );
});

// Fetch event - handle requests
self.addEventListener('fetch', event => {
    const { request } = event;
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle different types of requests
    if (request.url.includes('/api/v1/offline/')) {
        // API requests - cache with network first strategy
        event.respondWith(handleApiRequest(request));
    } else if (request.url.includes('/pos/') || request.url.includes('/dashboard')) {
        // POS pages - cache with cache first strategy
        event.respondWith(handlePageRequest(request));
    } else if (request.url.includes('/storage/') || request.url.includes('/images/')) {
        // Images - cache first strategy
        event.respondWith(handleImageRequest(request));
    } else {
        // Other requests - network first
        event.respondWith(handleNetworkFirst(request));
    }
});

// Handle API requests (network first, cache fallback)
async function handleApiRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful responses
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Network failed for API request, trying cache');
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return a custom offline response for API requests
        return new Response(
            JSON.stringify({
                success: false,
                message: 'Network unavailable. Please try again when online.',
                offline: true
            }),
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: {
                    'Content-Type': 'application/json'
                }
            }
        );
    }
}

// Handle page requests (cache first)
async function handlePageRequest(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Failed to fetch page', request.url);
        return new Response(
            `<!DOCTYPE html>
            <html>
            <head>
                <title>Offline - EldoGas POS</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 50px;
                        background-color: #f5f5f5;
                    }
                    .offline-message {
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        max-width: 500px;
                        margin: 0 auto;
                    }
                    .offline-icon {
                        font-size: 64px;
                        color: #ff6b35;
                        margin-bottom: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="offline-message">
                    <div class="offline-icon">📡</div>
                    <h1>You're Offline</h1>
                    <p>Please check your internet connection and try again.</p>
                    <button onclick="window.location.reload()" style="background: #ff6b35; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            </body>
            </html>`,
            {
                status: 200,
                statusText: 'OK',
                headers: {
                    'Content-Type': 'text/html'
                }
            }
        );
    }
}

// Handle image requests (cache first)
async function handleImageRequest(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Return placeholder image for failed image requests
        return new Response(
            // Simple SVG placeholder
            `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" fill="#f0f0f0"/>
                <text x="100" y="100" text-anchor="middle" dy="0.3em" font-family="Arial" font-size="14" fill="#999">
                    Image Unavailable
                </text>
            </svg>`,
            {
                status: 200,
                statusText: 'OK',
                headers: {
                    'Content-Type': 'image/svg+xml'
                }
            }
        );
    }
}

// Handle other requests (network first)
async function handleNetworkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Offline', { status: 503 });
    }
}

// Handle background sync for offline sales
self.addEventListener('sync', event => {
    if (event.tag === 'offline-sales-sync') {
        console.log('Service Worker: Background sync triggered for offline sales');
        event.waitUntil(syncOfflineSales());
    }
});

// Sync offline sales when connection is restored
async function syncOfflineSales() {
    try {
        // This would typically communicate with the IndexedDB
        // through the main thread via postMessage
        console.log('Service Worker: Attempting to sync offline sales');
        
        // Send message to main thread to trigger sync
        const clients = await self.clients.matchAll();
        clients.forEach(client => {
            client.postMessage({
                type: 'SYNC_OFFLINE_SALES',
                timestamp: Date.now()
            });
        });
    } catch (error) {
        console.error('Service Worker: Failed to sync offline sales', error);
    }
}

// Handle push notifications (for future use)
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        
        const options = {
            body: data.body || 'You have a new notification',
            icon: '/images/icon-192x192.png',
            badge: '/images/badge-72x72.png',
            vibrate: [200, 100, 200],
            data: data.data || {},
            actions: data.actions || []
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title || 'EldoGas POS', options)
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/pos/dashboard')
    );
});