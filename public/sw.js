// HawkerOps Service Worker v3
// Stale-while-revalidate for HTML: instant on mobile, always fresh in background

const CACHE_NAME = 'hawkerops-v4';

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

  // Skip export/download routes
  if (url.pathname.includes('/export') || url.pathname.includes('/logs/export')) return;

  // HTML pages: stale-while-revalidate
  // — Serve cached version instantly (fast on mobile)
  // — Fetch fresh in background and update cache for next visit
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);
      const cached = await cache.match(event.request);

      // Kick off network fetch in background regardless
      const networkPromise = fetch(event.request)
        .then(response => {
          if (response.ok) cache.put(event.request, response.clone());
          return response;
        })
        .catch(() => null);

      // If cached, return instantly — network updates cache in background
      if (cached) {
        networkPromise.catch(() => {});
        return cached;
      }

      // No cache yet — wait for network
      const response = await networkPromise;
      if (response) return response;

      // Network failed, no cache — show offline page
      return cache.match('/offline') || new Response(
        '<html><body style="font-family:sans-serif;text-align:center;padding:60px;background:#0f172a;color:#fff">' +
        '<h1>⚡ HawkerOps</h1><p>You are offline. Connect to see live data.</p></body></html>',
        { headers: { 'Content-Type': 'text/html' } }
      );
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
