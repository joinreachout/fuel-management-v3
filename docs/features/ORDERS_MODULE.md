# Orders Module — Architecture & Specification

> **Status:** ✅ Fully implemented and live (2026-02-23)
> **Migration:** 008_order_type.sql applied to production

---

## Two Order Types — Core Concept

The `orders` table holds **two fundamentally different kinds of records**, distinguished by the `order_type` column:

| Type | `order_type` | Who creates it | Purpose |
|------|-------------|----------------|---------|
| Purchase Order | `purchase_order` | User manually in UI | Planning document — a request sent to the supplier; a paper PO printed and given to management |
| ERP Order | `erp_order` | Import from erp.kittykat.tech (1C simulator) | Real confirmed supply from ERP; drives the Forecast chart |

### Why two types?
- A **Purchase Order** is a *planning intent* — it does NOT mean fuel will arrive. Only an ERP confirmation means it will.
- The **Forecast chart** must only show bumps for **real ERP deliveries**, not for unconfirmed POs (which could be ignored, duplicate, or cancelled due to error).
- The user needs to track both: did I submit a request to the supplier? Did the ERP confirm it?

---

## Purchase Order (PO) Lifecycle

```
[User creates PO]
        ↓
   status: planned
        │
        ├── [ERP import arrives, matches PO] ──→ matched
        │                                          (erp_order_id set, matched_at set)
        │
        ├── [Delivery date passes, no ERP] ──→ expired
        │   (auto: markExpiredPOs() on each index load)
        │
        └── [User cancels — error correction] ──→ cancelled
                (cancelled_reason required)
```

**Key rules for POs:**
- Only `purchase_order` type can be cancelled by user (not ERP orders)
- Cannot cancel if status is `matched` or `expired` (only `planned` → `cancelled`)
- Only `planned` POs can be printed and cancelled via UI
- POs do **NOT** affect the Forecast chart — they are invisible to ForecastService

---

## ERP Order Lifecycle

```
[Import from ERP system]
        ↓
   status: confirmed  (ERP has confirmed the shipment)
        ↓
   status: in_transit  (ERP reports truck dispatched)
        ↓
   status: delivered   (ERP reports delivery complete)
                        → stock physically updated elsewhere
        or
   status: cancelled   (ERP cancelled — managed by ERP/Import only)
```

**Key rules for ERP orders:**
- Created ONLY via Import module (or `Order::createErpOrder()` internally)
- Read-only in UI — no create/cancel buttons shown
- `confirmed` and `in_transit` ERP orders → **shown as delivery bumps on Forecast chart**
- `delivered` → stock already in `depot_tanks.current_stock_liters` (source of truth)

---

## Auto-Matching: PO ↔ ERP Order

When an ERP order is imported (`Order::createErpOrder()`), the system automatically searches for a matching PO:

```sql
WHERE order_type = 'purchase_order'
  AND status = 'planned'
  AND station_id = [same]
  AND fuel_type_id = [same]
  AND ABS(DATEDIFF(delivery_date, [erp_delivery_date])) <= 7
ORDER BY ABS(DATEDIFF(...)) ASC
LIMIT 1
```

If a match is found:
- PO status → `matched`
- PO `erp_order_id` → ID of the new ERP order
- PO `matched_at` → NOW()

---

## Database Schema

### New columns added by migration 008:

```sql
-- Added to orders table
order_type   ENUM('purchase_order', 'erp_order') NOT NULL DEFAULT 'purchase_order'
erp_order_id INT NULL       -- FK: which ERP order matched this PO
matched_at   DATETIME NULL  -- when was this PO matched to ERP
```

### Status ENUM (full):
```sql
ENUM('planned','matched','expired','confirmed','in_transit','delivered','cancelled')
```

| Status | Applies to | Description |
|--------|-----------|-------------|
| `planned` | purchase_order | Created by user, awaiting ERP confirmation |
| `matched` | purchase_order | ERP confirmed — linked to erp_order_id |
| `expired` | purchase_order | Delivery date passed without ERP matching |
| `confirmed` | erp_order | ERP has confirmed the shipment |
| `in_transit` | erp_order | Truck dispatched, en route |
| `delivered` | erp_order | Delivery completed |
| `cancelled` | both | Cancelled (with reason for PO; by ERP for erp_order) |

---

## Backend API

### GET `/api/orders`

Returns orders filtered by `order_type`:

```
GET /api/orders?order_type=purchase_order   → only POs
GET /api/orders?order_type=erp_order        → only ERP orders
GET /api/orders?station_id=249&fuel_type_id=25
```

**Response fields (all orders):**
```json
{
  "id": 42,
  "order_number": "ORD-2026-001",
  "order_type": "purchase_order",
  "supplier_id": 1,
  "supplier_name": "OPCK",
  "station_id": 249,
  "station_name": "Станция Каинда",
  "depot_id": 148,
  "depot_name": "Каинда-1",
  "fuel_type_id": 25,
  "fuel_type_name": "Diesel B7",
  "fuel_type_code": "DIESB7",
  "density": "0.830",
  "quantity_liters": "45000.00",
  "quantity_tons": "37.35",
  "price_per_ton": "850.00",
  "total_amount": "31747.50",
  "order_date": "2026-02-23",
  "delivery_date": "2026-03-05",
  "status": "planned",
  "notes": null,
  "cancelled_reason": null,
  "cancelled_at": null,
  "erp_order_id": null,
  "matched_at": null,
  "created_at": "2026-02-23 16:00:00",
  "created_by": null
}
```

### POST `/api/orders`
Creates a new purchase order (always `order_type = 'purchase_order'`, status `planned`).

**Required fields:** `station_id`, `fuel_type_id`, `quantity_liters`, `delivery_date`
**Optional:** `supplier_id`, `depot_id`, `price_per_ton`, `notes`

### POST `/api/orders/{id}/cancel`
```json
{ "reason": "Wrong quantity entered" }
```
Only works for `purchase_order` with status `planned`.

### PUT `/api/orders/{id}`
Update PO fields: `quantity_liters`, `price_per_ton`, `total_amount`, `delivery_date`, `supplier_id`, `depot_id`, `notes`.
Cannot update `delivered` or `cancelled` orders.

### DELETE `/api/orders/{id}`
Delete PO — only if `status = 'planned'`.

---

## Frontend: Orders Page (two tabs)

### Tab 1: Purchase Orders
- Shows: `planned`, `matched`, `expired`, `cancelled` statuses
- Has **+ New PO** button
- Per-row actions:
  - `planned` → **Print** + **Cancel**
  - `matched` → shows "ERP #ID" reference — no actions
  - `expired` → no actions
  - `cancelled` → shows reason — no actions
- Status badges:
  - `planned` → gray
  - `matched` → blue ("Matched")
  - `expired` → orange ("Expired")
  - `cancelled` → red

### Tab 2: ERP Deliveries
- Shows: `confirmed`, `in_transit`, `delivered`, `cancelled` statuses
- **Read-only** — no create/cancel buttons
- Info banner: "ERP orders are imported from erp.kittykat.tech and are read-only"
- Extra column: **Matched PO** — shows "Linked" if `erp_order_id` is set
- Status badges:
  - `confirmed` → sky blue
  - `in_transit` → amber
  - `delivered` → green
  - `cancelled` → red

---

## Forecast Integration

**ForecastService** (`backend/src/Services/ForecastService.php`) uses ONLY ERP orders for delivery bumps:

```php
AND o.order_type = 'erp_order'
AND o.status NOT IN ('cancelled', 'delivered')
-- i.e. only confirmed + in_transit ERP orders create bumps
```

Purchase Orders are **invisible** to the forecast. Only real ERP confirmations matter.

---

## Procurement Advisor Integration

`ProcurementAdvisorService::getUpcomingShortages()` calls `Order::findActivePO()` for each shortage entry:

```php
$activePO = Order::findActivePO((int)$row['station_id'], (int)$row['fuel_type_id']);

$shortages[] = [
    // ... all the usual fields ...
    'po_pending' => $activePO !== null,
    'active_po'  => $activePO,  // { order_number, delivery_date, quantity_tons, status }
];
```

In `ProcurementAdvisor.vue`:
- If `po_pending = true`: shows a blue banner "PO Issued — Awaiting ERP Confirmation" with PO details
- Button changes to "View Purchase Orders" (navigates to /orders)
- If `po_pending = false`: shows "Create Order" button as normal

---

## Key Methods (Order.php)

| Method | Description |
|--------|-------------|
| `Order::create(array $data)` | Creates PO — always `order_type = 'purchase_order'`, status `planned` |
| `Order::createErpOrder(array $data)` | Creates ERP order + auto-matches to existing PO |
| `Order::cancel(int $id, string $reason)` | Cancels PO (only `planned` POs) |
| `Order::matchWithErp(int $poId, int $erpOrderId)` | Links PO to ERP order, sets `matched` status |
| `Order::markExpiredPOs()` | Marks `planned` POs with past delivery_date as `expired` |
| `Order::findActivePO(int $stationId, int $fuelTypeId)` | For Procurement Advisor — finds active PO |
| `Order::all(array $filters)` | Supports `order_type` filter |

---

## Future Work

- **Import.vue** — Full UI to connect to erp.kittykat.tech, sync ERP orders
- **PO Expiry notification** — show warning badge for expired POs on Dashboard
- **ERP order link in table** — in PO tab, link to matching ERP order
- **Bulk import** — CSV/Excel import of ERP orders for historical data
- **PO PDF export** — already implemented via `window.print()` with print CSS

---

> Last updated: 2026-02-23
> Migration: 008_order_type.sql
> Commit: `feat: Orders module — PO vs ERP order type separation`
