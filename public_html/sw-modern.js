// Modern Service Worker with Advanced Caching Strategies
const CACHE_NAME = 'dishant-parihar-v2.0';
const RUNTIME_CACHE = 'runtime-cache-v1';
const IMAGE_CACHE = 'image-cache-v1';

// Files to cache on install
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/assets/css/modern-style.css',
    '/assets/js/modern-app.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://unpkg.com/aos@2.3.1/dist/aos.css',
    'https://unpkg.com/aos@2.3.1/dist/aos.js'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => {
                        return cacheName !== CACHE_NAME && 
                               cacheName !== RUNTIME_CACHE && 
                               cacheName !== IMAGE_CACHE;
                    })
                    .map(cacheName => {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Handle different types of requests with appropriate strategies
    if (request.destination === 'image') {
        // Images: Cache First, fallback to network
        event.respondWith(handleImageRequest(request));
    } else if (url.pathname.includes('/api/') || url.pathname.includes('ajax')) {
        // API calls: Network First, fallback to cache
        event.respondWith(handleApiRequest(request));
    } else if (request.mode === 'navigate') {
        // HTML pages: Network First with offline fallback
        event.respondWith(handleNavigationRequest(request));
    } else {
        // Other assets: Stale While Revalidate
        event.respondWith(handleAssetRequest(request));
    }
});

// Cache First strategy for images
async function handleImageRequest(request) {
    const cache = await caches.open(IMAGE_CACHE);
    const cached = await cache.match(request);
    
    if (cached) {
        // Return cached image and update in background
        fetchAndCache(request, IMAGE_CACHE);
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Return placeholder image if available
        return caches.match('/assets/img/placeholder.svg');
    }
}

// Network First strategy for API calls
async function handleApiRequest(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Fallback to cache for offline
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        // Return offline JSON response
        return new Response(
            JSON.stringify({ offline: true, message: 'You are offline' }),
            { headers: { 'Content-Type': 'application/json' } }
        );
    }
}

// Network First with offline page for navigation
async function handleNavigationRequest(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        // Return offline page
        return caches.match('/offline.html');
    }
}

// Stale While Revalidate for other assets
async function handleAssetRequest(request) {
    const cached = await caches.match(request);
    
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            const cache = caches.open(CACHE_NAME);
            cache.then(c => c.put(request, response.clone()));
        }
        return response;
    }).catch(() => cached);
    
    return cached || fetchPromise;
}

// Helper function to fetch and cache in background
async function fetchAndCache(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response);
        }
    } catch (error) {
        console.log('Background fetch failed:', error);
    }
}

// Handle background sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'sync-leads') {
        event.waitUntil(syncLeads());
    }
});

async function syncLeads() {
    // Get pending leads from IndexedDB
    const pendingLeads = await getPendingLeads();
    
    for (const lead of pendingLeads) {
        try {
            const response = await fetch('/api/leads', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lead)
            });
            
            if (response.ok) {
                await removePendingLead(lead.id);
            }
        } catch (error) {
            console.log('Failed to sync lead:', error);
        }
    }
}

// Push notifications
self.addEventListener('push', event => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/assets/img/icon-192.png',
        badge: '/assets/img/badge-72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/'
        },
        actions: [
            {
                action: 'view',
                title: 'View'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Dishant Parihar Team', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'view' || !event.action) {
        const url = event.notification.data.url;
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true })
                .then(clientList => {
                    // Check if there's already a window open
                    for (const client of clientList) {
                        if (client.url === url && 'focus' in client) {
                            return client.focus();
                        }
                    }
                    // Open new window if needed
                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
        );
    }
});

// Periodic background sync for regular updates
self.addEventListener('periodicsync', event => {
    if (event.tag === 'update-content') {
        event.waitUntil(updateContent());
    }
});

async function updateContent() {
    // Pre-cache important pages
    const cache = await caches.open(CACHE_NAME);
    const urlsToCache = [
        '/user/dashboard.php',
        '/user/learning.php',
        '/user/crm.php'
    ];
    
    try {
        await cache.addAll(urlsToCache);
        console.log('Content updated successfully');
    } catch (error) {
        console.log('Failed to update content:', error);
    }
}

// Message handling for skip waiting
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Placeholder functions for IndexedDB operations
async function getPendingLeads() {
    // Implementation would use IndexedDB
    return [];
}

async function removePendingLead(id) {
    // Implementation would use IndexedDB
    return true;
}