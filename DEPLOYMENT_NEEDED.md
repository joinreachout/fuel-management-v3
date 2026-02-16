# 🚀 Manual Deployment Required

**Date:** 2026-02-16
**Reason:** SSH/FTP access issues - automatic deployment failed

---

## Files to Upload

Upload the following file to production server:

### 1. ReportService.php
**Local Path:**
`/Volumes/WORK/Projects/Handshake/AI/GAS STATIONS PROJECT/REV 3.0/backend/src/Services/ReportService.php`

**Production Path:**
`fuel.kittykat.tech/rev3/backend/src/Services/ReportService.php`

**Changes:**
- Fixed `getLowStockReport()` method to use correct schema
- Changed from `stock_policies.min_stock_days` → `pol.min_level_liters`
- Changed from `sp.daily_consumption_liters` → `sp.liters_per_day` (from sales_params)
- Now uses sales_params for actual consumption (FACTS)
- Now uses stock_policies for threshold rules (RULES)

---

## Git Status

✅ Changes committed to Git: `f65460b`
✅ Changes pushed to GitHub: https://github.com/joinreachout/fuel-management-v3

---

## Expected Result

After deployment, this endpoint should work:
- ✅ `GET /api/reports/low-stock` - Currently failing with "Column 'sp.min_stock_days' not found"

---

## How to Deploy

### Option 1: FTP Upload (Recommended)
1. Connect to FTP: `ftp://d105380.mysql.zonevs.eu`
2. Navigate to: `fuel.kittykat.tech/rev3/backend/src/Services/`
3. Upload: `ReportService.php`

### Option 2: Web File Manager
1. Login to hosting control panel
2. Navigate to file manager
3. Upload to correct path

### Option 3: Fix SSH Access
```bash
# Add SSH key or fix host verification
ssh-keyscan d105380.mysql.zonevs.eu >> ~/.ssh/known_hosts

# Then deploy:
cd "/Volumes/WORK/Projects/Handshake/AI/GAS STATIONS PROJECT/REV 3.0"
scp backend/src/Services/ReportService.php d105380@d105380.mysql.zonevs.eu:~/fuel.kittykat.tech/rev3/backend/src/Services/
```

---

## Testing After Deployment

```bash
# Test the fixed endpoint
curl "https://fuel.kittykat.tech/rev3/backend/public/api/reports/low-stock"
```

**Expected:** Success response with low stock tanks data
**Current:** Error: "Column 'sp.min_stock_days' not found"

---

**Status:** ⏳ PENDING MANUAL UPLOAD
