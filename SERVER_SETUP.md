# 🚀 Server Setup Guide

## Current Server Structure

```
/fuel/
  ├── api/              ← OLD version
  ├── js/               ← OLD version
  ├── OLD/              ← OLD version
  ├── assets/           ← OLD version
  ├── cronjobs/         ← OLD version
  ├── uploads/          ← OLD version
  ├── logs/             ← OLD version
  ├── vendor/           ← OLD version
  ├── admin/            ← OLD version
  ├── optimizer/        ← OLD version (Python)
  ├── rev3/             ← NEW version (migration scripts only)
  └── *.php files       ← OLD version files
```

## Target Server Structure

```
/fuel/
  ├── REV20/                    ← Archived old version
  │   ├── api/
  │   ├── js/
  │   ├── OLD/
  │   ├── assets/
  │   ├── cronjobs/
  │   ├── uploads/
  │   ├── logs/
  │   ├── vendor/
  │   ├── admin/
  │   ├── optimizer/
  │   └── all old .php files
  │
  └── rev3/                     ← Active NEW version
      ├── backend/
      │   ├── public/           ← Web root (index.php, .htaccess)
      │   │   ├── index.php     ← API entry point
      │   │   └── .htaccess     ← URL rewriting
      │   ├── src/
      │   │   ├── Core/         ← Database, Response
      │   │   ├── Models/       ← Station, Depot, etc.
      │   │   ├── Services/     ← ForecastService, etc.
      │   │   ├── Controllers/  ← API controllers
      │   │   └── Utils/        ← UnitConverter, etc.
      │   ├── tests/            ← PHPUnit tests
      │   ├── .env              ← Database credentials
      │   └── composer.json     ← Dependencies
      │
      ├── frontend/             ← React/Vue (future)
      │   └── (empty for now)
      │
      └── optimizer/            ← Python optimizer (future)
          └── (empty for now)
```

---

## 📝 Step-by-Step Instructions

### Step 1: Reorganize Old Files (via FTP)

1. **Open FTP Client** (Transmit/FileZilla/etc.)
2. **Connect to server:**
   - Host: `www.kittykat.tech`
   - User: `d105380f801049`
   - Port: `21`

3. **Navigate to** `/fuel/` directory

4. **Create new folder** `REV20`

5. **Move these folders into `/fuel/REV20/`:**
   - `api/`
   - `js/`
   - `OLD/`
   - `assets/`
   - `cronjobs/`
   - `uploads/`
   - `logs/`
   - `vendor/`
   - `admin/`
   - `optimizer/` (old Python version)

6. **Move these PHP files into `/fuel/REV20/`:**
   - All `.php` files in root `/fuel/` (except those in `rev3/`)
   - Examples: `run-migration.php`, `check-*.php`, `test-*.php`

7. **Keep in `/fuel/rev3/`:**
   - Everything that's already there
   - We'll upload new files here

---

### Step 2: Upload REV 3.0 Backend Files

**Upload these folders from local to server:**

```
Local:  /REV 3.0/backend/
Server: /fuel/rev3/backend/
```

**Directory structure to upload:**

```
/fuel/rev3/backend/
  ├── public/
  │   ├── index.php
  │   └── .htaccess
  ├── src/
  │   ├── Core/
  │   │   ├── Database.php
  │   │   └── Response.php
  │   ├── Models/
  │   │   └── Station.php
  │   └── Utils/
  │       └── UnitConverter.php
  ├── tests/
  │   └── UnitConverterTest.php
  └── .env
```

**⚠️ Important:**
- Upload `.env` file with database credentials
- Make sure `.htaccess` is uploaded (rewrite rules)
- Check file permissions (755 for folders, 644 for files)

---

### Step 3: Configure Web Root (Optional)

**Current URL structure:**
```
https://fuel.kittykat.tech/rev3/public/index.php
```

**Desired URL structure:**
```
https://fuel.kittykat.tech/api/stations
```

**Two options:**

#### Option A: Keep `/rev3/` in URL (easier)
```
URL: https://fuel.kittykat.tech/rev3/api/stations

No changes needed - just access via /rev3/ prefix
```

#### Option B: Root domain for API (professional)
```
URL: https://fuel.kittykat.tech/api/stations

Requires:
1. Create .htaccess in /fuel/ root:
   RewriteRule ^api/(.*)$ rev3/public/index.php [L,QSA]

2. Or change web root to /fuel/rev3/public/
   (requires hosting control panel access)
```

---

### Step 4: Test API Endpoint

After upload, test the API:

```bash
# If using Option A (/rev3/ prefix):
curl https://fuel.kittykat.tech/rev3/api/stations

# If using Option B (root domain):
curl https://fuel.kittykat.tech/api/stations

# Expected response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "region_id": 1,
      "name": "Station Name",
      "code": "STATION_CODE"
    }
  ],
  "count": 9
}
```

---

## 🔐 SSH Setup (Automatic Deployment)

### Check if SSH is Available

Try connecting:
```bash
ssh d105380f801049@www.kittykat.tech
# or
ssh d105380f801049@kittykat.tech
```

If SSH is **enabled**, we can set up automatic deployment:

### Setup Automatic Deployment

1. **SSH into server:**
   ```bash
   ssh d105380f801049@www.kittykat.tech
   ```

2. **Navigate to project:**
   ```bash
   cd /path/to/fuel/rev3
   ```

3. **Clone repository:**
   ```bash
   git clone https://github.com/joinreachout/fuel-management-v3.git .
   ```

4. **Create deploy script:**
   ```bash
   nano deploy.sh
   ```

   ```bash
   #!/bin/bash
   # Auto-deploy script for REV 3.0

   echo "🚀 Deploying REV 3.0..."

   # Pull latest changes
   git pull origin main

   # Copy .env if not exists
   if [ ! -f backend/.env ]; then
       echo "⚠️  .env not found - please create it"
   fi

   # Set permissions
   chmod -R 755 backend/public
   chmod 644 backend/public/.htaccess

   echo "✅ Deployment complete!"
   ```

5. **Make executable:**
   ```bash
   chmod +x deploy.sh
   ```

6. **Deploy:**
   ```bash
   ./deploy.sh
   ```

### Future Deployments

```bash
# Local computer:
git add .
git commit -m "Add new feature"
git push origin main

# Then SSH to server:
ssh d105380f801049@www.kittykat.tech
cd /path/to/fuel/rev3
./deploy.sh
```

**Or even better - GitHub Actions (advanced):**
- Push to GitHub → automatically deploys to server
- No manual SSH needed
- Professional CI/CD pipeline

---

## 📊 Post-Setup Checklist

After completing setup:

- [ ] Old files moved to `/fuel/REV20/`
- [ ] New files in `/fuel/rev3/backend/`
- [ ] `.env` file uploaded with correct credentials
- [ ] `.htaccess` uploaded and working
- [ ] File permissions correct (755/644)
- [ ] API endpoint responding: `GET /api/stations`
- [ ] Database connection working
- [ ] No errors in server logs

---

## 🔧 Troubleshooting

### API returns 404 Not Found
- Check `.htaccess` exists in `/fuel/rev3/backend/public/`
- Verify `mod_rewrite` is enabled on server
- Check file permissions

### API returns 500 Internal Server Error
- Check PHP error logs (usually in `/logs/` or control panel)
- Verify `.env` file has correct database credentials
- Check PHP version (need PHP 8.0+)

### Database connection fails
- Verify credentials in `.env`
- Check if MySQL server is running
- Test connection using phpMyAdmin

### Files not uploading via FTP
- Check FTP credentials
- Verify folder permissions
- Try passive mode in FTP client

---

## 🎯 Next Steps

After server setup:

1. Test API endpoint: `GET /api/stations`
2. Create more Models: Depot, FuelType, DepotTank
3. Create Services: ForecastService, OptimizationService
4. Build frontend (React/Vue)
5. Integrate Python optimizer

---

**Last updated:** Feb 16, 2025
**Server:** fuel.kittykat.tech
**Version:** 3.0
