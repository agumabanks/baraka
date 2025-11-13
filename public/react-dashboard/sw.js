const CACHE_NAME = 'baraka-scanner-v1.0.0';
const STATIC_CACHE = 'baraka-static-v1.0.0';
const DYNAMIC_CACHE = 'baraka-dynamic-v1.0.0';

// Assets to cache immediately
const STATIC_ASSETS = [
  '/',
  '/mobile',
  '/static/js/bundle.js',
  '/static/css/main.css',
  '/manifest.json',
  '/icon-192x192.png',
  '/icon-512x512.png',
  '/offline.html'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
  /\/api\/v1\/mobile\/scan/,
  /\/api\/v1\/mobile\/bulk-scan/,
  /\/api\/v1\/mobile\/device-info/,
  /\/api\/v1\/devices\/authenticate/
];

// Background sync tag
const BACKGROUND_SYNC_TAG = 'scan-sync';

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('Service Worker: Static assets cached');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('Service Worker: Failed to cache static assets', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => {
              return cacheName !== STATIC_CACHE && 
                     cacheName !== DYNAMIC_CACHE &&
                     cacheName.startsWith('baraka-');
            })
            .map((cacheName) => {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('Service Worker: Activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - handle requests
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Handle different types of requests
  if (request.method === 'GET') {
    // Static assets - Cache First strategy
    if (isStaticAsset(url)) {
      event.respondWith(
        caches.match(request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            
            return fetch(request)
              .then((response) => {
                // Clone and store in cache
                const responseClone = response.clone();
                caches.open(STATIC_CACHE)
                  .then((cache) => cache.put(request, responseClone));
                return response;
              })
              .catch(() => {
                // Return offline fallback for static assets
                if (request.destination === 'document') {
                  return caches.match('/offline.html');
                }
                return new Response('Offline', { status: 503 });
              });
          })
      );
    }
    // API requests - Network First with cache fallback
    else if (isApiRequest(url)) {
      event.respondWith(
        networkFirstStrategy(request)
      );
    }
    // Other requests - Network First strategy
    else {
      event.respondWith(
        networkFirstStrategy(request)
      );
    }
  }
  // POST requests (for scan submissions)
  else if (request.method === 'POST' && isMobileApi(url)) {
    event.respondWith(
      handlePostRequest(request)
    );
  }
});

// Network First strategy for API requests
async function networkFirstStrategy(request) {
  try {
    // Try network first
    const networkResponse = await fetch(request);
    
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Network failed, trying cache', request.url);
    
    // Network failed, try cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline response for API requests
    if (isApiRequest(new URL(request.url))) {
      return new Response(
        JSON.stringify({ 
          success: false, 
          error: 'Offline - request queued for sync',
          offline: true 
        }),
        {
          status: 503,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
    
    // Return offline page for other requests
    return caches.match('/offline.html') || 
           new Response('Offline', { status: 503 });
  }
}

// Handle POST requests (scan submissions)
async function handlePostRequest(request) {
  try {
    // Try to send request to network
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: POST request failed, storing for sync', request.url);
    
    // Network failed, store request for background sync
    try {
      const requestData = await request.clone().json();
      await storeRequestForSync(request.url, requestData);
      
      // Register background sync
      if ('sync' in self.registration) {
        await self.registration.sync.register(BACKGROUND_SYNC_TAG);
      }
      
      // Return success response indicating offline queue
      return new Response(
        JSON.stringify({
          success: true,
          offline: true,
          message: 'Request stored for sync when online'
        }),
        {
          status: 202,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    } catch (storageError) {
      console.error('Service Worker: Failed to store request for sync', storageError);
      return new Response(
        JSON.stringify({
          success: false,
          error: 'Failed to store request offline'
        }),
        {
          status: 500,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
  }
}

// Background sync event
self.addEventListener('sync', (event) => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === BACKGROUND_SYNC_TAG) {
    event.waitUntil(
      syncStoredRequests()
    );
  }
});

// Sync stored requests
async function syncStoredRequests() {
  try {
    const requests = await getStoredRequests();
    console.log('Service Worker: Syncing', requests.length, 'requests');
    
    for (const requestData of requests) {
      try {
        const response = await fetch(requestData.url, {
          method: requestData.method,
          headers: requestData.headers,
          body: JSON.stringify(requestData.data)
        });
        
        if (response.ok) {
          // Remove successfully synced request
          await removeStoredRequest(requestData.id);
          console.log('Service Worker: Successfully synced request', requestData.id);
        } else {
          console.error('Service Worker: Sync failed for request', requestData.id, response.status);
        }
      } catch (error) {
        console.error('Service Worker: Error syncing request', requestData.id, error);
      }
    }
  } catch (error) {
    console.error('Service Worker: Error during sync', error);
  }
}

// Helper functions
function isStaticAsset(url) {
  return url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/);
}

function isApiRequest(url) {
  return url.pathname.startsWith('/api/');
}

function isMobileApi(url) {
  return url.pathname.startsWith('/api/v1/mobile/');
}

async function storeRequestForSync(url, data) {
  // Use IndexedDB for storing requests
  const db = await openDB();
  const transaction = db.transaction(['sync_requests'], 'readwrite');
  const store = transaction.objectStore('sync_requests');
  
  const request = {
    id: Date.now() + Math.random().toString(36).substr(2, 9),
    url,
    method: 'POST',
    data,
    headers: {
      'Content-Type': 'application/json'
    },
    timestamp: Date.now()
  };
  
  await store.add(request);
  return request.id;
}

async function getStoredRequests() {
  const db = await openDB();
  const transaction = db.transaction(['sync_requests'], 'readonly');
  const store = transaction.objectStore('sync_requests');
  
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function removeStoredRequest(id) {
  const db = await openDB();
  const transaction = db.transaction(['sync_requests'], 'readwrite');
  const store = transaction.objectStore('sync_requests');
  
  await store.delete(id);
}

// IndexedDB helper
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('BarakaScannerDB', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      if (!db.objectStoreNames.contains('sync_requests')) {
        const store = db.createObjectStore('sync_requests', { keyPath: 'id' });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
      
      if (!db.objectStoreNames.contains('scan_history')) {
        const store = db.createObjectStore('scan_history', { keyPath: 'id' });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
      
      if (!db.objectStoreNames.contains('device_info')) {
        db.createObjectStore('device_info', { keyPath: 'id' });
      }
    };
  });
}

// Push notification event
self.addEventListener('push', (event) => {
  console.log('Service Worker: Push received', event);
  
  const options = {
    body: event.data ? event.data.text() : 'New scan event received',
    icon: '/icon-192x192.png',
    badge: '/badge-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'view',
        title: 'View Details',
        icon: '/action-view.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/action-dismiss.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('Baraka Scanner', options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Notification clicked', event);
  
  event.notification.close();
  
  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow('/mobile')
    );
  } else if (event.action === 'dismiss') {
    // Just close the notification
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.openWindow('/mobile')
    );
  }
});

// Message event for communication with main thread
self.addEventListener('message', (event) => {
  console.log('Service Worker: Message received', event.data);
  
  const { type, data } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
    case 'CACHE_SCAN_DATA':
      // Cache scan data for offline access
      cacheScanData(data);
      break;
    case 'GET_SCAN_HISTORY':
      // Return cached scan history
      getScanHistory().then(history => {
        event.ports[0].postMessage({ type: 'SCAN_HISTORY', data: history });
      });
      break;
  }
});

// Cache scan data
async function cacheScanData(scanData) {
  try {
    const db = await openDB();
    const transaction = db.transaction(['scan_history'], 'readwrite');
    const store = transaction.objectStore('scan_history');
    
    const scan = {
      id: scanData.offline_sync_key || Date.now().toString(),
      ...scanData,
      cached_at: Date.now()
    };
    
    await store.add(scan);
    console.log('Service Worker: Scan data cached', scan.id);
  } catch (error) {
    console.error('Service Worker: Failed to cache scan data', error);
  }
}

// Get scan history
async function getScanHistory() {
  try {
    const db = await openDB();
    const transaction = db.transaction(['scan_history'], 'readonly');
    const store = transaction.objectStore('scan_history');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.error('Service Worker: Failed to get scan history', error);
    return [];
  }
}

console.log('Service Worker: Loaded');