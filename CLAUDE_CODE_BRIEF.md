# 🔧 REV 3.0 Fuel Management System — Claude Code Audit & Improvement Brief

## Project Overview

Fuel management system for Alfa Oil (Kyrgyzstan) — railway fuel supply optimization platform.
- **Backend:** PHP 8.1+ (custom MVC + Services, no framework), MySQL 8.0
- **Frontend:** Vue 3 + Vite 7 + Tailwind 4 + Chart.js + Axios
- **Production:** https://fuel.kittykat.tech/rev3/
- **DB:** d105380_fuelv3 on d105380.mysql.zonevs.eu
- **Architecture:** Models → Services → Controllers, RESTful API (31 GET endpoints)
- **Source of Truth:** `depot_tanks.current_stock_liters` (all stock in liters, tons calculated on-the-fly)

---

## 🚨 CRITICAL ISSUES (Fix First)

### 1. CostAnalysisService breaks architectural pattern
**File:** `backend/src/Services/CostAnalysisService.php`
**Problem:** Uses `Database::getInstance()` and `$pdo->query()` directly — completely different pattern from every other Service. All other services use `Database::fetchAll()` static methods. This service also references `orders_calendar` table and `fuel_prices` table which are NOT in the migration schema (`001_create_core_tables.sql`). This means the service either uses legacy tables from a previous revision, or the migration is incomplete.
**Action:**
- Refactor to use `Database::fetchAll()` / `Database::fetchOne()` like all other services
- Verify which tables (`orders_calendar`, `fuel_prices`) this service actually needs
- Either add these tables to the migration or refactor the service to use the `orders` table
- Remove direct PDO usage

### 2. No autoloader — manual require_once chain
**File:** `backend/public/index.php`
**Problem:** 30+ manual `require_once` statements. Every new file requires adding a line here. No PSR-4 autoloading despite having namespaces (`App\Controllers`, `App\Models`, etc.).
**Action:**
- Add Composer with PSR-4 autoloading (`composer.json` → `autoload.psr-4: {"App\\": "src/"}`)
- Replace all `require_once` with a single `require __DIR__ . '/../vendor/autoload.php'`
- Run `composer dump-autoload`

### 3. Router is a giant if/elseif chain
**File:** `backend/public/index.php` (lines 70–220+)
**Problem:** ~150 lines of `if/elseif` with regex pattern matching. Adding a new endpoint requires editing this monolith. All controllers are instantiated on every request regardless of which endpoint is hit.
**Action:**
- Extract routing into a `Router` class in `src/Core/Router.php`
- Implement route registration: `$router->get('/api/stations', [StationController::class, 'index'])`
- Lazy-instantiate controllers only when matched
- Support route parameters: `$router->get('/api/stations/{id}', [StationController::class, 'show'])`

### 4. Security: display_errors enabled in production
**File:** `backend/public/index.php`, line 7
**Problem:** `ini_set('display_errors', 1)` exposes stack traces, file paths, and DB credentials in error responses.
**Action:**
- Make error display conditional: `ini_set('display_errors', env('APP_DEBUG', '0'))`
- Add `APP_DEBUG=0` to production `.env`
- Ensure error messages in catch blocks don't expose internals in production (currently `$e->getMessage()` is sent to clients everywhere)

### 5. No authentication / authorization
**Problem:** All 31 API endpoints are publicly accessible. Anyone can read station data, fuel levels, supplier info, and financial data.
**Action:**
- Implement API key middleware (simplest: check `X-API-Key` header against env variable)
- Or implement JWT auth with login endpoint
- Add middleware layer before controller dispatch
- At minimum, add CORS restrictions (currently allows all origins)

---

## ⚠️ IMPORTANT IMPROVEMENTS

### 6. N+1 query problem in ForecastService
**File:** `backend/src/Services/ForecastService.php` → `getDepotForecast()`
**Problem:** Calls `getDaysUntilEmpty()` in a loop for each tank. Each call makes 3 separate DB queries (tank data, sales_params, stock_policies). For a depot with 10 tanks = 30 queries.
**Action:**
- Create a bulk method that fetches all tanks + sales_params + policies in one JOIN query
- Refactor `getDepotForecast()` to use the bulk result and calculate in PHP

### 7. AlertService generates ALL alerts every time
**File:** `backend/src/Services/AlertService.php`
**Problem:** `getActiveAlerts()` runs 4 separate heavy queries every call. `getAlertSummary()` calls `getActiveAlerts()` then counts. `getDashboardSummary()` in ReportService calls both `getAlertSummary()` AND `getCriticalTanks()` — which runs yet another heavy query.
**Action:**
- Add simple in-memory or file-based caching (even 60-second TTL helps)
- Or refactor `getAlertSummary()` to use COUNT queries instead of fetching all alerts
- Consider making `getDepotAlerts()` filter at SQL level, not PHP `array_filter` on full dataset

### 8. Duplicate try/catch in every controller method
**Files:** All controllers
**Problem:** Every single controller method wraps logic in identical try/catch → `Response::json(['error' => ...], 500)`. This is ~80 lines of duplicated error handling across 31 endpoints.
**Action:**
- Add global exception handler in `index.php` (or Router)
- `set_exception_handler()` to catch unhandled exceptions
- Remove try/catch from controllers, let exceptions bubble up
- Controllers become clean one-liners

### 9. SQL query duplication in Models
**Files:** `Station.php`, `Order.php`, `Transfer.php`, `DepotTank.php`
**Problem:** Large SELECT queries with JOINs are copy-pasted across methods in the same model. For example, `Transfer::all()`, `Transfer::find()`, `Transfer::getByStation()`, `Transfer::getByStatus()` all have nearly identical SELECT columns with different WHERE clauses.
**Action:**
- Extract base SELECT into a private method or constant
- Use query builder pattern: `self::baseQuery()->where('status = ?', [$status])`
- Or at minimum, extract column lists into class constants

### 10. Input validation missing
**Files:** All controllers that accept `$_GET` parameters
**Problem:** `DashboardController::forecast()` reads `$_GET['days']` with basic `(int)` cast but no bounds checking. `OrderController::recent()` does the same. No validation for negative numbers, huge values, or SQL injection via non-numeric route params.
**Action:**
- Create `src/Utils/Validator.php` with methods: `required()`, `integer()`, `inRange()`, `inEnum()`
- Validate all inputs before passing to models/services
- Return 400 Bad Request for invalid input

### 11. CORS configuration is missing/incomplete
**File:** `backend/public/index.php`
**Problem:** OPTIONS handler returns 200 but doesn't set CORS headers. The `config/database.php` or a separate CORS config should set `Access-Control-Allow-Origin`, `Allow-Methods`, `Allow-Headers`.
**Action:**
- Check where CORS headers are set (possibly in `.htaccess` or `Response.php`)
- Ensure proper CORS headers for production domain
- Restrict to `https://fuel.kittykat.tech` in production

---

## 🧹 CLEANUP TASKS

### 12. Remove dev artifacts from frontend
**Files to remove/clean:**
- `frontend/src/components/HelloWorld.vue` — Vite scaffold leftover
- `frontend/src/components/StationFillLevels.vue.backup` — backup file
- `frontend/dev.log` — development log
- `frontend/src/assets/rev2-styles.css` — legacy REV 2 styles (verify not used)

### 13. Hardcoded paths in frontend
**File:** `frontend/src/views/Dashboard.vue`
**Problem:** Background image path hardcoded to `/rev3/frontend/dist/truck_header.jpg`. Router base path hardcoded to `/rev3/frontend/dist/`.
**Action:**
- Move image to `public/` folder, reference as `/truck_header.jpg`
- Use Vite's `import.meta.env.BASE_URL` for dynamic base paths
- Or use `vite.config.js` base setting consistently

### 14. .env file present in project root
**File:** `.env` in project root
**Problem:** `.gitignore` lists `.env` but verify it's actually excluded from git. The `.env` file contains database credentials.
**Action:**
- Run `git status` to verify `.env` is not tracked
- If tracked, remove from git: `git rm --cached .env`
- Verify `.env.example` has placeholder values (it exists already ✓)

### 15. Migration comments in Russian
**File:** `database/migrations/001_create_core_tables.sql`
**Problem:** Despite "ENGLISH ONLY" development principle, SQL migration comments are in Russian (`-- СПРАВОЧНЫЕ ТАБЛИЦЫ`, `-- Регионы`, `COMMENT 'Плотность в кг/л'`). Column COMMENTs in Russian will appear in DB tooling.
**Action:**
- Translate all SQL comments to English
- Translate column COMMENT strings to English
- Keep section headers in English

### 16. DepotTank::updateStock() has schema mismatch
**File:** `backend/src/Models/DepotTank.php` → `updateStock()`
**Problem:** Inserts into `stock_audit` with field `change_type` but the migration defines the column as `change_reason` (ENUM type). This will throw a SQL error at runtime.
**Action:**
- Fix column name to `change_reason` in the INSERT statement
- Ensure the value matches the ENUM: `'order', 'transfer', 'adjustment', 'consumption', 'manual'`
- Also the trigger already logs to `stock_audit` automatically — so `updateStock()` creates a DUPLICATE audit entry. Decide: use trigger OR manual insert, not both.

### 17. UnitConverter has hardcoded densities
**File:** `backend/src/Utils/UnitConverter.php`
**Problem:** `getStandardDensities()` has hardcoded density values while `fuel_types` table has a `density` column. This violates the "NO HARDCODE" principle and creates two sources of truth.
**Action:**
- Remove hardcoded densities from UnitConverter
- Always fetch density from `fuel_types.density` via the database
- Or at minimum, add a note that these are fallback defaults only

---

## 🚀 FEATURE IMPROVEMENTS

### 18. Add pagination to list endpoints
**Problem:** `Station::all()`, `Order::all()`, `Transfer::all()` return ALL records. As data grows, these become slow and return massive JSON payloads.
**Action:**
- Add `?page=1&per_page=50` support
- Add SQL `LIMIT ? OFFSET ?`
- Return pagination metadata: `{data: [...], pagination: {page, per_page, total, total_pages}}`

### 19. Add write endpoints (POST/PUT/DELETE)
**Problem:** The API is read-only (all GET). Orders, transfers, and stock updates can only be managed via direct DB access.
**Action:**
- Add `POST /api/orders` — create order
- Add `PUT /api/orders/{id}/status` — update order status
- Add `POST /api/transfers` — create transfer
- Add `PUT /api/depot-tanks/{id}/stock` — update stock level
- With proper validation and audit logging

### 20. OptimusAI component — verify Claude API integration
**File:** `frontend/src/components/OptimusAI.vue`
**Action:**
- Review the full component (only first 60 lines examined)
- Verify Claude API key is not exposed in frontend code
- Ensure API calls go through backend proxy, not directly from browser
- Check error handling and rate limiting

---

## 📋 VERIFICATION CHECKLIST

After making changes, verify:

1. [ ] All API endpoints still return correct data (run `backend/test-api.sh`)
2. [ ] `CostAnalysisService` works with refactored Database calls
3. [ ] `DepotTank::updateStock()` correctly writes to `stock_audit` (fix column name)
4. [ ] No duplicate audit entries (trigger + manual insert conflict)
5. [ ] Frontend builds successfully: `cd frontend && npm run build`
6. [ ] `.env` is NOT in git history
7. [ ] CORS headers work from production frontend domain
8. [ ] Error responses don't leak stack traces in production

---

## 📁 FILE STRUCTURE REFERENCE

```
REV 3.0/
├── backend/
│   ├── config/database.php          # ENV loader + env() helper
│   ├── public/index.php             # Entry point + router (NEEDS REFACTOR)
│   ├── src/
│   │   ├── Core/
│   │   │   ├── Database.php         # PDO singleton + query helpers
│   │   │   └── Response.php         # JSON response helper
│   │   ├── Controllers/ (10)        # HTTP handlers
│   │   ├── Models/ (7)              # Data access layer
│   │   ├── Services/ (6)            # Business logic
│   │   └── Utils/UnitConverter.php  # Unit conversion
│   └── tests/
├── frontend/
│   ├── src/
│   │   ├── components/ (18)         # Vue components
│   │   ├── views/ (6)               # Page views
│   │   ├── services/api.js          # Axios API client
│   │   └── router/index.js          # Vue Router
│   └── dist/                        # Built output
├── database/migrations/             # SQL schema
└── docs/                            # Documentation
```

---

## PRIORITY ORDER

**Do these in order:**
1. Fix `DepotTank::updateStock()` column name bug (schema mismatch) — runtime error
2. Fix `CostAnalysisService` to use consistent Database pattern
3. Disable `display_errors` in production
4. Add Composer autoloader (replaces require_once chain)
5. Extract Router class (replaces if/elseif chain)
6. Add centralized exception handler (removes try/catch duplication)
7. Add input validation
8. Fix N+1 queries in ForecastService
9. Add API authentication
10. Cleanup dev artifacts
