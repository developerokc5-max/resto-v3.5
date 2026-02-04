# Performance Verification Guide
**Quick reference for monitoring scraper optimization improvements**

## Baseline Metrics (Before Phase 1)
- **Date:** 2026-02-03 16:45 PM
- **Total Time:** 2216.6 seconds (36.9 minutes)
- **Database Scan:** ~50 seconds
- **Scraping Phase:** ~2166 seconds

## Degraded Performance (No Optimization)
- **Date:** 2026-02-04 11:32 AM (same day, morning)
- **Total Time:** 2583.5 seconds (43.1 minutes) ← TARGET FOR IMPROVEMENT
- **Database Scan:** ~70 seconds (+20s, +40% slower)
- **Scraping Phase:** ~2513 seconds (+347s, +27% slower)
- **Reason:** Time-of-day resource contention

## After Phase 1 (Indexes Applied)
- **Expected improvement:** 5-15 seconds (0.8-1.5% at 46 outlets)
- **Target time:** 2565-2580 seconds (42.7-43 minutes)
- **Note:** Modest improvement at current scale, but ensures scalability for growth

## How to Run a Manual Test

### Option 1: Using Artisan Command
```bash
cd /c/resto-db-v3.5
php artisan scraper:run --items
```

### Option 2: Using Python Directly
```bash
cd /c/resto-db-v3.5/item-test-trait-1
python scrape_items_sync_v2.py
```

### Option 3: View Automated Schedule
```bash
# Check if it's scheduled in cron
crontab -l | grep scraper

# Check if there's a scheduler
php artisan schedule:list
```

## Metrics to Capture

### 1. Total Execution Time
Look for this line in the log:
```
[MAIN] Total time: XXXX.X seconds (XX.X minutes)
```

### 2. Database Scan Time
Look for timestamp differences in database initialization:
```
[MAIN] Scanning outlets from shops table...
[MAIN] Found 46 active outlets (database scan completed)
```

### 3. Per-Worker Breakdown
```
[MAIN] Worker 0: XXX items
[MAIN] Worker 1: XXX items
[MAIN] Worker 2: XXX items
[MAIN] Worker 3: XXX items
[MAIN] Worker 4: XXX items
[MAIN] Worker 5: XXX items
```

### 4. Item Collection Results
```
[MAIN] Total items collected: XXXX
```

## Performance Trend Table

| Run | Date/Time | Outlets | Items | Total Time | DB Scan | Status |
|-----|-----------|---------|-------|------------|---------|--------|
| Baseline | 2026-02-03 16:45 | 46 | 7455 | 2216.6s | ~50s | Good afternoon performance |
| Degraded | 2026-02-04 11:32 | 46 | 7455 | 2583.5s | ~70s | Morning slowdown (26% ↑) |
| After Phase 1 | [TBD] | 46 | 7455 | ???? | ~50-60s | Indexes applied |
| Future Growth | TBD | 100+ | 16000+ | ???? | [Scalable] | Demonstrates index benefits |

## What to Look For

### ✅ SUCCESS INDICATORS
- Total time is between 2565-2580 seconds (5-15 second improvement)
- Database scan is back to ~50-60 seconds (optimization working)
- No database errors in logs
- All 46 outlets processed successfully
- All workers completed their assignments

### ⚠️ REGRESSION INDICATORS
- Total time increases beyond 2583.5 seconds (deterioration)
- Database scan time increases beyond 70 seconds
- Missing outlets or workers
- Database connection errors

### 📊 SCALABILITY TEST (Future)
Once indexes are confirmed working on 46 outlets:
1. Add a few more outlets (50-60) to test scalability
2. Monitor database scan time - should remain roughly constant
3. If scan time increases significantly, Phase 2 optimization needed

## Files to Monitor

### Log Files
- **Primary:** `/c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log`
- **Secondary:** `/c/resto-db-v3.5/storage/logs/items_scraper.log`
- **Laravel:** `/c/resto-db-v3.5/storage/logs/laravel.log`

### Database
```sql
-- Check if indexes exist
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'resto_db_v3' 
AND INDEX_NAME LIKE 'idx_%';

-- Check index usage
SELECT * FROM sys.schema_unused_indexes 
WHERE object_schema = 'resto_db_v3';
```

## Commands for Quick Analysis

### Get the total time quickly
```bash
tail -20 /c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log | grep "Total time"
```

### Compare run times
```bash
echo "=== 2026-02-03 (baseline) ===" && \
grep "Total time" /c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log | tail -1 && \
echo "=== 2026-02-04 (degraded) ===" && \
grep "Total time" /c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log | grep "11:32" && \
echo "=== Latest run ===" && \
grep "Total time" /c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log | tail -1
```

### Monitor indexes in real-time
```bash
watch -n 5 'tail -1 /c/resto-db-v3.5/item-test-trait-1/scrape_items_sync_v2.log'
```

## Next Steps After Verification

### If Performance Improved (5-15 seconds)
✅ Phase 1 successful - indexes are working  
→ Ready to proceed with Phase 2 (Query Optimization) when desired

### If No Improvement
⚠️ Investigate:
1. Confirm indexes actually exist: `SHOW INDEXES FROM shops;`
2. Check if scraper is using indexed columns
3. Verify migration was applied: `php artisan migrate:status`

### If Performance Degraded
❌ Rollback:
```bash
php artisan migrate:rollback --step=1
```
Then investigate why indexes caused regression (unusual)

## Documentation
- **Main Report:** `/c/resto-db-v3.5/OPTIMIZATION_STATUS.md`
- **This Guide:** `/c/resto-db-v3.5/PERFORMANCE_VERIFICATION_GUIDE.md`
- **Migration Code:** `/c/resto-db-v3.5/database/migrations/2026_02_04_133000_add_scraper_performance_indexes.php`

---
**Last Updated:** 2026-02-04  
**Status:** Awaiting next scheduled scraper run for verification
