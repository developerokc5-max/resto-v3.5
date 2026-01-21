@echo off
REM Platform Page Protection Verification Script
REM Run this BEFORE and AFTER working on items to ensure platforms page is not affected

echo ========================================
echo PLATFORM PAGE PROTECTION CHECK
echo ========================================
echo.

echo [1/5] Checking platform cache file...
if not exist storage\app\platform_data_cache.json (
    echo ERROR: Platform cache file is MISSING!
    exit /b 1
)
echo OK: Platform cache file exists

echo.
echo [2/5] Verifying cache file size...
for %%A in (storage\app\platform_data_cache.json) do set size=%%~zA
if %size% LSS 10000 (
    echo ERROR: Platform cache file is too small ^(%size% bytes^)
    exit /b 1
)
echo OK: Cache file size is %size% bytes

echo.
echo [3/5] Checking backup exists...
if not exist storage\app\platform_data_cache_BACKUP.json (
    echo WARNING: No backup found, creating one...
    copy storage\app\platform_data_cache.json storage\app\platform_data_cache_BACKUP.json >nul
    echo OK: Backup created
) else (
    echo OK: Backup exists
)

echo.
echo [4/5] Verifying JSON structure...
python -c "import json; json.load(open('storage/app/platform_data_cache.json')); print('OK: Valid JSON')" 2>nul
if errorlevel 1 (
    echo ERROR: Platform cache is corrupted!
    exit /b 1
)

echo.
echo [5/5] Counting shops in cache...
python -c "import json; d=json.load(open('storage/app/platform_data_cache.json')); print(f'OK: {len(d[\"grab\"])} shops in cache')"

echo.
echo ========================================
echo PLATFORM PAGE IS PROTECTED
echo ========================================
echo Cache file: storage\app\platform_data_cache.json
echo Backup file: storage\app\platform_data_cache_BACKUP.json
echo.
echo Safe to proceed with items page work!
echo ========================================
