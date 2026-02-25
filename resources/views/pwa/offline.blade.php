<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HawkerOps — Offline</title>
  <meta name="theme-color" content="#0f172a" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      color: #f1f5f9;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      padding: 24px;
      text-align: center;
    }
    .icon { font-size: 64px; margin-bottom: 24px; }
    h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
    p { font-size: 15px; color: #94a3b8; margin-bottom: 32px; line-height: 1.6; }
    .btn {
      background: #16a34a;
      color: white;
      border: none;
      padding: 12px 28px;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .btn:hover { opacity: 0.85; }
    .cached-pages {
      margin-top: 40px;
      background: #1e293b;
      border-radius: 16px;
      padding: 20px;
      width: 100%;
      max-width: 320px;
    }
    .cached-pages h2 { font-size: 13px; color: #64748b; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
    .page-link {
      display: block;
      padding: 10px 14px;
      border-radius: 8px;
      color: #cbd5e1;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.15s;
      margin-bottom: 4px;
    }
    .page-link:hover { background: #334155; }
  </style>
</head>
<body>
  <div class="icon">⚡</div>
  <h1>You're Offline</h1>
  <p>No internet connection detected.<br>Some pages may still be available from cache.</p>
  <button class="btn" onclick="window.location.reload()">Try Again</button>

  <div class="cached-pages">
    <h2>Try cached pages</h2>
    <a class="page-link" href="/dashboard">📊 Dashboard</a>
    <a class="page-link" href="/platforms">🔗 Platforms</a>
    <a class="page-link" href="/alerts">🔔 Alerts</a>
    <a class="page-link" href="/items">🍜 Items</a>
    <a class="page-link" href="/stores">🏪 Stores</a>
  </div>
</body>
</html>
