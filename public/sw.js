// HawkerOps Service Worker
// HTML strategy: stale-while-revalidate
//   — Serve SW-cached page INSTANTLY on navigation (no waiting for server)
//   — Fetch fresh version in background and update cache silently
//   — smartReload() + /api/last-sync handles showing new data to the user
// Static assets: cache-first (Vite content-hashes guarantee freshness)

const CACHE_NAME = 'hawkerops-cache';
const LEGACY_CACHES = ['hawkerops-v6', 'hawkerops-v7', 'hawkerops-v8'];

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

// ── Activate: delete only known old versioned caches, keep current ─────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => LEGACY_CACHES.includes(key)).map(key => caches.delete(key))
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

  // Skip export/download routes (but not /settings/export which is an HTML page)
  if (url.pathname !== '/settings/export' && (url.pathname.startsWith('/export') || url.pathname.endsWith('/export') || url.pathname.includes('/logs/export'))) return;

  // Skip non-Vite JS — no content hash so cache-first would serve stale code after updates
  if (url.pathname.startsWith('/js/')) return;

  // Bypass SW cache for explicit no-store requests (e.g. smartReload JS fetch)
  // Without this, fetch(url, { cache: 'no-store' }) falls into cache-first and gets stale HTML
  if (event.request.cache === 'no-store') return;

  // ── HTML pages: stale-while-revalidate ────────────────────────────────────
  // Serve cached page INSTANTLY (sub-second LCP), fetch fresh in background.
  // smartReload() + visibilitychange handles showing new data to the user.
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith((async () => {
      const cache  = await caches.open(CACHE_NAME);
      const cached = await cache.match(event.request);

      // Always fetch fresh in background to keep cache up-to-date
      const fetchPromise = fetch(event.request.url).then(response => {
        if (response.ok && !response.redirected) {
          cache.put(event.request, response.clone());
        }
        return response;
      }).catch(() => null);

      // Return cached immediately if available; otherwise wait for network
      if (cached) return cached;

      // No cache yet (first visit) — wait for network, fall back to offline page
      const response = await fetchPromise;
      if (response) return response;
      return new Response(
        '<html><body style="font-family:sans-serif;text-align:center;padding:60px;background:#0f172a;color:#fff">' +
        '<h1>⚡ HawkerOps</h1><p>You are offline. Connect to see live data.</p></body></html>',
        { headers: { 'Content-Type': 'text/html' } }
      );
    })());
    return;
  }

  // ── Static assets (CSS, JS, images): cache-first ───────────────────────────
  // Vite content-hashes filenames so stale cache is never an issue
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
