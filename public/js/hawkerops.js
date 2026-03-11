// Dark mode toggle
function toggleDarkMode() {
  const html = document.documentElement;
  const isDark = html.classList.toggle('dark');
  localStorage.setItem('darkMode', isDark);
  const icon = isDark ? '☀️' : '🌙';
  const di = document.getElementById('darkIcon');
  const mdi = document.getElementById('mobileDarkIcon');
  if (di) di.textContent = icon;
  if (mdi) mdi.textContent = icon;
}

// Set correct icon on load
document.addEventListener('DOMContentLoaded', function() {
  const isDark = localStorage.getItem('darkMode') === 'true';
  const icon = isDark ? '☀️' : '🌙';
  const di = document.getElementById('darkIcon');
  const mdi = document.getElementById('mobileDarkIcon');
  if (di) di.textContent = icon;
  if (mdi) mdi.textContent = icon;
});

async function triggerSync() {
  const path = window.location.pathname;
  const isSyncPage = path === '/platforms' || path.includes('/items');

  // On non-sync pages, just reload the page data
  if (!isSyncPage) {
    smartReload(document.getElementById('syncBtn'));
    return;
  }

  const btn = document.getElementById('syncBtn');
  const btnTextEl = document.getElementById('syncBtnText');
  const originalText = btnTextEl ? btnTextEl.textContent : 'Sync';
  const isItemsPage = path.includes('/items');
  const endpoint = isItemsPage ? '/api/v1/items/sync' : '/api/sync/scrape';
  const syncType = isItemsPage ? 'Items' : 'Platform';

  if (btn) { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); }
  if (btnTextEl) btnTextEl.textContent = `Syncing ${syncType}...`;

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
    });
    const data = await response.json();
    if (data.success) {
      if (btn) { btn.classList.remove('bg-slate-900', 'dark:bg-slate-700'); btn.classList.add('bg-green-600'); }
      if (isItemsPage) {
        if (btnTextEl) btnTextEl.textContent = 'Triggered!';
        showNotification('✅ Items scraper triggered! Data will update in ~10–15 minutes. Come back later.', 'success');
        setTimeout(() => {
          if (btn) { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-green-600'); btn.classList.add('bg-slate-900'); }
          if (btnTextEl) btnTextEl.textContent = originalText;
        }, 5000);
      } else {
        if (btnTextEl) btnTextEl.textContent = 'Triggered!';
        showNotification('✅ Platform scraper triggered! Data will update in ~3 minutes. Reloading...', 'success');
        setTimeout(() => window.location.reload(), 4000);
      }
    } else {
      throw new Error(data.message || 'Sync failed');
    }
  } catch (error) {
    if (btn) { btn.classList.remove('bg-slate-900', 'dark:bg-slate-700'); btn.classList.add('bg-red-600'); }
    if (btnTextEl) btnTextEl.textContent = 'Sync Failed';
    showNotification('❌ Sync failed: ' + error.message, 'error');
    setTimeout(() => {
      if (btn) { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-red-600'); btn.classList.add('bg-slate-900'); }
      if (btnTextEl) btnTextEl.textContent = originalText;
    }, 3000);
  }
}

async function triggerBothSyncs() {
  const btn = document.getElementById('mobileSyncBtn');
  const textEl = document.getElementById('mobileSyncBtnText');
  if (btn) { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); }
  if (textEl) textEl.textContent = '⚡ Syncing...';
  try {
    const [platformRes, itemsRes] = await Promise.all([
      fetch('/api/sync/scrape',    { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }),
      fetch('/api/v1/items/sync',  { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } })
    ]);
    const [platformData, itemsData] = await Promise.all([platformRes.json(), itemsRes.json()]);
    if (platformData.success && itemsData.success) {
      if (btn) { btn.classList.remove('bg-slate-900', 'dark:bg-slate-700'); btn.classList.add('bg-green-600'); }
      if (textEl) textEl.textContent = '✅ Triggered!';
      showNotification('✅ Both syncs triggered! Platforms ~3 min, Items ~10–15 min.', 'success');
    } else {
      throw new Error('One or both syncs failed');
    }
  } catch (error) {
    if (btn) { btn.classList.remove('bg-slate-900', 'dark:bg-slate-700'); btn.classList.add('bg-red-600'); }
    if (textEl) textEl.textContent = '❌ Failed';
    showNotification('❌ Sync failed: ' + error.message, 'error');
  }
  setTimeout(() => {
    if (btn) { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-green-600', 'bg-red-600'); btn.classList.add('bg-slate-900'); }
    if (textEl) textEl.textContent = '⚡ Sync All';
  }, 5000);
}

function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl font-semibold text-white transform transition-all duration-300 ${
    type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'
  }`;
  notification.textContent = message;
  notification.style.opacity = '0';
  notification.style.transform = 'translateY(-20px)';
  document.body.appendChild(notification);
  setTimeout(() => { notification.style.opacity = '1'; notification.style.transform = 'translateY(0)'; }, 10);
  setTimeout(() => {
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-20px)';
    setTimeout(() => notification.remove(), 300);
  }, 5000);
}

// Last known sync unix timestamp — used to skip full-page fetches when data hasn't changed
let _lastSyncTs = 0;

// Auto-reload every 5 minutes — skip if tab is hidden to avoid wasted requests
const _autoReloadTimer = setTimeout(() => { if (!document.hidden) smartReload(); }, 300000);
window.addEventListener('beforeunload', () => clearTimeout(_autoReloadTimer));

async function smartReload(btn) {
  // Visual feedback (only when user explicitly clicks Reload)
  if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

  try {
    // ── Step 1: Lightweight timestamp check (~50ms vs ~500ms for full HTML) ──
    // Skip the expensive full-page fetch on auto-refresh when data hasn't changed.
    // Always fetch fresh content when the user explicitly clicks Reload (btn is set).
    try {
      const tsRes = await fetch('/api/last-sync', { cache: 'no-store' });
      if (tsRes.ok) {
        const { timestamp } = await tsRes.json();
        if (!btn && _lastSyncTs !== 0 && timestamp === _lastSyncTs) {
          // Auto-refresh, nothing new — bail out without touching the DOM
          return;
        }
        _lastSyncTs = timestamp;
      }
    } catch (_) { /* timestamp check failed — proceed with full fetch anyway */ }

    // ── Step 2: Fetch fresh full-page HTML and swap content ──
    const res = await fetch(window.location.href);
    if (!res.ok) throw new Error('fetch failed');

    const html = await res.text();
    const doc  = new DOMParser().parseFromString(html, 'text/html');

    // Swap page content
    const freshContent = doc.getElementById('main-content');
    const liveContent  = document.getElementById('main-content');
    if (freshContent && liveContent) liveContent.innerHTML = freshContent.innerHTML;

    // Update sidebar last-sync time
    const freshTime = doc.getElementById('lastSyncTime');
    const liveTime  = document.getElementById('lastSyncTime');
    if (freshTime && liveTime) liveTime.textContent = freshTime.textContent;

  } catch {
    window.location.reload(); // hard fallback
  } finally {
    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
  }
}

function toggleInfoPopup() {
  document.getElementById('infoPopup').classList.toggle('hidden');
}

document.getElementById('infoPopup')?.addEventListener('click', function(e) {
  if (e.target === this) toggleInfoPopup();
});

function toggleMobileDrawer() {
  const drawer = document.getElementById('mobile-drawer');
  const overlay = document.getElementById('mobile-drawer-overlay');
  const isOpen = !drawer.classList.contains('-translate-x-full');
  if (isOpen) {
    drawer.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
  } else {
    drawer.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
  }
}

function toggleSection(sectionName) {
  const section = document.getElementById(sectionName + '-section');
  const arrow = document.getElementById(sectionName + '-arrow');
  section.classList.toggle('hidden');
  arrow.classList.toggle('rotate-180');
}

function updateSyncButtonText() {
  const path = window.location.pathname;
  const isSync = path === '/items' || path === '/platforms';
  const btnText = document.getElementById('syncBtnText');
  if (btnText) btnText.textContent = isSync ? 'Run Sync' : 'Refresh Data';
}
document.addEventListener('DOMContentLoaded', updateSyncButtonText);
updateSyncButtonText();

// ── Offline indicator ─────────────────────────────────────────────────────────
(function () {
  const banner = document.getElementById('offline-banner');
  if (!banner) return;
  function setOnlineState(online) {
    if (online) {
      banner.classList.add('hidden');
      document.querySelector('header')?.style.removeProperty('top');
    } else {
      banner.classList.remove('hidden');
      document.querySelector('header')?.style.setProperty('top', '36px');
    }
  }
  setOnlineState(navigator.onLine);
  window.addEventListener('online',  () => setOnlineState(true));
  window.addEventListener('offline', () => setOnlineState(false));
})();

// ── App Badge (count of offline platforms) ────────────────────────────────────
let deferredInstallPrompt = null;

if ('setAppBadge' in navigator) {
  function updateAppBadge() {
    if (document.hidden) return; // skip when tab not visible
    fetch('/api/monitor/dashboard', { cache: 'no-store' })
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        const offline = data?.data?.kpis?.platforms_offline ?? 0;
        if (offline > 0) {
          navigator.setAppBadge(offline).catch(() => {});
        } else {
          navigator.clearAppBadge().catch(() => {});
        }
      })
      .catch(() => {});
  }
  updateAppBadge();
  setInterval(updateAppBadge, 5 * 60 * 1000);
}

// ── PWA install prompt ────────────────────────────────────────────────────────
function showPWASidebarBtn(show) {
  const btn = document.getElementById('pwa-sidebar-btn');
  if (!btn) return;
  if (show) { btn.classList.remove('hidden'); btn.classList.add('flex'); }
  else       { btn.classList.add('hidden');    btn.classList.remove('flex'); }
}

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredInstallPrompt = e;
  showPWASidebarBtn(true);
  if (!sessionStorage.getItem('pwa-prompt-shown')) {
    setTimeout(() => {
      const prompt = document.getElementById('pwa-startup-prompt');
      if (prompt && deferredInstallPrompt) {
        prompt.classList.remove('hidden');
        sessionStorage.setItem('pwa-prompt-shown', '1');
      }
    }, 3000);
  }
});

window.addEventListener('appinstalled', () => {
  deferredInstallPrompt = null;
  showPWASidebarBtn(false);
  const prompt = document.getElementById('pwa-startup-prompt');
  if (prompt) prompt.classList.add('hidden');
});

function installPWA() {
  if (!deferredInstallPrompt) return;
  const prompt = document.getElementById('pwa-startup-prompt');
  if (prompt) prompt.classList.add('hidden');
  deferredInstallPrompt.prompt();
  deferredInstallPrompt.userChoice.then(() => {
    deferredInstallPrompt = null;
    showPWASidebarBtn(false);
  });
}

function dismissPWAPrompt() {
  const prompt = document.getElementById('pwa-startup-prompt');
  if (prompt) prompt.classList.add('hidden');
}

// ── Service Worker registration ───────────────────────────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('[PWA] Service worker registered:', reg.scope))
      .catch(err => console.log('[PWA] Service worker failed:', err));

    navigator.serviceWorker.addEventListener('message', event => {
      if (event.data?.type === 'SW_UPDATED') {
        const banner = document.createElement('div');
        banner.innerHTML = `
          <div style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:9999;
            background:#1e293b;color:#f1f5f9;padding:12px 20px;border-radius:12px;
            box-shadow:0 4px 24px rgba(0,0,0,0.4);display:flex;align-items:center;gap:12px;
            font-family:sans-serif;font-size:14px;border:1px solid #334155;">
            <span>⚡ New version available</span>
            <button onclick="window.location.reload()" style="background:#16a34a;color:#fff;
              border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
              Reload
            </button>
            <button onclick="this.parentElement.parentElement.remove()" style="background:none;
              color:#64748b;border:none;cursor:pointer;font-size:18px;line-height:1;">×</button>
          </div>`;
        document.body.appendChild(banner);
      }
    });
  });
}
