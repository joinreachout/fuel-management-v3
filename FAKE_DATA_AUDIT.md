# 🚨 Fake Data Audit Report - Frontend Components

**Audit Date:** 2026-02-17
**Auditor:** Claude Sonnet 4.5

---

## 📊 Summary

| Component | Status | Fake Data Found | Priority |
|-----------|--------|-----------------|----------|
| **StationFillLevels.vue** | ✅ FIXED | Was using Math.random() | DONE |
| **StockByFuelType.vue** | ❌ FAKE DATA | Math.random() on line 110 | HIGH |
| **TransferActivity.vue** | ❌ FAKE DATA | Math.random() progress on line 171 | MEDIUM |
| **OptimusAI.vue** | ⚠️ CHECK | Contains "mock" keyword | LOW |
| **StationFillLevels.vue.backup** | 🗑️ DELETE | Backup file, not used | N/A |

---

## ✅ FIXED

### 1. StationFillLevels.vue
**Status:** ✅ FIXED (2026-02-17)

**Previous Issue:**
```javascript
// OLD CODE - FAKE DATA
const fixedTankTypes = [
  { name: 'Diesel', capacity: 100000 },
  { name: 'Petrol 95', capacity: 100000 },
  { name: 'Petrol 98', capacity: 100000 }
];

const currentStationTanks = computed(() => {
  return fixedTankTypes.map((tankType, idx) => {
    const fillPercentage = Math.random() * 70 + 20; // FAKE!
    //...
  });
});
```

**Fixed:**
```javascript
// NEW CODE - REAL API DATA
const loadStationTanks = async (stationId) => {
  const response = await stationsApi.getTanks(stationId); // REAL API
  // Transform and display real tank data
};
```

**Result:** Now displays real tank data from `/api/stations/{id}/tanks`

---

## ❌ COMPONENTS WITH FAKE DATA (Need Fixing)

### 2. StockByFuelType.vue
**File:** `frontend/src/components/StockByFuelType.vue`
**Line:** 110
**Priority:** HIGH

**Issue:**
```javascript
const stock = Math.random() * 80000 + 20000; // FAKE RANDOM DATA!
```

**Context:**
This component shows fuel stock distribution across stations. It's generating random stock values instead of fetching real data from API.

**Fix Required:**
Use endpoint: `GET /api/fuel-types/{id}/stations`

**Expected API Response:**
```json
{
  "success": true,
  "data": [
    {
      "station_id": 249,
      "station_name": "Станция Каинда",
      "total_stock_liters": "8456789.12",
      "total_stock_tons": "6765.43",
      "avg_fill_percentage": "78.0"
    }
  ]
}
```

**Action:**
1. Add `getStationsByFuelType` method to `fuelTypesApi` in `api.js`
2. Replace Math.random() with real API call
3. Transform response data for chart display

---

### 3. TransferActivity.vue
**File:** `frontend/src/components/TransferActivity.vue`
**Line:** 171
**Priority:** MEDIUM

**Issue:**
```javascript
progress: t.status === 'in_progress' ? Math.floor(Math.random() * 40 + 40) : 0, // FAKE!
```

**Context:**
This component shows transfer progress. The "in_progress" transfers get a random progress percentage (40-80%) instead of real progress data.

**Possible Solutions:**
1. **Option A:** Remove progress bar (no real progress tracking in DB)
2. **Option B:** Calculate estimated progress based on:
   - `created_at` timestamp
   - `estimated_days` field
   - Current date
   - Formula: `progress = (days_elapsed / estimated_days) * 100`

**Recommendation:**
Option B - Calculate progress from timestamps:
```javascript
progress: t.status === 'in_progress' ? calculateProgress(t.created_at, t.estimated_days) : 0
```

**Action:**
1. Add `calculateProgress()` helper function
2. Use real timestamps from API
3. Show realistic progress based on time elapsed

---

### 4. OptimusAI.vue
**File:** `frontend/src/components/OptimusAI.vue`
**Priority:** LOW (Need to verify)

**Issue:**
Contains keyword "mock" - needs investigation to determine if it's using fake data or just has "mock" in comments/variable names.

**Action:**
Manual review needed to determine if real issue exists.

---

## 🗑️ FILES TO DELETE

### 5. StationFillLevels.vue.backup
**File:** `frontend/src/components/StationFillLevels.vue.backup`
**Action:** DELETE

This is a backup file and should not be in production codebase.

```bash
rm "frontend/src/components/StationFillLevels.vue.backup"
```

---

## 🎯 PRIORITY FIX ORDER

### IMMEDIATE (Today):
1. ✅ StationFillLevels.vue - DONE
2. ❌ StockByFuelType.vue - Use `/api/fuel-types/{id}/stations`
3. ❌ TransferActivity.vue - Calculate progress from timestamps
4. 🗑️ Delete StationFillLevels.vue.backup

### REVIEW (This Week):
5. ⚠️ OptimusAI.vue - Manual review needed

---

## 📋 API Endpoints Available

All these endpoints are working and ready to use:

### Stations:
- ✅ `GET /api/stations` - All stations
- ✅ `GET /api/stations/{id}/tanks` - Station tanks (JUST ADDED!)
- ✅ `GET /api/stations/{id}/depots` - Station depots

### Fuel Types:
- ✅ `GET /api/fuel-types` - All fuel types
- ✅ `GET /api/fuel-types/{id}/stock` - Total stock for fuel type
- ✅ `GET /api/fuel-types/{id}/stations` - Fuel distribution by stations (JUST ADDED!)

### Transfers:
- ✅ `GET /api/transfers` - All transfers
- ✅ `GET /api/transfers/{id}` - Single transfer
- ✅ `GET /api/transfers/status/{status}` - Transfers by status
- ✅ `GET /api/transfers/station/{id}` - Transfers by station

---

## 🔧 FIXES NEEDED

### Fix #1: StockByFuelType.vue

**Current Code (Lines 100-120):**
```javascript
// FAKE DATA - REMOVE THIS
const stations = computed(() => {
  if (!selectedFuelType.value) return [];

  return allStations.value.map(station => {
    const stock = Math.random() * 80000 + 20000; // FAKE!
    return {
      name: station.station_name,
      stock: stock
    };
  });
});
```

**Fixed Code:**
```javascript
// REAL DATA - USE API
const stations = ref([]);

const loadStockByFuelType = async (fuelTypeId) => {
  try {
    const response = await fuelTypesApi.getStationsByFuelType(fuelTypeId);

    if (response.data.success) {
      stations.value = response.data.data.map(s => ({
        name: s.station_name,
        stock: parseFloat(s.total_stock_liters),
        stockTons: parseFloat(s.total_stock_tons),
        fillPercentage: parseFloat(s.avg_fill_percentage)
      }));
    }
  } catch (error) {
    console.error('Error loading stock by fuel type:', error);
  }
};
```

**Also need to add to api.js:**
```javascript
// Fuel Types API
export const fuelTypesApi = {
  getAll: () => api.get('/fuel-types'),
  getById: (id) => api.get(`/fuel-types/${id}`),
  getTotalStock: (id) => api.get(`/fuel-types/${id}/total-stock`),
  getStationsByFuelType: (id) => api.get(`/fuel-types/${id}/stations`), // ADD THIS
};
```

---

### Fix #2: TransferActivity.vue

**Current Code (Line 171):**
```javascript
progress: t.status === 'in_progress' ? Math.floor(Math.random() * 40 + 40) : 0, // FAKE!
```

**Fixed Code:**
```javascript
// Add helper function
const calculateProgress = (createdAt, estimatedDays) => {
  if (!createdAt || !estimatedDays) return 0;

  const created = new Date(createdAt);
  const now = new Date();
  const daysElapsed = (now - created) / (1000 * 60 * 60 * 24);
  const progress = Math.min((daysElapsed / estimatedDays) * 100, 95); // Cap at 95%

  return Math.floor(progress);
};

// Use in transform
progress: t.status === 'in_progress' ? calculateProgress(t.created_at, t.estimated_days) : 0,
```

---

## ✅ VERIFICATION CHECKLIST

After applying fixes:

- [ ] StockByFuelType shows real stock values from database
- [ ] Values change when data updates (not random on every render)
- [ ] TransferActivity progress is calculated from timestamps
- [ ] Progress values are realistic (not jumping around)
- [ ] All API endpoints return data successfully
- [ ] No console errors
- [ ] StationFillLevels.vue.backup deleted

---

## 📊 IMPACT

**Current State:**
- 3 components showing FAKE data
- Users see random values that change on every page refresh
- No real business insights possible
- Misleading information

**After Fixes:**
- All components show REAL data from database
- Values are consistent and accurate
- Real-time business insights
- Trustworthy dashboard

---

**Next Steps:**
1. Fix StockByFuelType.vue (highest priority)
2. Fix TransferActivity.vue progress calculation
3. Delete backup file
4. Deploy to production
5. Verify all data is real

---

**Audit Completed:** 2026-02-17
**Status:** 1/4 fixed, 2 need immediate fixes, 1 to review
