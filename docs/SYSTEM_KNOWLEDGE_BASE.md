# System Knowledge Base — Fuel Planning System REV 3.0
> Comprehensive documentation of system architecture, data flows, modules, and calculations.
> Updated: 2026-02-18

---

## Table of Contents
1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [Database Schema](#database-schema)
4. [Backend Modules](#backend-modules)
5. [Frontend Modules](#frontend-modules)
6. [Key Calculations Explained](#key-calculations-explained)
7. [Data Migration History](#data-migration-history)
8. [Deployment Process](#deployment-process)
9. [Change Log](#change-log)

---

## 1. Overview

A web-based fuel planning and procurement management system for railway fuel depots across Kyrgyzstan. Tracks fuel stock levels across 9 stations, 11 suppliers, and multiple fuel types. Provides procurement recommendations, consumption forecasting, and stock alerts.

**Live URL:** hosted on shared PHP hosting (no SSH shell, no build step on server)
**Architecture:** Vue 3 SPA (pre-built locally) + PHP 8 REST API + MySQL

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vue 3 + Composition API (`<script setup>`) |
| UI components | Custom: InlineEdit, StatusBadge, AlertBanner |
| HTTP client | Axios |
| Charts | Chart.js via vue-chartjs |
| Backend | PHP 8, no framework (custom Router + Controller pattern) |
| Database | MySQL on shared hosting |
| Deployment | Local `npm run build` → `git add -f frontend/dist/` → push → `update.html` on server |

### Why pre-built dist is committed to git
Server has no Node.js. `frontend/dist/` is force-added to the repository so the server can serve static files directly after a `git pull` triggered by `update.html`.

---

## 3. Database Schema

### Core tables (d105380_fuelv3)

#### `stations`
Railway stations. Each station has one or more depots.
```sql
id | name | code | region_id | created_at
```
IDs used in production: 249=Каинда, 250=Бишкек, 251=Рыбачье, 252=ОШ, 253=Жалал-Абад, 254=Кызыл-Кыя, 255=Шопоков, 256=Аламедин, 257=Токмок

#### `depots`
A fuel storage facility at a station. One station can have multiple depots.
```sql
id | station_id | name | code | created_at
```

#### `depot_tanks`
Individual physical tanks inside a depot. Each tank holds one fuel type.
```sql
id | depot_id | fuel_type_id | tank_number | capacity_liters | current_stock_liters
```
> **current_stock_liters** is the live stock level. Updated when deliveries arrive or consumption is recorded.

#### `fuel_types`
Master list of fuel products.
```sql
id | name | code | density
```
IDs: 24=A-92, 25=Diesel B7, 26=LPG, 27=Jet Fuel, 28=MTBE, 31=A-95, 32=A-98, 33=Diesel B10, 34=A-80, 35=A-92 Euro
**density** (kg/L): used to convert liters ↔ tons for procurement orders.
Formula: `tons = liters × density / 1000`

#### `sales_params`
Daily consumption rate per depot per fuel type. Used for all forecasting.
```sql
id | depot_id | fuel_type_id | liters_per_day | effective_from | effective_to
```
- `liters_per_day`: how many liters this depot consumes daily of this fuel
- `effective_to = NULL` means currently active
- Multiple rows per depot/fuel allowed — the latest active one is used

> **Important:** Unit is LITERS per day (not tons). All forecast math uses liters internally.

#### `stock_policies`
Threshold levels per depot per fuel type that trigger alerts and procurement.
```sql
id | depot_id | fuel_type_id | min_level_liters | critical_level_liters | target_level_liters
```
- `critical_level_liters`: below this = CATASTROPHE alert
- `min_level_liters`: below this = CRITICAL alert, must order immediately
- `target_level_liters`: desired level after a delivery (fill-up target)

#### `suppliers`
Fuel suppliers / refineries.
```sql
id | name | is_active | created_at
```
IDs: 1=ОРСК, 2=Тайф-НК, 3=Нижневартовск, 4=Газпром нефтехим Салават, 5=Пурнефтепеработка, 6=НС Ойл, 7=КрНПЗ, 8=НПЗ Кара май Ойл-Тараз, 9=Актобе нефтепереработка, 10=Лукоил-Пермнефтеоргсинтез, 21=Мозырский НПЗ

#### `supplier_station_offers`
Links suppliers to stations with pricing and delivery time.
```sql
id | supplier_id | station_id | fuel_type_id | price_per_ton | delivery_days | currency | is_active
```
- `price_per_ton`: current offer price in USD/ton — **editable via UI**
- `delivery_days`: transit time from supplier to this specific station — critical for procurement planning
- One row per (supplier, station, fuel_type) combination

> **Why per-station delivery_days?** A refinery in Russia is 15 days to Кызыл-Кыя but 35 days to Рыбачье. Delivery times differ by geography.

#### `system_parameters`
Global configuration values managed via UI.
```sql
parameter_name | parameter_value | data_type | description
```
Examples: planning_horizon_days, safety_stock_days, etc.

---

## 4. Backend Modules

### Architecture: `backend/src/`
```
Controllers/          — HTTP request handlers, thin layer
  DashboardController
  ForecastController
  ParametersController
  ProcurementController
  ReportController
  SupplierController

Services/             — Business logic
  ForecastService       — Consumption projections, days-until-empty
  ParametersService     — CRUD for all config tables
  ProcurementAdvisorService — Procurement recommendations
  AlertService          — Critical/warning stock detection
  RegionalComparisonService — Cross-station analytics

Models/               — Simple DB query wrappers
  Depot.php
  Station.php
  FuelType.php

Core/
  Database.php          — PDO wrapper (fetchAll, fetchOne, execute)
  Router.php            — Route registration and dispatch
```

### API routing (`backend/public/index.php`)
All routes registered in one place. Pattern: `GET /api/resource`, `POST /api/resource/action`.

---

## 5. Frontend Modules

### Architecture: `frontend/src/`
```
views/
  Dashboard.vue         — Overview cards + critical alerts
  Parameters.vue        — All editable config tables (tabs)
  Procurement.vue       — Procurement advisor + order recommendations
  Forecast.vue          — Time series charts of projected fuel levels
  Reports.vue           — Export and analytics
  Suppliers.vue         — Supplier management

components/
  InlineEdit.vue        — Click-to-edit cell component
  StatusBadge.vue       — Color-coded status chip
  AlertBanner.vue       — Warning/error banners

router/index.js         — Vue Router config
store/ or composables/  — Shared state (Axios calls)
```

### Parameters.vue — Tab Structure
| Tab | Data source | Editable fields |
|-----|-------------|-----------------|
| Infrastructure | depots + stations | read-only reference |
| Supply Offers | supplier_station_offers | price_per_ton, delivery_days |
| Sales Params | sales_params | liters_per_day |
| Stock Policies | stock_policies | min/critical/target levels |
| Fuel Types | fuel_types | density only |
| System Parameters | system_parameters | parameter_value |
| Depot Tanks | depot_tanks | read-only reference |

---

## 6. Key Calculations Explained

### 6.1 Days Until Empty
```
days_until_empty = current_stock_liters / liters_per_day
```
Where `liters_per_day` comes from the active `sales_params` row for that depot+fuel_type.

### 6.2 Days Until Minimum / Critical
```
days_until_minimum = (current_stock_liters - min_level_liters) / liters_per_day
days_until_critical = (current_stock_liters - critical_level_liters) / liters_per_day
```
If result ≤ 0, depot is already below threshold.

### 6.3 Urgency Levels
| Condition | Status |
|-----------|--------|
| stock ≤ critical_level | CATASTROPHE |
| stock ≤ min_level | CRITICAL |
| days_until_minimum ≤ 3 | MUST_ORDER |
| otherwise | NORMAL |

### 6.4 Procurement Order Quantity (ProcurementAdvisorService)
**Correct formula (after fix on 2026-02-18):**
```
consumption_during_delivery = liters_per_day × delivery_days
stock_at_arrival = current_stock_liters - consumption_during_delivery
needed_to_reach_target = target_level_liters - stock_at_arrival
order_liters = needed_to_reach_target   (capped at tank capacity)
order_tons = order_liters × density / 1000
```
> The key insight: you must order enough to cover **both** the transit period AND reach the target level on arrival. The naive formula `target - current` was wrong because it ignored consumption during the delivery window.

### 6.5 Liters ↔ Tons Conversion
All DB storage is in **liters**. All procurement orders are in **tons** (industry standard).
```
tons = liters × density / 1000
liters = tons × 1000 / density
```
Density is stored per fuel type in `fuel_types.density` (kg/L).
Example: Diesel B7 density = 0.845 kg/L → 1000 L = 0.845 tons

### 6.6 Forecast Chart (ForecastService::getStationForecast)
Generates time-series data for Chart.js:
```
for each day i from 0 to horizon:
    projected_stock_kL = (current_stock_L - liters_per_day × i) / 1000
    floor at 0 (can't go negative)
```
Data is displayed in **kiloliters (kL)** on the chart Y-axis for readability.

---

## 7. Data Migration History

### 7.1 Old DB: d105380_fueloptimization
The previous system version. Key tables:
- `delivery_times` (supplier_id, destination_station_id, delivery_days)
- `fuel_prices` (supplier_id, fuel_type_id, price_per_ton)
- `suppliers` (id, name, is_active)

### 7.2 New DB: d105380_fuelv3
Merged `delivery_times` + `fuel_prices` into `supplier_station_offers` — one unified table with all supplier-station-fuel combinations.

### 7.3 Supplier Migration (2026-02-18)
**File:** `migration_suppliers.sql`

**What it does:**
1. Inserts 9 missing suppliers (id 1–7, 9, 10, 21) into `suppliers`
2. Inserts all `supplier_station_offers` rows: 11 suppliers × 9 stations × relevant fuel types
3. Uses `ON DUPLICATE KEY UPDATE` — safe to run multiple times

**Station IDs are IDENTICAL** between old and new DBs (249–257).
**Fuel type IDs are IDENTICAL** between old and new DBs (24–35).
This means delivery_days and prices mapped directly without ID translation.

**Аламедин (256)** was not in the old DB — it's a new station added in REV 3.0. Its delivery_days were assigned the same as Бишкек (250) as the nearest geographical approximation.

### 7.4 liters_per_day Revert (previous session)
The `sales_params.liters_per_day` column was briefly renamed to `tons_per_day` during a refactor, then reverted back. All affected files:
- `ForecastService.php` — all 4 methods
- `Depot.php` — `getConsumptionForecast()`
- `ParametersService.php` — `getSalesParams()`, `updateSalesParam()`
- `AlertService.php`, `RegionalComparisonService.php`, `ReportService.php`, `ProcurementAdvisorService.php`
- `Parameters.vue` — Sales Params tab

### 7.5 cost_per_ton Removal from Fuel Types
`fuel_types` table has/had a `cost_per_ton` column that was not used correctly. Decision:
- **Pricing belongs in `supplier_station_offers.price_per_ton`** (per supplier, per station, per fuel type)
- A single price in `fuel_types` makes no sense — prices vary by supplier and station
- Removed `cost_per_ton` from: `Parameters.vue` UI, `ParametersService::getFuelTypes()` SELECT query, `ParametersService::updateFuelType()` UPDATE logic, `ParametersController::updateFuelType()`

---

## 8. Deployment Process

### Local development → production
```bash
# 1. Build frontend
cd frontend && npm run build

# 2. Stage built files (forced — dist/ is in .gitignore normally)
git add -f frontend/dist/

# 3. Commit all changes
git add backend/ frontend/src/
git commit -m "description"

# 4. Push to remote
git push

# 5. Trigger server update
# Open in browser: https://[server]/update.html
# Or: curl https://[server]/update.html
```

### Why `update.html`?
Server has no SSH shell access. `update.html` (or `update.php`) runs `git pull` via PHP `exec()`. This pulls the latest commit including the pre-built `frontend/dist/`.

---

## 9. Change Log

### 2026-02-18 (current session)
- **Reverted** `tons_per_day` → `liters_per_day` across all backend services and frontend
- **Removed** `cost_per_ton` from Fuel Types tab — pricing managed in Supply Offers
- **Generated** `migration_suppliers.sql` — adds 9 missing suppliers + ~400 supplier_station_offers rows

### 2026-02-18 (earlier session)
- **Fixed** procurement calculation: order quantity now accounts for consumption during delivery transit
- **Fixed** capacity overflow: orders capped at available tank capacity
- **Added** insufficient capacity warnings in procurement recommendations

### Previous sessions
- Initial REV 3.0 build: PHP backend, Vue 3 frontend
- Database schema created with all core tables
- Dashboard, Forecast, Parameters, Procurement, Reports views implemented
- Supplier offers UI with inline editing

---

## Notes & Conventions

1. **No Russian in code or UI** — all variable names, comments, API keys, column aliases in English
2. **Units**: DB stores liters, API returns liters for stock/consumption, UI may display tons for procurement
3. **Density**: always stored as kg/L (e.g., 0.845 not 845)
4. **Prices**: always USD/ton in `supplier_station_offers.price_per_ton`
5. **Dates**: `effective_to IS NULL` means currently active (open-ended)
6. **InlineEdit**: click pencil icon → input appears → blur or Enter saves via PATCH API call
