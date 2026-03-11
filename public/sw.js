// HawkerOps Service Worker v5
// Network-first for HTML: always fetch fresh, fall back to cache only if offline

const CACHE_NAME = 'hawkerops-v7';

// Only precache the offline fallback — everything else is cached on first visit
const PRECACHE_URLS = ['/offline'];

// ── Install: pre-cache offline fallback only ───────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
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
      self.clients.matchAll({ type: 'window' }).then(clients => {
        clients.forEach(client => client.postMessage({ type: 'SW_UPDATED' }));
      });
    })
  );
});

// ── Fetch ──────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle same-origin requests
  if (url.origin !== location.origin) return;

  // Skip POST requests
  if (event.request.method !== 'GET') return;

  // Skip API calls — always fetch fresh
  if (url.pathname.startsWith('/api/')) return;

  // Never cache auth/login pages — flash messages must always render fresh
  if (url.pathname === '/login' || url.pathname.startsWith('/auth/')) return;

  // Skip root URL — it's a redirect to /dashboard, let browser handle natively
  if (url.pathname === '/') return;

  // Skip export/download routes
  if (url.pathname.includes('/export') || url.pathname.includes('/logs/export')) return;

  // HTML pages: network-first
  // — Always fetch fresh HTML from server (live data dashboard needs it)
  // — Cache the fresh response for offline fallback only
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);
      try {
        // Always try network first — use URL string to avoid navigate-mode issues
        const response = await fetch(event.request.url);
        // Cache non-redirected OK responses as offline fallback
        if (response.ok && !response.redirected) cache.put(event.request, response.clone());
        return response;
      } catch (_) {
        // Network failed — serve from cache if available
        const cached = await cache.match(event.request);
        if (cached) return cached;
        // No cache — show offline page
        return cache.match('/offline') || new Response(
          '<html><body style="font-family:sans-serif;text-align:center;padding:60px;background:#0f172a;color:#fff">' +
          '<h1>⚡ HawkerOps</h1><p>You are offline. Connect to see live data.</p></body></html>',
          { headers: { 'Content-Type': 'text/html' } }
        );
      }
    })());
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
