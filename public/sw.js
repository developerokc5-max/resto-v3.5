// HawkerOps Service Worker v2
// Caches key pages for fast load + offline access

const CACHE_NAME = 'hawkerops-v2';

// Pages to cache immediately on install
const PRECACHE_URLS = [
  '/dashboard',
  '/platforms',
  '/alerts',
  '/items',
  '/stores',
  '/history',
  '/reports/daily-trends',
  '/reports/platform-reliability',
  '/reports/item-performance',
  '/reports/store-comparison',
  '/settings/scraper-status',
  '/settings/configuration',
  '/settings/export',
  '/offline',
];

// ── Install: pre-cache key pages ──────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      // Cache pages one by one — if one fails, others still cache
      return Promise.allSettled(
        PRECACHE_URLS.map(url =>
          cache.add(url).catch(() => console.log('[SW] Could not pre-cache:', url))
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// ── Activate: delete old caches, notify clients of update ─────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      )
    ).then(() => {
      self.clients.claim();
      // Notify all open tabs that a new version is active
      self.clients.matchAll({ type: 'window' }).then(clients => {
        clients.forEach(client => client.postMessage({ type: 'SW_UPDATED' }));
      });
    })
  );
});

// ── Fetch: network-first for HTML, cache-first for assets ─────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle same-origin requests
  if (url.origin !== location.origin) return;

  // Skip POST requests (sync buttons, form submissions)
  if (event.request.method !== 'GET') return;

  // Skip API calls — always fetch fresh
  if (url.pathname.startsWith('/api/')) return;

  // Skip export/download routes — always fetch fresh
  if (url.pathname.includes('/export') || url.pathname.includes('/logs/export')) return;

  // HTML pages: network-first (get fresh data), fall back to cache
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Cache the fresh response
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => {
          // Network failed — serve from cache
          return caches.match(event.request).then(cached => {
            if (cached) return cached;
            // No cache — show offline page
            return caches.match('/offline') || new Response(
              '<html><body style="font-family:sans-serif;text-align:center;padding:60px;background:#0f172a;color:#fff">' +
              '<h1>⚡ HawkerOps</h1><p>You are offline. Connect to see live data.</p></body></html>',
              { headers: { 'Content-Type': 'text/html' } }
            );
          });
        })
    );
    return;
  }

  // Static assets (CSS, JS, images): cache-first
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        if (response.ok) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      });
    })
  );
});
