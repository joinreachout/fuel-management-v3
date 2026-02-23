# Project Status & Roadmap

## System Status

**Status:** Live
**URL:** https://fuel.kittykat.tech/rev3/
**Last deploy:** Feb 2026 (all audit fixes applied)

---

## What's Built

### Core Infrastructure
- PHP REST API — custom router, no framework, raw PDO/MySQL
- Vue 3 SPA — Composition API, `<script setup>`, Vite, TailwindCSS
- MySQL schema — 16 tables (regions, stations, depots, tanks, orders, transfers, suppliers, etc.)
- Deploy pipeline — `npm run build` → `git push` → auto `git pull` on server

### Dashboard
- Fuel Level Forecast chart — station-level, 30/60/90 days, includes scheduled deliveries
- KPI cards — Total Stations, Shortages Predicted, Mandatory Orders, Active Transfers
- Critical alerts banner — stations below threshold
- Filters — Region / Station / Fuel Type / Forecast horizon

### Analytics Widgets
- Stock by Fuel Type (real depot_tanks data)
- Station Fill Levels (real current_stock vs capacity)
- Top Suppliers (from orders + supplier_station_offers)
- Fuel Type Distribution (% of total stock)
- Working Capital Snapshot (stock value, days of cover)
- Transfer Activity (in_transit transfers)
- Orders Calendar (delivery dates from orders table)
- Procurement Advisor (shortage predictions + recommended orders)
- Risk Exposure, Cost Analysis, Inventory Turnover (static analytical views)

### Orders Module
- Backend API: full CRUD, status transitions
- Statuses: pending → confirmed → in_transit → delivered
- Delivery date tracking — feeds forecast chart
- **Frontend UI: not yet implemented** (in progress)

### Transfers Module
- Depot-to-depot transfers
- Status tracking with progress

### Parameters Module (System Config)
- Infrastructure tab — Regions → Stations → Depots → Tanks hierarchy
- Supply Offers tab — supplier cards with inline-editable prices + delivery days
- Sales Params tab — daily consumption (liters/day) per depot/fuel type
- Stock Policies tab — critical/min/target thresholds per depot/fuel type
- Fuel Types tab — density values per fuel type

### Import Module
- CSV/Excel import for sales data

---

## Current Metrics

| Item | Count |
|------|-------|
| Regions | 3 |
| Stations | 9 |
| Depots | 19 |
| Depot Tanks | 95 |
| Fuel Types | 10 |
| Suppliers | 11 |
| DB Tables | 16+ |
| API Endpoints | 40+ |
| Frontend Components | 20+ |
| DB Migrations | 7 |

---

## Next Up — In Progress

### Orders Module (Full UI Implementation + Cancellation Logic)
**Priority:** HIGH — Next task
**Added:** 2026-02
**Spec:** [docs/features/ORDERS_MODULE.md](docs/features/ORDERS_MODULE.md)

**Core concept:**
The forecast chart shows two types of deliveries:
1. **Past deliveries** — already completed (`delivered`), reflected in current stock
2. **Future planned deliveries** — scheduled (`confirmed` / `in_transit`), shown as bumps on forecast

When a planned order gets **cancelled** (broken truck, factory bombed, etc.) — the system must:
- Immediately recalculate the forecast curve (delivery bump disappears)
- Re-evaluate Procurement Advisor recommendations (new shortage risk detected)
- Show reason for cancellation in order history

**Status flow:**
```
pending → confirmed → in_transit → delivered
                  ↘              ↘
                  cancelled      cancelled
                  (+ reason)     (+ reason)
```

**Architecture clarification (2026-02-23):**
- PO = created → printed → given to boss. After that our system has no control over execution.
- ERP (erp.kittykat.tech) sends actual delivery data → Import → updates depot stock
- User only: Create PO, Print PO, Cancel PO (if user error)
- Status transitions `confirmed`/`in_transit`/`delivered` happen via ERP/Import only

**Implementation checklist:**
- [x] DB migration 007: `cancelled_reason`, `cancelled_at` fields
- [x] `Order.php` — create/update/cancel/delete + filters
- [ ] `OrderController.php` — store/update/cancel/destroy
- [ ] `index.php` — 4 new routes
- [ ] `ForecastService.php` — change filter to `NOT IN ('cancelled', 'delivered')`
- [ ] `api.js` — create/update/cancel/delete methods
- [ ] `Orders.vue` — full page: table + filters + badges + print + cancel modal + create form

---

## Backlog — Future Features

### Automatic Fuel Exchange Price Tracking
**Priority:** Medium
**Added:** 2026-02

Integrate with commodity exchange sources to automatically fetch market fuel prices.

**Used in:**
- `ProcurementAdvisor.vue` — replace static `marketPrices` with live exchange rates
- `WorkingCapital.vue` — recalculate stock value at current market price
- `CostAnalysis.vue` — compare purchase prices vs market rates

**Data sources (options):**
- СПбМТСБ (St. Petersburg International Mercantile Exchange) — API or scraping
- Platts / Argus Media — paid API
- ЦДУ ТЭК — open data from Russian Ministry of Energy

**Implementation needed:**
- [ ] DB table `market_prices`: `fuel_type_id`, `price_per_ton`, `price_date`, `source`
- [ ] `backend/src/Services/FuelPriceService.php` — fetch + cache prices
- [ ] `backend/src/Controllers/FuelPriceController.php` — `GET /api/fuel-prices/market`
- [ ] Cron job or manual refresh button in UI
- [ ] Frontend: replace static `marketPrices` in `ProcurementAdvisor.vue` with API data

### Other Backlog Items
- [ ] User authentication (login/roles)
- [ ] Reports & exports (PDF/Excel)
- [ ] Full test suite (unit + integration)
- [ ] Python optimizer integration (advanced procurement calculations)
