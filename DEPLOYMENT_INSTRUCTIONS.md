# 🚀 Deployment Instructions - New Endpoints

**Date:** 2026-02-17
**Commit:** bad351d
**Files to Deploy:** 6 files

---

## 📦 What's New

### New Services (2):
1. `backend/src/Services/StationTanksService.php` - Get all tanks for a station grouped by depot
2. `backend/src/Services/FuelStockService.php` - Get fuel stock distribution across stations

### Updated Controllers (2):
3. `backend/src/Controllers/StationController.php` - Added `tanks()` method
4. `backend/src/Controllers/FuelTypeController.php` - Added `stations()` method

### Updated Routing:
5. `backend/public/index.php` - Added 2 new routes

### Documentation:
6. `CODE_AUDIT_RESULTS.md` - Detailed code audit report

---

## 🎯 New API Endpoints

### 1. GET /api/stations/{id}/tanks
**Description:** Get all tanks for a station, grouped by depot

**Example Request:**
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/stations/249/tanks"
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "depot_id": 152,
      "depot_name": "Каинда Ойл",
      "depot_code": "KAINOIL",
      "tanks": [
        {
          "tank_id": 130,
          "tank_number": "№10",
          "fuel_type_id": 34,
          "fuel_type_name": "A-80",
          "capacity_liters": "4000000.00",
          "current_stock_liters": "339891.63",
          "current_stock_tons": "271.91",
          "fill_percentage": "85.0"
        }
      ],
      "total_tanks": 10,
      "total_capacity_liters": "41035000.00",
      "total_stock_liters": "21449740.52"
    }
  ],
  "station_id": 249,
  "total_depots": 4,
  "total_tanks": 24
}
```

### 2. GET /api/fuel-types/{id}/stations
**Description:** Get fuel stock distribution for a specific fuel type across all stations

**Example Request:**
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/fuel-types/31/stations"
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "station_id": 249,
      "station_name": "Станция Каинда",
      "station_code": "KAIN",
      "region_name": "Алматинская область",
      "depot_count": 2,
      "tank_count": 5,
      "total_capacity_liters": "10845000.00",
      "total_stock_liters": "8456789.12",
      "total_stock_tons": "6765.43",
      "avg_fill_percentage": "78.0"
    }
  ],
  "fuel_type_id": 31,
  "fuel_type_name": "A-95",
  "fuel_type_code": "GAS95",
  "total_stations": 6,
  "total_tanks": 15,
  "grand_total_stock_liters": "25456789.00"
}
```

---

## 📋 Files to Upload via FTP

Upload these files to production server:

### Path: `/fuel/rev3/backend/src/Services/`
1. ✅ `StationTanksService.php` (NEW)
2. ✅ `FuelStockService.php` (NEW)

### Path: `/fuel/rev3/backend/src/Controllers/`
3. ✅ `StationController.php` (UPDATED)
4. ✅ `FuelTypeController.php` (UPDATED)

### Path: `/fuel/rev3/backend/public/`
5. ✅ `index.php` (UPDATED - added 2 routes)

---

## 🔧 Manual Deployment Steps

### Option A: Via FTP (Transmit/FileZilla)

1. **Connect to FTP:**
   - Host: `www.kittykat.tech`
   - User: `d105380f801049`
   - Port: `21`

2. **Upload Files:**
   - Upload `StationTanksService.php` → `/fuel/rev3/backend/src/Services/`
   - Upload `FuelStockService.php` → `/fuel/rev3/backend/src/Services/`
   - Upload `StationController.php` → `/fuel/rev3/backend/src/Controllers/` (replace)
   - Upload `FuelTypeController.php` → `/fuel/rev3/backend/src/Controllers/` (replace)
   - Upload `index.php` → `/fuel/rev3/backend/public/` (replace)

3. **Set Permissions:**
   - All .php files: `644`

---

### Option B: Via SSH (Automatic)

```bash
# SSH into server
ssh virt105026@kittykat.tech

# Navigate to project
cd /fuel/rev3

# Pull latest changes from GitHub
git pull origin main

# Verify files
ls -la backend/src/Services/StationTanksService.php
ls -la backend/src/Services/FuelStockService.php

# Set permissions
chmod 644 backend/src/Services/*.php
chmod 644 backend/src/Controllers/*.php
chmod 644 backend/public/index.php

# Exit
exit
```

---

## ✅ Testing After Deployment

Test both endpoints:

```bash
# Test 1: Station tanks (Станция Каинда)
curl "https://fuel.kittykat.tech/rev3/backend/public/api/stations/249/tanks"

# Expected: 24 tanks grouped by 4 depots
# Should return: {"success": true, "data": [...], "total_tanks": 24}

# Test 2: Fuel stock by stations (A-95)
curl "https://fuel.kittykat.tech/rev3/backend/public/api/fuel-types/31/stations"

# Expected: Stock distribution for A-95 across all stations
# Should return: {"success": true, "data": [...], "fuel_type_name": "A-95"}
```

**Expected Results:**
- ✅ Both endpoints return `"success": true`
- ✅ Data arrays are populated
- ✅ No SQL errors
- ✅ Response time < 1 second

---

## 🐛 Troubleshooting

### Issue: "Endpoint not found"
**Fix:**
- Verify `index.php` was uploaded
- Check file permissions (should be 644)
- Clear any server-side cache

### Issue: "Service class not found"
**Fix:**
- Verify both Service files uploaded to `/fuel/rev3/backend/src/Services/`
- Check file permissions
- Verify filenames are exact (case-sensitive)

### Issue: SQL errors
**Fix:**
- Verify database connection in `.env`
- Check that `depot_tanks`, `depots`, `stations`, `fuel_types` tables exist
- Run test query in phpMyAdmin to verify data

---

## 📊 Deployment Checklist

- [ ] Files uploaded via FTP or git pull
- [ ] Permissions set to 644
- [ ] Test endpoint 1: `/api/stations/249/tanks`
- [ ] Test endpoint 2: `/api/fuel-types/31/stations`
- [ ] Both return success: true
- [ ] No errors in server logs
- [ ] Update API documentation

---

## 🎯 Next Steps After Deployment

1. ✅ Test endpoints with different station IDs (249-257)
2. ✅ Test endpoints with different fuel types (24-35)
3. ⏳ Update API_DOCUMENTATION.md with new endpoints
4. ⏳ Fix critical bugs from CODE_AUDIT_RESULTS.md:
   - Fix `DepotTank::getStockHistory()` column name bug
   - Add API authentication
   - Add Composer autoloader

---

**Deployed By:** Claude Sonnet 4.5
**Deployment Date:** 2026-02-17
**Git Commit:** bad351d
**Status:** Ready for deployment
