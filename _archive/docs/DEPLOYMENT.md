# Deployment Guide - Platform Monitoring System

## How It Works on Render

### Architecture

```
┌─────────────────┐         ┌──────────────────┐
│   Web Service   │         │   Cron Service   │
│  (Laravel App)  │◄────────│  (Scraper Job)   │
│                 │  reads  │                  │
│  Serves /       │  cache  │  Runs every 15   │
│  /platforms     │         │  minutes         │
└─────────────────┘         └──────────────────┘
         │                           │
         │                           │
         ▼                           ▼
    ┌────────────────────────────────┐
    │   platform_data_cache.json     │
    │   (Shared storage/volume)      │
    └────────────────────────────────┘
                     │
                     ▼
            ┌────────────────┐
            │   PostgreSQL   │
            │   Database     │
            └────────────────┘
```

### Services

1. **Web Service** (`resto-db-v3`)
   - Serves the Laravel dashboard
   - Shows platform status from cached data
   - Has "Run Scrape" button for manual triggers
   - Fast response (no 60-second waits)

2. **Cron Service** (`resto-db-scraper`)
   - Runs `cache_platform_data.php` every 15 minutes
   - Uses Playwright to scrape RestoSuite
   - Updates `platform_data_cache.json`
   - Syncs to database

## Problem & Solution

### ❌ Problem Without Cron

```
User clicks "Run Scrape"
  ↓
Web request starts scraper (60-70 seconds)
  ↓
Web request timeout (30 seconds) ⚠️ ERROR
  ↓
User sees error, data not updated
```

### ✅ Solution With Cron + Background Jobs

```
Cron runs every 15 minutes
  ↓
Scraper runs in background (60-70 seconds)
  ↓
Cache file updated automatically
  ↓
User opens /platforms → sees fresh data (< 15 min old)
```

**Manual Trigger:**
```
User clicks "Run Scrape"
  ↓
Button triggers background job
  ↓
Response: "Scraping started, refresh in 1 minute"
  ↓
User waits 1 minute, refreshes page
  ↓
Updated data appears ✅
```

## How Admin Uses It

### Automated Updates
1. Data is automatically refreshed every 15 minutes by cron job
2. Admin just opens `https://your-app.onrender.com/platforms`
3. Page shows current status (max 15 minutes old)

### Manual Refresh (Button Click)
1. Admin opens `/platforms`
2. Clicks "Run Scrape" button
3. Button becomes disabled, shows "Scraping..."
4. API queues a Laravel job (returns immediately)
5. Queue worker processes job in background (takes 60-80 seconds)
6. After 1 minute, admin clicks "Reload" button
7. Fresh data appears

**How it works internally:**
- Clicking button → API endpoint `/api/sync/scrape`
- API dispatches `ScrapePlatformData` job to queue
- Laravel queue worker (running in background) picks up job
- Job runs Python scraper, saves to cache file
- Job updates database via artisan command
- Page reload fetches fresh data from cache

## Preventing Errors on Render

### 1. Timeout Prevention ✅
- **Cron job**: Scraper runs as separate service (no web timeout)
- **Manual scrape**: Uses Laravel queue jobs (not direct exec)
- **Queue worker**: Runs in background with 180-second timeout
- **Web response**: Returns immediately (< 1 second)
- **Button always works**: Even after restart/sleep

### 2. Dependencies Installed
The Dockerfile includes:
```dockerfile
RUN apt-get install -y python3 python3-pip
RUN pip3 install playwright
RUN playwright install chromium
RUN playwright install-deps
```

### 3. Environment Variables
Set in `render.yaml`:
- `RESTOSUITE_EMAIL` - Login email
- `RESTOSUITE_PASSWORD` - Login password

### 4. Rate Limiting
- Cron runs max every 15 minutes
- Manual scrape blocked if < 1 minute since last run
- Prevents multiple simultaneous scrapes

## Deployment Steps

### 1. Push to GitHub
```bash
git add .
git commit -m "Add platform monitoring with cron"
git push origin main
```

### 2. Create Render Blueprint
- Go to Render Dashboard
- Click "New" → "Blueprint"
- Connect your GitHub repo
- Render will read `render.yaml` automatically

### 3. Configure Services
Render creates:
- Web service (free tier)
- Cron service (free tier)
- PostgreSQL database (free tier)

### 4. Monitor
- Web: https://resto-db-v3.onrender.com
- Logs: Check cron service logs every 15 minutes

## Cost
**Free Tier:**
- Web service: Free
- Cron service: Free (750 hours/month)
- Database: Free (90 days, then $7/month)

**Total: $0/month** (first 90 days)

## Testing Locally

### Run scraper manually:
```bash
php cache_platform_data.php
```

### Check cache file:
```bash
cat storage/app/platform_data_cache.json
```

### Test API endpoint:
```bash
curl -X POST http://127.0.0.1:8000/api/sync/scrape
```

## Troubleshooting

### Scraper not running?
Check cron service logs in Render dashboard

### Data not updating?
1. Check cache file modification time
2. Verify cron schedule (`*/15 * * * *`)
3. Check for scraper errors in logs

### Wrong data showing?
1. Click "Run Scrape"
2. Wait 1 minute
3. Click "Reload"

## Security Notes

- Credentials stored as environment variables (not in code)
- Scraper runs in isolated container
- No public API endpoints for scraping (POST only)
- Rate limiting prevents abuse
