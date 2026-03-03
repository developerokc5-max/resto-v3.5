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

// Auto-reload every 5 minutes — timer cleared on navigation to prevent multi-tab waste
const _autoReloadTimer = setTimeout(smartReload, 300000);
window.addEventListener('beforeunload', () => clearTimeout(_autoReloadTimer));

async function smartReload(btn) {
  // Visual feedback
  if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

  try {
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
