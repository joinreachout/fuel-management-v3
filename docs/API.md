# REV 3.0 API Reference

**Base URL:** `https://fuel.kittykat.tech/rev3/backend/public`

All responses JSON. Consistent envelope:
```json
{ "success": true,  "data": [...], "count": N }
{ "success": false, "error": "Error message in English" }
```

**Units:** All stock/consumption stored and returned in **liters**. Tons calculated on-the-fly via `fuel_types.density`. See `docs/TECH_DECISIONS.md` for conversion rules.

---

## Stations `GET /api/stations`
```json
[{ "id": 249, "name": "Станция Каинда", "code": "KAIN",
   "region_id": 1, "region_name": "Алматинская область", "depot_count": 5 }]
```
`GET /api/stations/{id}` — single station
`GET /api/stations/{id}/depots` — depots for station

---

## Depots

`GET /api/depots` — all depots (19)
`GET /api/depots/{id}` — single depot
`GET /api/depots/{id}/tanks` — tanks with stock:
```json
[{ "id": 116, "tank_number": "№1", "fuel_type_id": 33, "fuel_type_name": "Diesel B10",
   "density": "0.800", "capacity_liters": "1985690.00",
   "current_stock_liters": "618886.89", "current_stock_tons": "495.11",
   "fill_percentage": "31.2" }]
```
`GET /api/depots/{id}/stock` — aggregated stock by fuel type
`GET /api/depots/{id}/forecast` — consumption forecast (requires stock_policies data)

---

## Fuel Types `GET /api/fuel-types`
```json
[{ "id": 31, "name": "A-95", "code": "GAS95", "density": "0.800",
   "fuel_group": "Gasoline" }]
```
`GET /api/fuel-types/{id}` — single fuel type
`GET /api/fuel-types/{id}/stock` — total stock across all depots for this fuel type
`PUT /api/fuel-types/{id}` — update `density` only

---

## Suppliers `GET /api/suppliers`
```json
[{ "id": 1, "name": "ОРСК", "is_active": 1,
   "avg_delivery_days": 26, "auto_score": "2.00" }]
```
`GET /api/suppliers/active` — active only
`GET /api/suppliers/{id}` — single supplier
`GET /api/suppliers/{id}/orders` — orders for supplier
`GET /api/suppliers/{id}/stats` — totals: orders, volumes, amounts

---

## Supplier Station Offers

`GET /api/supplier-station-offers` — all offers
`GET /api/supplier-station-offers?station_id=249` — filter by station
`GET /api/supplier-station-offers?supplier_id=1` — filter by supplier
`PUT /api/supplier-station-offers/{id}` — update `price_per_ton`, `delivery_days`
```json
{ "price_per_ton": 950.00, "delivery_days": 28 }
```

---

## Orders

Two types in the same table, distinguished by `order_type`:
- `purchase_order` — created in UI; planning doc; does NOT affect forecast
- `erp_order` — from ERP system; drives forecast delivery bumps

### GET `/api/orders`
Filter by query params: `order_type`, `station_id`, `fuel_type_id`, `status`, `date_from`, `date_to`
```
GET /api/orders?order_type=purchase_order
GET /api/orders?order_type=erp_order
```

Response object:
```json
{
  "id": 1, "order_number": "PO-20260225-0001",
  "order_type": "purchase_order",
  "supplier_id": 1, "supplier_name": "ОРСК",
  "station_id": 249, "station_name": "Станция Каинда",
  "depot_id": 148, "depot_name": "Каинда-1",
  "fuel_type_id": 31, "fuel_type_name": "A-95", "fuel_type_code": "GAS95",
  "density": "0.750",
  "quantity_liters": "50000.00",
  "quantity_tons": "37.50",
  "price_per_ton": "850.00",
  "total_amount": "31875.00",
  "order_date": "2026-02-25",
  "delivery_date": "2026-03-05",
  "status": "planned",
  "notes": null,
  "cancelled_reason": null
}
```

**PO statuses:** `planned` → `matched` | `expired` | `cancelled`
**ERP statuses:** `confirmed` → `in_transit` → `delivered` | `cancelled`

### GET `/api/orders/{id}` — single order
### GET `/api/orders/stats` — status counts by order_type
```json
{ "po": { "planned": 3, "matched": 1, "expired": 2, "cancelled": 0 },
  "erp": { "confirmed": 4, "in_transit": 2, "delivered": 10, "cancelled": 1 } }
```

### POST `/api/orders` — create PO (always `purchase_order`, status `planned`)
```json
{
  "station_id": 249, "fuel_type_id": 31,
  "quantity_liters": 50000,
  "delivery_date": "2026-03-05",
  "supplier_id": 1,
  "depot_id": 148,
  "price_per_ton": 850.00,
  "notes": "optional"
}
```
`quantity_liters` is stored as-is. Frontend converts from tons using `density` before submitting.

### POST `/api/orders` with `order_type: erp_order` — create ERP order (Manual Entry)
```json
{
  "order_type": "erp_order",
  "station_id": 249, "fuel_type_id": 31,
  "quantity_liters": 50000, "price_per_ton": 850,
  "delivery_date": "2026-03-05", "supplier_id": 1
}
```

### PUT `/api/orders/{id}` — update order fields
Editable: `quantity_liters`, `price_per_ton`, `total_amount`, `delivery_date`,
`supplier_id`, `station_id`, `fuel_type_id`, `depot_id`, `notes`, `status`

Status validation:
- `purchase_order` → accepts: `planned`, `matched`, `expired`
- `erp_order` → accepts: `confirmed`, `in_transit`, `delivered`
- `cancelled` and `delivered` orders are **terminal** — cannot be updated

### POST `/api/orders/{id}/cancel` — cancel PO (mandatory reason)
```json
{ "reason": "Wrong quantity entered" }
```
Only for `purchase_order` with `status = planned`.

### GET `/api/orders/pending` — planned POs only
### GET `/api/orders/summary` — grouped by fuel type
### GET `/api/orders/recent?days=30` — last N days

---

## Transfers

`GET /api/transfers` — all
`GET /api/transfers/{id}` — single
`GET /api/transfers/pending` — pending only
`GET /api/transfers/recent?days=30` — last N days

```json
{
  "id": 1,
  "from_station_id": 249, "from_station_name": "Станция Каинда",
  "to_station_id": 250,   "to_station_name": "Станция Бишкек",
  "fuel_type_id": 31, "fuel_type_name": "A-95",
  "transfer_amount_liters": "30000.00", "transfer_amount_tons": "24.00",
  "status": "pending", "urgency": "NORMAL", "estimated_days": "5.0"
}
```
**Statuses:** `pending`, `in_progress`, `completed`, `cancelled`
**Urgency:** `NORMAL`, `MUST_ORDER`, `CRITICAL`, `CATASTROPHE`

---

## Dashboard

`GET /api/dashboard/summary` — KPI totals (stations, alerts, orders counts)
`GET /api/dashboard/stock-by-fuel` — stock aggregated by fuel type
`GET /api/dashboard/station-fill-levels` — fill% per station
`GET /api/dashboard/top-suppliers` — top suppliers by order volume

---

## Forecast

`GET /api/forecast/station/{id}?horizon=30` — forecast for station (30/60/90 days)
`GET /api/forecast/alerts` — stations below thresholds

---

## Parameters (CRUD)

`GET /api/parameters/sales-params` — daily consumption rates
`PUT /api/parameters/sales-params/{id}` — update `liters_per_day`

`GET /api/parameters/stock-policies` — threshold levels (⚠️ 0 records currently)
`PUT /api/parameters/stock-policies/{id}` — update `critical/min/target_level_liters`

`GET /api/parameters/infrastructure` — regions + stations + depots + tanks tree

---

## Procurement

`GET /api/procurement/summary` — shortage predictions + recommended orders
`GET /api/procurement/recommendations` — per-depot recommendations with supplier scores

---

## Import / Sync

`POST /api/import/sales` — CSV import of sales data
`POST /api/import/erp-order` — manual ERP order entry
`POST /api/import/sync-erp` — *(planned)* auto-sync from erp.kittykat.tech
