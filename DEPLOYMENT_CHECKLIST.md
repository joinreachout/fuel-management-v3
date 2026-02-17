# Deployment Checklist - Procurement Advisor Update

## ✅ Completed Steps

1. **Database Migration** - ✅ DONE
   - Created `supplier_station_offers` table
   - Inserted seed data for supplier 8 (9 stations)
   - Foreign keys and indexes created

2. **Code Update** - ✅ DONE
   - Updated `ProcurementAdvisorService.php`
   - Now uses `supplier_station_offers` table
   - Station-specific delivery times and prices

3. **Git Commit** - ✅ DONE
   - Committed all changes
   - Pushed to GitHub branch `claude/romantic-heyrovsky`

---

## 📋 Next: Deploy to Production

### Method 1: cPanel File Manager (Recommended)

1. **Login to cPanel**
   - Go to https://my.zone.ee
   - Login to your account
   - Open cPanel

2. **Navigate to directory**
   - File Manager → `/home/kittykat/htdocs/rev3/backend/src/Services/`

3. **Upload file**
   - Upload `ProcurementAdvisorService.php`
   - Source location on your Mac:
     ```
     /Volumes/WORK/Projects/Handshake/AI/GAS STATIONS PROJECT/REV 3.0/.claude/worktrees/romantic-heyrovsky/backend/src/Services/ProcurementAdvisorService.php
     ```
   - **OR** use the archive: `procurement_advisor_update.tar.gz`

4. **Set permissions** (if needed)
   - Right-click file → Permissions → 644

---

### Method 2: SSH/SCP (Alternative)

If you have SSH access configured:

```bash
cd "/Volumes/WORK/Projects/Handshake/AI/GAS STATIONS PROJECT/REV 3.0/.claude/worktrees/romantic-heyrovsky"

scp backend/src/Services/ProcurementAdvisorService.php \
  kittykat@fuel.kittykat.tech:/home/kittykat/htdocs/rev3/backend/src/Services/
```

---

## 🧪 Testing After Deployment

### 1. Test Procurement Summary
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/procurement/summary" | jq '.'
```

**Expected:**
- Should show `total_shortages`
- Should have `by_urgency` breakdown
- Should show `total_value_estimate` (NOW WITH ACTUAL PRICES!)

### 2. Test Upcoming Shortages
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/procurement/upcoming-shortages?days=14" | jq '.data[0]'
```

**Expected output should include:**
```json
{
  "station_name": "...",
  "fuel_type_name": "...",
  "urgency": "MUST_ORDER",
  "days_left": 4.7,
  "best_supplier": {
    "name": "НПЗ Кара май Ойл-Тараз",
    "avg_delivery_days": 16,           // ← NOW STATION-SPECIFIC!
    "price_per_ton": 830.00,           // ← NOW HAS ACTUAL PRICE!
    "currency": "USD"                  // ← NOW HAS CURRENCY!
  }
}
```

### 3. Test Supplier Recommendations
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/procurement/supplier-recommendations?fuel_type_id=25&required_tons=200&urgency=CRITICAL" | jq '.'
```

**Expected:**
- List of suppliers ranked by composite score
- Now includes `price_per_ton` and `currency`
- Average delivery times calculated from offers

---

## 🔍 Verify Database Data

Run these queries in phpMyAdmin to verify data:

```sql
-- 1. Check offers count
SELECT COUNT(*) FROM supplier_station_offers WHERE is_active = 1;
-- Expected: 9 rows (supplier 8 has 9 stations)

-- 2. View supplier 8 offers
SELECT
    st.name as station,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.currency
FROM supplier_station_offers sso
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.supplier_id = 8
ORDER BY sso.delivery_days;

-- Expected: 9 rows with different delivery_days (16-21 days)
```

More test queries available in `test_supplier_offers.sql`

---

## ✅ Success Criteria

After deployment, verify:

- [ ] API `/procurement/summary` returns data without errors
- [ ] API `/procurement/upcoming-shortages` shows `best_supplier.price_per_ton` (not null!)
- [ ] API shows different `delivery_days` for different stations
- [ ] Frontend Procurement Advisor widget displays prices
- [ ] No PHP errors in server logs

---

## 🐛 Troubleshooting

### If API returns errors:

1. **Check PHP error log**
   - cPanel → Error Logs
   - Look for syntax errors or database errors

2. **Verify table exists**
   ```sql
   SHOW TABLES LIKE 'supplier_station_offers';
   DESCRIBE supplier_station_offers;
   ```

3. **Check file permissions**
   - Should be 644 for PHP files
   - Should be owned by `kittykat:kittykat`

4. **Clear PHP cache** (if using opcache)
   - Create file: `/home/kittykat/htdocs/rev3/backend/public/clear_cache.php`
   - Content: `<?php opcache_reset(); echo "Cache cleared"; ?>`
   - Visit: https://fuel.kittykat.tech/rev3/backend/public/clear_cache.php

---

## 📊 Expected Improvements

### Before (with avg_delivery_days):
```json
"best_supplier": {
  "avg_delivery_days": 18,        // Same for all stations
  "price_per_ton": null,          // No price info
  "currency": null
}
```

### After (with supplier_station_offers):
```json
"best_supplier": {
  "avg_delivery_days": 16,        // Specific to Каинда station
  "price_per_ton": 830.00,        // Actual price!
  "currency": "USD"               // Actual currency!
}
```

---

**Date:** 2026-02-17
**Status:** Ready for deployment
**Files to deploy:** `backend/src/Services/ProcurementAdvisorService.php`
