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
1. ~~**Crisis Resolution System**~~ ✅ DONE — see Session Log 2026-02-27
2. **Stock Policies data** — migration 004 ready, needs to run on server; then UI shows real thresholds
3. **How It Works page** — `/how-it-works` route; per-widget explanation cards with screenshots; content drafted in PROJECT.md § "How It Works — Content Draft" below

### 🟠 MEDIUM
4. **ERP Status Transitions** — quick-action buttons in ERP table (confirmed→in_transit→delivered) without opening full Edit modal
5. **Crisis case badge on Immediate Action cards** — if active crisis case exists for this depot+fuel, show "🔄 Crisis Case #N — in progress" banner (link to Cases tab)
6. **Transfers Module full UI** — table + create modal + status transitions
7. **Import Auto-sync from ERP** — "Sync from ERP" button → `POST /api/import/sync-erp`

### 🟡 LOW / BACKLOG
8. **+ Add fuel type to supplier offer** — button on supplier card in Parameters > Supply Offers
9. **PO Expiry warnings on Dashboard** — alert chip for expired POs
10. **Alamedyn delivery days** — correct values needed from client (currently copied from Bishkek)
11. **Market fuel price feed** — auto-fetch from СПбМТСБ / Platts; used in Procurement Advisor + Working Capital
12. **User authentication** (login/roles)
13. **Full test suite** (PHPUnit + integration)
14. **Python optimizer** (advanced procurement math)

---

## Crisis Resolution System — Feature Spec (2026-02-25)

> **Status: DESIGNED, NOT YET IMPLEMENTED**
> Full implementation guide in `memory/MEMORY.md` § "Crisis Resolution"

### Purpose

When a depot reaches CATASTROPHE urgency (no regular delivery can arrive before critical date),
the system proposes intelligent resolutions instead of just showing "Escalate".
System only **proposes** — humans decide and execute.

### Two Resolution Types

#### Type 1: Split In-Transit Delivery (preferred)
A sibling depot at the same station has an ERP order `in_transit` that:
- Has the same `fuel_type`
- Will arrive **before** the critical depot's `critical_level_date`
- Has enough quantity that the donor can spare some and still survive

The system proposes redirecting part of that delivery to the critical depot.

#### Type 2: Transfer from Sibling Depot (fallback)
No in-transit delivery available, but a sibling depot has surplus stock it can safely give away.
Max safe transfer = `donor.current_stock - donor.min_safe_stock_until_next_delivery`

### User Flow (5 steps)

```
1. PROPOSAL    — Immediate Action card shows "💡 Split Delivery Available" or "↔ Transfer Available"
                 with key numbers (how much, from where, arrives when)

2. REVIEW      — User clicks "Review" → opens CrisisResolutionModal with full impact breakdown:
                 • Receiving depot: before/after stock levels
                 • Donor depot: before/after stock levels, safety check
                 • Suggested split qty (pre-calculated)
                 User can adjust qty within safe range

3. ACCEPT      — User clicks "Accept Proposal" → system:
                 a. Creates crisis_cases record (status: accepted)
                 b. Appends note to donor ERP order: "Split: Xt → [DepotName], Case #ID"
                 c. Shows Step 4

4. TWO POs     — System presents 2 pre-filled PO creation modals in sequence:

   PO #1 (for CRITICAL depot — immediate):
     qty = target_level_tons - current_stock_tons - split_received_tons
     label: "Emergency restocking after delivery split"
     urgency: HIGH — order ASAP

   PO #2 (for DONOR depot — compensating):
     qty = split_qty_tons
     label: "Compensating order after delivery split, Case #ID"
     urgency: MEDIUM — order within normal lead time

   Both are pre-filled; user reviews and confirms each.
   Either can be skipped (but system warns if skipped).
   If depot already has an active PO → offer to increase qty instead.

5. MONITORING  — Case moves to "📊 Cases" tab (new 5th tab in Procurement Advisor)
                 Status: monitoring
                 Resolved manually when situation is handled
```

### Cases Tab (new 5th tab)

Columns: Case# | Type | Critical Depot | Donor | Split Qty | Status | Compensating POs | Date | Actions

Statuses:
- `proposed`   — system found an option, not yet reviewed by user
- `accepted`   — user accepted, actions taken
- `monitoring` — tracking until resolved
- `resolved`   — marked done by user (archived, shown collapsed)

### DB Schema — `crisis_cases` table

```sql
CREATE TABLE crisis_cases (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  case_type             ENUM('split_delivery','transfer') NOT NULL,
  status                ENUM('proposed','accepted','monitoring','resolved') DEFAULT 'proposed',

  -- Receiving (critical) depot
  receiving_depot_id    INT NOT NULL,
  fuel_type_id          INT NOT NULL,
  qty_needed_tons       DECIMAL(10,2),

  -- Donor
  donor_order_id        INT NULL,          -- ERP order being split (split_delivery)
  donor_depot_id        INT NULL,          -- depot giving stock (transfer)
  split_qty_tons        DECIMAL(10,2),

  -- Compensating orders (created in step 4)
  po_for_critical_id    INT NULL,          -- PO #1 for critical depot
  po_for_donor_id       INT NULL,          -- PO #2 for donor depot

  notes                 TEXT,
  created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at           TIMESTAMP NULL,

  FOREIGN KEY (receiving_depot_id) REFERENCES depots(id),
  FOREIGN KEY (fuel_type_id)       REFERENCES fuel_types(id),
  FOREIGN KEY (donor_order_id)     REFERENCES orders(id),
  FOREIGN KEY (donor_depot_id)     REFERENCES depots(id),
  FOREIGN KEY (po_for_critical_id) REFERENCES orders(id),
  FOREIGN KEY (po_for_donor_id)    REFERENCES orders(id)
);
```

### Calculation Formulas

> These formulas are used in `CrisisResolutionService.php` and explain the logic to clients.

#### 1. Does the split delivery arrive in time?

```
Condition: donor_order.delivery_date  <  critical_depot.critical_level_date

Where:
  critical_level_date = TODAY + days_until_critical_level
  days_until_critical_level = (current_stock_liters - critical_threshold_liters)
                              ÷ daily_consumption_liters_per_day
```

#### 2. How much can the donor safely give away? (max_split)

The donor must survive on its own stock until its (now partially-redirected) delivery arrives,
plus a safety buffer in case delivery is delayed:

```
donor_days_to_delivery  =  donor_order.delivery_date  −  TODAY           [days]

donor_min_safe_liters   =  donor.daily_consumption
                         × (donor_days_to_delivery + DELIVERY_BUFFER_DAYS)
                                                       ↑ 15 days (spec §3.2)

donor_spare_liters      =  donor.current_stock_liters  −  donor_min_safe_liters

max_split_tons          =  max(0,  donor_spare_liters) ÷ 1000 × fuel_type.density
```

If `max_split_tons = 0` → this donor cannot help without risking its own stockout.

#### 3. How much does the critical depot need?

The goal is to bring the critical depot to its **target level**, not just barely above critical:

```
qty_needed_liters  =  target_level_liters  −  critical_depot.current_stock_liters

target_level_liters  =  tank.capacity_liters × target_threshold_pct ÷ 100

qty_needed_tons  =  qty_needed_liters ÷ 1000 × fuel_type.density
```

#### 4. Optimal split quantity

```
split_qty_tons  =  min(max_split_tons,  qty_needed_tons)
```

If one donor isn't enough, aggregate from multiple donors:
```
split_qty_tons  =  min(Σ max_split_tons[all eligible donors],  qty_needed_tons)
```

#### 5. Two compensating Purchase Orders (created in Step 4 of user flow)

```
PO #1 — Emergency restock for CRITICAL depot:
  qty  =  roundUpTons(qty_needed_tons  −  split_qty_tons)
  Note: covers the gap between what the split provides and the target level

PO #2 — Compensation for DONOR depot:
  qty  =  roundUpTons(split_qty_tons)
  Note: replaces exactly the volume diverted from donor's incoming delivery
```

`roundUpTons()` rounds to the nearest sensible order unit:
- < 50 t → round to nearest 5 t
- 50–200 t → round to nearest 10 t
- 200–500 t → round to nearest 25 t
- > 500 t → round to nearest 50 t

#### 6. Validation: donor remains safe after the split

After accepting the proposal, verify:
```
donor_stock_after_split     =  donor.current_stock_liters  −  split_liters
donor_stock_after_delivery  =  donor_stock_after_split  +  (order.quantity_liters − split_liters)

Must hold:
  donor_stock_after_split     >  donor.critical_threshold_liters     ✓ or ✗
  donor_stock_after_delivery  >  donor.min_threshold_liters          ✓ or ✗
```

Both conditions must be ✓. If either fails → reduce split_qty or disqualify this donor.

### Backend: Split Opportunity Detection

Endpoint: `GET /api/crisis/options?depot_id=X&fuel_type_id=Y`

For **split_delivery** — finds eligible sibling ERP orders:
```sql
SELECT o.*, d.name AS depot_name, d.id AS donor_depot_id,
       sp.liters_per_day AS donor_daily_consumption
FROM orders o
JOIN depots d ON o.depot_id = d.id
LEFT JOIN sales_params sp ON sp.depot_id = o.depot_id
                          AND sp.fuel_type_id = o.fuel_type_id
WHERE d.station_id = (SELECT station_id FROM depots WHERE id = :depot_id)
  AND d.id != :depot_id
  AND o.fuel_type_id = :fuel_type_id
  AND o.order_type = 'erp_order'
  AND o.status = 'in_transit'
  AND o.delivery_date <= :critical_level_date   -- arrives in time
ORDER BY o.delivery_date ASC
```

Then apply formulas above in PHP to compute `max_split_tons` per candidate.
Return only candidates where `max_split_tons > 0`.

### Safeguards
- Never propose split that would push donor below `critical_threshold` (validation formula #6)
- If no eligible options found → show only "Escalate" button (no false promises)
- Both POs are optional at Step 4 (user can skip) but system warns with amber alert
- `Order::findActivePO(depot_id, fuel_type_id)` checked before each PO — offer to increase existing qty instead of creating a duplicate

---

## Session Log

### 2026-02-27 — Crisis Resolution System (full implementation)

**Commits:** `7422145`, `27ae35b`, `5c733c2`, `3691ddc`

#### What was built
- **DB migration** `009_crisis_cases.sql` — table `crisis_cases` (split_delivery|transfer, proposed→accepted→monitoring→resolved)
- **Backend:** `CrisisResolutionService.php` (findOptions, acceptSplitDelivery, acceptTransfer, linkCompensatingPO, getCases, resolveCase, roundUpTons)
- **Backend:** `CrisisController.php` — 5 endpoints: `/api/crisis/options`, `/api/crisis/accept`, `/api/crisis/link-po`, `/api/crisis/cases`, `/api/crisis/cases/{id}/resolve`
- **Backend:** `CrisisCase.php` model (findAll, findById, create, update, resolve, countActive)
- **Frontend:** `CrisisResolutionModal.vue` — 5-step wizard (Options → Confirm → PO #1 → PO #2 → Done)
- **Frontend:** `ProcurementAdvisor.vue` — "Resolve Crisis" button on CATASTROPHE cards, Cases tab (list + resolve)
- **Frontend:** `Orders.vue` — reads `crisis_case_id` + `crisis_po_role` query params, auto-links created PO to case
- **Frontend:** `api.js` — `crisisApi` export

#### Bugs fixed during session
1. **`depot_id` missing from `recommendations` computed** — was undefined → API got null depot_id → always returned empty options
2. **Station constraint too narrow** — SQL searched only same-station donors; removed; now all depots eligible
3. **Stale split-delivery dates** — no `>= CURDATE()` filter; past in_transit orders showed as options
4. **Misleading green border** — CATASTROPHE cards with `po_pending` showed green (implies safe); changed to amber "⚠️ PO EXISTS — arrives too late"
5. **Step 3 shows 0.0 t** — when split fully covers shortage, PO qty = 0; now shows ✅ green confirmation "Split fully covers the shortage" + Next button

#### Key design decisions
- System **proposes; humans decide** — no automatic stock changes, just tracking
- Donor search: cross-station (region-wide), all depots eligible
- `delivery_date >= CURDATE()` filter ensures only future deliveries shown as split candidates
- Cases remain in Immediate Action until stock actually improves (delivery marked `delivered`)
- PO creation reuses existing Orders.vue form via router.push query params

---

### 2026-02-25 — InfrastructureService critical bug fix
- **Root cause:** `InfrastructureService` called `Database::execute()` and `Database::lastInsertId()` — neither method exists in `Database.php`
- **All 4 write operations were broken** (500 on any save in Infrastructure tab):
  - `updateStation`, `updateDepot`, `updateTank` → `Database::execute()` → `Database::query()->rowCount()`
  - `addTank` → `Database::execute()` → `Database::query()` + `getConnection()->lastInsertId()`
- Tank capacity edit now works (user was trying to fix BiMM A-80: 312,595 → 1,312,595 L)

### 2026-02-25 — Urgency Logic Bug + Spec Review (Fix #5 diagnosed — NOT YET FIXED)
- **Root cause:** Urgency based on fill% vs thresholds, NOT time. Items at 33% fill show CRITICAL even if 44 days remain before threshold breach.
- **Correct formula** (from spec §4.5.2 + §3.2): `daysToAct = days_until_critical - (delivery_days + delivery_buffer_days)` where `delivery_buffer_days = 15` per spec
- **Zero-consumption bug:** Backend returns `0.0` for `days_until_critical_level` when consumption = 0. Frontend `?? s.days_left` fallback then shows days-until-empty (44/47 days) as if it were days-to-critical. Fix: return `null` when consumption = 0; remove fallback.
- **`procurement_too_late` field** to add: `lastOrderDate < today` → delivery cannot arrive before critical date → CATASTROPHE mode
- **Two operational modes** confirmed by spec §4.4.4: (1) PROACTIVE PLANNING — prevent stockouts with normal POs; (2) CRISIS (CATASTROPHE) — delivery too late, needs transfers/escalation, NO "Create PO" button
- **Spec reviewed:** `fuel_planning_system_functional_spec_final_draft.pdf` + `fuel_planning_implementation_qa_Claude_v3.pdf` — key findings captured in MEMORY.md "Spec Key Findings" section
- **Full Fix #5 spec in MEMORY.md** — backend code + frontend splits + urgency thresholds

### 2026-02-25 — 4 Bugs Diagnosed (NOT YET FIXED — pending for Cursor/next session)
- **Fix 1 (CRITICAL):** `fuel_type_id` is missing from `recommendations` computed map in `ProcurementAdvisor.vue`
  - `rec.fuel_type_id` = undefined → URL param absent → Create PO modal shows "Select fuel type…" instead of pre-filling
  - Fix: add `fuel_type_id: s.fuel_type_id,` inside the `.map(s => ({...}))` block, after `depot_name`
- **Fix 2:** Briefing KPI "Recommended Orders = 0" is misleading (all items are CATASTROPHE/CRITICAL or PLANNED)
  - Fix: add `mandatoryCount`/`actSoonCount`/`plannedCount` computed refs; replace 2-card grid with 3-card urgency grid
- **Fix 3:** Create PO modal shows plain `<select>` with all suppliers, not filtered by station+fuel
  - Fix: add `availableSupplierOffers` computed (filter by station_id + fuel_type_id, sort by composite score); replace `<select>` with visual radio-card picker showing price + delivery days + BEST badge; auto-watch to select best
- **Fix 4:** Recommendation cards look identical whether or not a PO already exists
  - Fix: when `rec.po_pending === true` → `border-green-400 bg-green-50/40` instead of urgency-based border + `bg-white`

### 2026-02-25 — Procurement Advisor Round 2 + Inline PO Management
- Backend: `days_until_critical_level` (days until stock hits critical threshold %, not empty date), `critical_level_date`, `thresholds_pct`
- Frontend: compact 3-column card grid (`grid-cols-1 md:grid-cols-2 xl:grid-cols-3`), "to crit." days hero, clickable Briefing KPI cards
- Stock level bar with colored threshold zones (uses `thresholds_pct` from API, fallback 20/40/80/95)
- Timeline label: "Upcoming — Next 14 Days" → "Stock hits critical level — next 14 days" (was misleading)
- Inline Remove PO on recommendation cards: 2-step confirm → `DELETE /api/orders/{id}` → `loadData()` (recalculate)
- `loadData()` extracted as reusable fn; `confirmRemovePO()` uses `ordersApi.delete()`
- "Create Purchase Order" → `router.push('/orders', { query: { action:'create_po', station_id, fuel_type_id, quantity_tons, supplier_id, delivery_date } })`
- Orders.vue onMounted reads query params and pre-fills + auto-opens Create PO modal
- Backend already has `Order::findActivePO(station_id, fuel_type_id)` — returns first planned PO with future delivery date

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

---

## How It Works — Content Draft

> **Status:** Draft. To be turned into a proper `/how-it-works` page (Vue view + route).
> Each section = one widget/module. Use as copy for the page.

---

### 📊 Dashboard

The main overview page. Shows the current state of all 9 stations at a glance.

**KPI Cards (top row)**
- Total stations, active tanks, total stock in tons, combined capacity utilisation %

**Forecast Chart**
- Plots projected stock level over the next N days for a selected station + fuel type
- Takes into account: current stock, daily consumption rate (`sales_params.liters_per_day`), and confirmed/in-transit ERP orders (which add stock on their delivery date)
- Purchase Orders (PO) are NOT included in the forecast — they are plans, not confirmed deliveries

**Analytics Widgets**
- Utilisation heatmap, top consumers, stock distribution across stations

---

### 🧠 Procurement Advisor (Dashboard widget)

AI-powered procurement planning. Analyses all 9 stations and surfaces items needing attention.

**How urgency is calculated**
The system doesn't look at fill % alone. It calculates how many days remain before stock falls below the critical threshold, then subtracts delivery time + a 15-day safety buffer:
```
daysToAct = days_until_critical_level − (supplier_delivery_days + 15)
```
| daysToAct | Urgency |
|-----------|---------|
| < 0 | CATASTROPHE — delivery cannot arrive in time |
| < 3 | CRITICAL — order today |
| < 7 | MUST ORDER — order this week |
| < 21 | WARNING — order within 3 weeks |
| ≥ 21 | PLANNED — comfortable buffer |

**Tab: Briefing**
Summary of the situation: how many items are Mandatory (CATASTROPHE+CRITICAL), Act Soon (MUST+WARNING), and Planned. 14-day timeline shows when each depot hits critical level.

**Tab: Immediate Action**
CATASTROPHE items only — situations where no regular delivery can arrive in time. Each card shows:
- Days until critical level, stock needed to reach target
- Best supplier + estimated delivery time
- Existing PO if any (shown in amber: "PO EXISTS — arrives too late")
- **Resolve Crisis** button → opens the Crisis Resolution wizard

**Tab: Proactive Planning**
Items where there's still time to place a regular order. Each card shows urgency, stock bar with threshold zones, recommended order quantity, best supplier. **Create Purchase Order** button opens the Orders form pre-filled.

**Tab: Cases**
Active crisis cases — situations that have been acknowledged and are being tracked. Shows: case type (Split Delivery / Transfer), status (accepted → monitoring → resolved), linked POs, depots involved. Cases stay open until manually marked resolved. Cards remain in Immediate Action until actual stock improves (delivery marked `delivered`).

**Tab: Price Check** *(placeholder — market price feed not yet implemented)*

---

### 🚨 Crisis Resolution (modal, opened from Immediate Action)

A 5-step wizard for handling CATASTROPHE situations where regular ordering is too slow.

**Why it exists:** When `daysToAct < 0`, a new order from a supplier won't arrive before the critical date. Two alternatives exist:
1. **Split Delivery (preferred):** A neighbouring depot has an in-transit ERP order arriving soon. Part of that delivery is redirected to the critical depot. Two compensating POs are created to "repay" the donor.
2. **Transfer from Sibling Depot (fallback):** A neighbouring depot has surplus stock above its safe minimum. Stock is physically moved (tanker truck). Compensating PO created for donor.

**Step 1 — Options:** System queries all other depots for eligible split/transfer candidates. For split delivery, only orders with `delivery_date >= today AND delivery_date <= critical_level_date` are shown. Donor safety is validated: donor must remain above critical threshold after giving, and above min threshold after its own compensating delivery arrives.

**Step 2 — Confirm:** Shows impact table (before/after stocks for both depots). Quantity is adjustable up to the safe maximum. ⚠️ This is a proposal — user must contact the supplier separately to actually redirect the delivery.

**Step 3 — PO for Critical Depot:** If split doesn't fully cover the shortage, a top-up PO is needed. If split covers 100%, this step shows a green "fully covered" confirmation and skips to Step 4.

**Step 4 — Compensating PO for Donor:** Donor gave X tons → a PO for X tons is created for the donor depot to replace what was redirected.

**Step 5 — Done:** Summary of all actions taken. Case is now visible in the Cases tab.

---

### 📋 Orders

Two tabs: **Purchase Orders (PO)** and **ERP Orders**.

**Purchase Orders** are internal plans — "we intend to buy X tons". Status flow: `planned → matched | expired | cancelled`. They appear in Procurement Advisor recommendations and are shown on crisis cards. They do NOT affect the Forecast chart.

**ERP Orders** are confirmed external deliveries from the supplier's ERP system. Status flow: `confirmed → in_transit → delivered | cancelled`. Only ERP orders affect the Forecast chart (because they represent real incoming stock).

**PDF Export:** Each PO can be exported as a formatted PDF (Cyrillic supported via embedded Roboto font).

---

### ⚙️ Parameters

Configuration for the planning system.

- **Infrastructure:** Edit station names, depot names, tank capacities (in liters)
- **Fuel Types:** Density (kg/L) — critical for tons↔liters conversions throughout the system
- **Sales Params:** Daily consumption per depot per fuel type (`liters_per_day`) — drives all forecasts and urgency calculations
- **Stock Policies:** Critical / Min / Target / Max thresholds per depot per fuel type (as % of capacity). Defaults: 20% / 40% / 80% / 95%.
- **Supply Offers:** Price per ton + delivery days per supplier per station per fuel type. Used by Procurement Advisor to rank suppliers and calculate recommended order dates.

---

### 🔄 Transfers *(in development)*

Manual stock transfers between depots within the system. Backend ready, frontend in progress.

---

### 📥 Import *(in development)*

Sync ERP orders from the external ERP system. Will auto-create/update ERP orders based on supplier data feed.

---

### Units & Conversions

All stock is stored internally in **liters** (`depot_tanks.current_stock_liters`). Orders and prices use **tons** (industry standard). Conversion always uses the fuel type's density:
```
tons = liters × density / 1000
liters = tons × 1000 / density
```
Density values are in `fuel_types.density` (kg/L). Never hardcoded — always fetched from DB.
