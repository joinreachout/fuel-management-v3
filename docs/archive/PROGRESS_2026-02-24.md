# Project Status & Roadmap

## System Status

**Status:** Live
**URL:** https://fuel.kittykat.tech/rev3/
**Last deploy:** 2026-02-24 (dark hero headers + orders UI overhaul)

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
- Dark hero header (REV 2.0 style) — truck bg, 3-row KPI chips, white card overlaps from below

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

### Orders Module ✅ COMPLETE
- Backend API: full CRUD, status transitions, cancel with reason
- Two order types: **Purchase Orders** (PO) + **ERP Deliveries**
- Status flow PO: `pending → [cancelled]` (PO just created, boss approves, ERP handles rest)
- Status flow ERP: `confirmed → in_transit → delivered → [cancelled]`
- **Orders.vue** — dark hero header + single big white card (stats bar + tabs + filters + table)
- Create PO modal — **quantity in TONS** (converted to liters on submit via fuel density)
- Manual ERP Entry modal — same tons logic
- Print PO (PDF-style print CSS)
- Cancel PO modal with mandatory reason field
- Stats bar: PO counts (Planned/Matched/Expired) + ERP counts (Confirmed/InTransit/Delivered)
- Header KPIs: Total Stations, Below Threshold, Mandatory Orders, Recommended Orders
- **Supplier offer auto-fill**: select station + supplier → delivery days shown, date auto-filled (today + N), price auto-filled from contract

### Parameters Module ✅ COMPLETE
- Dark hero header (same REV 2.0 style as Dashboard + Orders)
- Infrastructure tab — Regions → Stations → Depots → Tanks hierarchy
- Supply Offers tab — supplier cards with inline-editable prices + delivery days
- Sales Params tab — daily consumption per depot/fuel type
- Stock Policies tab — critical/min/target thresholds per depot/fuel type ⚠️ currently 0 records
- Fuel Types tab — density values per fuel type

### Transfers Module
- Depot-to-depot transfers
- Status tracking with progress
- **Frontend UI: stub only** — full implementation pending

### Import Module
- CSV/Excel import for sales data
- Manual ERP Entry (fallback when ERP system unavailable)
- **Auto-sync from ERP: pending**

---

## Architecture Rules (Immutable)

### Units
| Field | Stored as | Displayed/Entered as |
|-------|-----------|---------------------|
| Tank capacity | **litres** | litres |
| Current stock | **litres** | litres |
| Daily consumption (`sales_params.tons_per_day`) | **tons** | tons |
| Order quantity (`orders.quantity_liters`) | **litres** | **tons** (converted via density) |
| Prices (`supplier_station_offers.price_per_ton`) | **USD/ton** | USD/ton |

**Conversion:** `tons = litres × density_kg_per_litre / 1000`
Density always from `fuel_types.density` — never hardcoded.

### Order Types
- **Purchase Order (PO)** — created by user, printed, given to management. After creation, PO has no system control over execution. Only action: Cancel (user error correction).
- **ERP Order** — comes from `erp.kittykat.tech` via Import or Manual Entry. Represents actual physical shipment. Status transitions: `confirmed → in_transit → delivered`.

### Supplier Offers
- Delivery days + contract price per `(supplier, station, fuel_type)` stored in `supplier_station_offers`
- Used in: Parameters UI (edit), Order modals (auto-fill), Procurement Advisor (recommendations)

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

## Session Log

### 2026-02-23 — Orders Module + ERP Manual Entry
- [x] `GET /api/orders/stats` endpoint → `Order::getStatusCounts()`
- [x] Manual ERP Order creation backend + frontend modal
- [x] Stats bar on Orders page (PO + ERP counts)
- [x] Stats bar on Parameters page (infrastructure counts)

### 2026-02-24 — Dark Hero Headers + Orders UX + Tons
- [x] Orders.vue: dark hero header matching Dashboard/REV 2.0 style
  - Truck background image with gradient fade
  - 3 rows: Title+KPIs | KPIs row 2 | datetime chips
  - KPIs: Total Stations / Below Threshold / Mandatory Orders / Recommended Orders / Active Orders
  - Loads `dashboardApi.getSummary()` + `procurementApi.getSummary()` in parallel
- [x] Parameters.vue: same dark hero header with 5 KPI chips (Stations/Fuel Types/Suppliers/Depots/Tanks)
- [x] **Overlap effect** — white content card overlaps bottom of dark header; black header visible on sides (same as Dashboard). Implemented via `pb-24` on header + `relative -mt-16 z-10` on content.
- [x] **Single big white card** — all Orders content (stats bar + tabs + filters + table) merged into one `bg-white rounded-2xl shadow-xl` card
- [x] Action buttons (New PO / Manual Entry) moved to tab bar level
- [x] **Quantity: liters → tons** in both Create PO and Manual ERP modals
  - User enters tons, UI shows litres hint (`≈ N liters`)
  - On submit: `quantity_liters = tons × 1000 / fuel_density` (uses `fuel_types.density`)
  - Total cost = `quantity_tons × price_per_ton` (no density needed)
- [x] **Delivery days auto-fill**: when supplier + station selected, `supplier_station_offers` queried
  - Info chip shown: "Delivery: N days → date auto-filled" + "Price: $X/ton ✓"
  - `delivery_date` auto-set to today + N days
  - `price_per_ton` auto-filled from contract price (if field empty)

---

## Plan for 2026-02-25

### 🔴 HIGH PRIORITY

#### 1. Stock Policies — Fill Data
**Problem:** `stock_policies` table has 0 records. Procurement Advisor and Forecast are running on default fallback values, not real per-station thresholds.
**Fix options:**
- A) SQL seed script — insert Critical/Min/Target thresholds for all 9 stations × 10 fuel types
- B) Parameters UI — add bulk-fill or import button in Stock Policies tab
**Impact:** Unlocks correct operation of Procurement Advisor and all shortage predictions.

#### 2. Best Supplier Selection Widget (Dashboard + PO Modal)
**Concept:** A widget that shows the recommended best supplier for each station/fuel type combination based on:
- Lowest price per ton (from `supplier_station_offers`)
- Shortest delivery days
- Combined score (price × delivery days)
**Locations:**
- Dashboard — new analytics widget "Best Suppliers"
- Dashboard — Procurement Advisor already uses this logic; surface it visually
- Create PO modal — "Recommended supplier" suggestion with score

---

### 🟠 MEDIUM PRIORITY

#### 3. ERP Manual Entry: Status Transitions
**Problem:** Manually created ERP orders get stuck at `confirmed`. No UI to move them through the pipeline.
**Fix:**
- Add status transition buttons in ERP Deliveries table: `confirmed → in_transit` and `in_transit → delivered`
- Add Cancel button for ERP records (with reason)
- Backend: `PUT /api/orders/{id}/status` endpoint (or extend existing update)

#### 4. Import Module: Auto-sync from ERP
**Problem:** Currently only Manual Entry exists. Need automatic sync.
**Fix:**
- "Sync from ERP" button on Import page
- Backend: `POST /api/import/sync-erp` — calls `erp.kittykat.tech` API, pulls new orders, upserts into DB
- Show sync log / last sync time

#### 5. Transfers Module — Full UI
**Problem:** Transfer module exists in backend but frontend is a stub.
**Fix:**
- Transfers.vue — table + create transfer modal + status transitions
- Backend already has transfer endpoints

---

### 🟡 LOW PRIORITY

#### 6. Add Fuel Type to Existing Supplier via UI
**Problem:** Currently no button to add a new fuel type entry to a supplier's offer card in Parameters.
**Fix:** "+ Add fuel type" button on supplier card in Supply Offers tab.

#### 7. PO Expiry Warnings on Dashboard
**Problem:** Expired Purchase Orders (delivery date passed, still `planned`) don't surface visually.
**Fix:** Alert chip on Dashboard for X expired POs, link to Orders page filtered by `expired`.

#### 8. Alamedин Delivery Days
**Problem:** Delivery days for Станция Аламедин are copied from Бишкек data (incorrect).
**Fix:** Update `supplier_station_offers` rows where `station_id = [Аламедин]` with correct days.
**Action:** Confirm correct days with client, then SQL update or Parameters UI edit.

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
