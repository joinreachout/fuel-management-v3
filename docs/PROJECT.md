# Fuel Management System REV 3.0 — Project Reference

> **Living document.** Updated each session. Source of truth for project status, architecture, and roadmap.

**Live:** https://fuel.kittykat.tech/rev3/
**Worktree:** `optimistic-chebyshev` | **Branch:** `main`
**DB:** MySQL `d105380_fuelv3` on shared hosting

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vue 3 + Composition API (`<script setup>`) + Vite + TailwindCSS v4 |
| Charts | Chart.js |
| HTTP client | Axios |
| PDF | jsPDF **v2.5.1** (NOT v4 — see TECH_DECISIONS.md) |
| Backend | PHP 8.1, no framework — custom Router + PDO/MySQL, MVC+Services |
| Database | MySQL 8.0 |
| Deploy | `npm run build` → `git add -f frontend/dist/` → `git push` → `update.html` on server |

> **Why dist is committed:** Server has no Node.js. Pre-built `frontend/dist/` is force-added to git so server can serve static files after `git pull` triggered by `update.html`.

---

## Key Paths

```
backend/public/index.php          — all routes registered here
backend/src/Controllers/          — HTTP request handlers
backend/src/Services/             — business logic
backend/src/Models/               — DB wrappers
backend/src/Utils/UnitConverter.php — liters↔tons conversion
frontend/src/views/               — pages (Dashboard, Orders, Transfers, Parameters, Import)
frontend/src/components/          — shared widgets
frontend/src/services/api.js      — all API calls
frontend/src/utils/robotoBase64.js — pre-encoded Roboto TTF for jsPDF (Cyrillic)
database/migrations/              — SQL migrations (001–008 applied)
docs/PROJECT.md                   — this file
docs/TECH_DECISIONS.md            — architecture decisions & lessons learned
docs/API.md                       — API reference
```

---

## Data Model

### Core tables

| Table | Rows | Notes |
|-------|------|-------|
| `regions` | 3 | Kyrgyzstan regions |
| `stations` | 9 | IDs 249–257 (railway fuel stations) |
| `depots` | 19 | Storage facilities per station |
| `depot_tanks` | 95 | Individual physical tanks; `current_stock_liters` = SOURCE OF TRUTH |
| `fuel_types` | 10 | IDs 24–35; has `density` (kg/L) — critical for tons conversion |
| `suppliers` | 11 | Refineries |
| `supplier_station_offers` | ~400 | Price + delivery days per (supplier, station, fuel_type) |
| `sales_params` | — | `liters_per_day` consumption per depot+fuel_type |
| `stock_policies` | ⚠️ 0 | critical/min/target thresholds — NEEDS DATA |
| `orders` | — | POs + ERP orders; `quantity_liters` stored in liters |
| `transfers` | — | Depot-to-depot transfers |

### Station IDs (memorize)
```
249=Каинда  250=Бишкек  251=Рыбачье  252=ОШ  253=Жалал-Абад
254=Кызыл-Кыя  255=Шопоков  256=Аламедин  257=Токмок
```

### Fuel type IDs (memorize)
```
24=A-92  25=Diesel B7  26=LPG  27=Jet Fuel  28=MTBE
31=A-95  32=A-98  33=Diesel B10  34=A-80  35=A-92 Euro
```

---

## Critical Business Rules

- Stock stored in **liters** (`depot_tanks.current_stock_liters` = SOURCE OF TRUTH)
- Orders/prices in **tons** (industry standard)
- Conversion: `tons = liters × density / 1000` — ALWAYS use `fuel_types.density`, never hardcode
- `UnitConverter::litreToTon()` / `UnitConverter::tonToLitre()` — use these, never inline formulas
- All SQL divisions protected with `NULLIF(x, 0)`
- ENGLISH ONLY in code/comments; Russian allowed only in DB content (station names)
- No hardcoded values, DRY principle, PSR-12

---

## Orders Module

### Order types
- **PO (purchase_order):** Created in UI → printed/sent to management → does NOT affect forecast
  - Statuses: `planned → matched | expired | cancelled`
  - `Order::markExpiredPOs()` runs on each index load
- **ERP (erp_order):** Imported from `erp.kittykat.tech` or manually entered → DOES affect Forecast
  - Statuses: `confirmed → in_transit → delivered | cancelled`

### PO status validation (Order::update)
```
purchase_order allows: planned, matched, expired
erp_order allows:      confirmed, in_transit, delivered
cancelled & delivered: terminal — blocked from further updates
```

---

## What's Built

### ✅ Dashboard
- Fuel Level Forecast chart (30/60/90 days, includes ERP deliveries)
- KPI cards: Total Stations, Shortages Predicted, Mandatory Orders, Active Transfers
- Critical alerts banner
- Filters: Region / Station / Fuel Type / Forecast horizon
- Dark hero header (truck bg + gradient, 3-row KPIs)
- Analytics widgets: Stock by Fuel Type, Station Fill Levels, Top Suppliers, Fuel Distribution, Working Capital, Transfer Activity, Orders Calendar, Procurement Advisor, Risk/Cost/Turnover

### ✅ Orders
- PO + ERP tabs, dark hero header, single white card
- Create PO modal (quantity in tons, auto-converts to liters)
- Manual ERP Entry modal
- Stats bar (PO counts + ERP counts)
- Sortable columns (reactive(), not ref() — see TECH_DECISIONS.md)
- Edit modal (all fields + status change for both PO and ERP)
- Print PO (browser print CSS)
- Download PDF button (REV 2.0 style, Cyrillic via embedded Roboto)
- Cancel PO with mandatory reason
- Supplier offer auto-fill (price + delivery days + date)

### ✅ Parameters
- Dark hero header with infrastructure KPI chips
- Infrastructure tab (Regions → Stations → Depots → Tanks hierarchy)
- Supply Offers tab (inline-editable prices + delivery days per supplier)
- Sales Params tab (liters_per_day per depot/fuel_type)
- Stock Policies tab (critical/min/target thresholds) ⚠️ 0 records
- Fuel Types tab (density values only)

### 🔲 Transfers
- Backend API ready; frontend is a stub — needs full UI

### 🔲 Import
- CSV/Excel import done
- Manual ERP Entry done
- Auto-sync from ERP: pending

---

## Roadmap

### 🔴 HIGH (next up)
1. **Stock Policies data** — migration 004 ready, needs to run on server; then UI shows real thresholds
2. **Best Supplier Selection Widget** — recommend supplier per station/fuel based on price + delivery days; surfaced on Dashboard and in Create PO modal

### 🟠 MEDIUM
3. **ERP Status Transitions** — quick-action buttons in ERP table (confirmed→in_transit→delivered) without opening full Edit modal
4. **Transfers Module full UI** — table + create modal + status transitions
5. **Import Auto-sync from ERP** — "Sync from ERP" button → `POST /api/import/sync-erp`

### 🟡 LOW / BACKLOG
6. **+ Add fuel type to supplier offer** — button on supplier card in Parameters > Supply Offers
7. **PO Expiry warnings on Dashboard** — alert chip for expired POs
8. **Alamedyn delivery days** — correct values needed from client (currently copied from Bishkek)
9. **Market fuel price feed** — auto-fetch from СПбМТСБ / Platts; used in Procurement Advisor + Working Capital
10. **User authentication** (login/roles)
11. **Full test suite** (PHPUnit + integration)
12. **Python optimizer** (advanced procurement math)

---

## Session Log

### 2026-02-25 — Orders UX + PDF
- Sortable columns (reactive() fix)
- Edit modal (all fields + status transitions, both PO and ERP)
- Icon-only action buttons (28×28px row)
- PDF download: jsPDF, REV 2.0 style layout, Cyrillic via embedded Roboto base64
- Fixed "No unicode cmap" bug (font b64 encoding, jsPDF v2.5.1)

### 2026-02-24 — Dark Hero Headers + Tons
- Orders.vue + Parameters.vue: dark hero headers (truck bg, gradient, 3-row KPIs)
- White card overlap effect (`pb-24` + `relative -mt-16 z-10`)
- Quantity input: liters → tons in Create PO and Manual ERP modals
- Supplier auto-fill (delivery days + price from `supplier_station_offers`)

### 2026-02-23 — Orders Module + ERP Manual Entry
- `GET /api/orders/stats` endpoint
- Manual ERP Order creation (backend + frontend modal)
- Stats bar on Orders + Parameters pages

### 2026-02-20 — Parameters Module
- Full Parameters.vue with all tabs
- Supply Offers inline editing
- Stock Policies UI (table exists, 0 data)

### 2026-02-18 — Core Systems
- Reverted `tons_per_day` → `liters_per_day`
- Removed `cost_per_ton` from Fuel Types (pricing in supplier_station_offers)
- Fixed procurement calculation (accounts for consumption during transit)
- Supplier migration: 11 suppliers × 9 stations × fuel types → `supplier_station_offers`
