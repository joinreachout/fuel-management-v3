# Orders Module — Full Specification

> Status: Backend API exists. Frontend UI not implemented. See PROGRESS.md.

---

## Why This Matters

The forecast chart is only useful if orders are managed correctly. The core contract:

- **Delivered orders** → stock was already physically added → `current_stock_liters` in `depot_tanks` is the source of truth
- **Planned orders** (`confirmed` / `in_transit`) → shown as delivery bumps on the forecast chart
- **Cancelled orders** → immediately vanish from forecast, triggering new shortage warnings

Real-world risks that make cancellation critical:
- Supplier truck breaks down en route
- Factory/refinery disruption (fire, strike, sanctions)
- Customs delays
- Extreme weather blocks route
- Order placed with wrong quantity or depot

When a bump disappears from the forecast, the system must surface the impact **immediately** — which stations are now at risk, how urgent is re-ordering.

---

## Status Flow

```
pending
  │
  ├─ [Confirm] ──────────────→ confirmed
  │                                │
  │                         [Mark In Transit] → in_transit
  │                                │                │
  │                          [Cancel]         [Cancel]
  │                          + reason         + reason
  │                                │                │
  │                          cancelled        cancelled
  │
  └─ [Cancel] → cancelled (+ reason)

confirmed / in_transit:
  └─ [Mark Delivered] → delivered
```

**Rules:**
- `delivered` is **terminal** — cannot be cancelled
- `cancelled` is **terminal** — cannot be un-cancelled (create new order instead)
- Cancellation always requires a reason (non-empty string)

---

## DB Schema Changes

Add to `orders` table (migration 003 or separate):

```sql
ALTER TABLE orders
  ADD COLUMN cancelled_reason VARCHAR(500) NULL AFTER status,
  ADD COLUMN cancelled_at DATETIME NULL AFTER cancelled_reason;
```

`status` ENUM extended to include `'cancelled'`:
```sql
ALTER TABLE orders MODIFY COLUMN status ENUM(
  'pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'
) NOT NULL DEFAULT 'pending';
```

---

## Backend API

### Existing endpoints (already in OrderController):
| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/orders` | List all orders (filterable) |
| GET | `/api/orders/{id}` | Single order |
| POST | `/api/orders` | Create order |
| PUT | `/api/orders/{id}` | Update order (status, quantity, date) |
| DELETE | `/api/orders/{id}` | Delete order (admin only) |

### New endpoint needed:
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/orders/{id}/cancel` | Cancel with reason |

Request body:
```json
{ "reason": "Supplier truck broke down on Bishkek-Osh road" }
```

The cancel endpoint:
1. Validates `reason` is not empty
2. Sets `status = 'cancelled'`, `cancelled_reason = ?`, `cancelled_at = NOW()`
3. Returns updated order

**Forecast impact:** ForecastService queries `status IN ('confirmed', 'in_transit')` — cancellation automatically removes the bump. No extra code needed in ForecastService.

---

## Frontend: Orders Page

### Layout
```
┌─────────────────────────────────────────────────────┐
│  Orders                          [+ New Order]       │
├─────────────────────────────────────────────────────┤
│  Filters: [Station ▼] [Fuel Type ▼] [Status ▼] [Date range]  │
├──────┬──────────┬───────────┬───────────┬───────────┬────────┤
│  #   │ Station  │ Fuel Type │ Qty (tons)│ Delivery  │ Status │ Actions │
├──────┼──────────┼───────────┼───────────┼───────────┼────────┤
│ ...  │  ...     │  ...      │   ...     │ Feb 26    │ ✅ del │        │
│ ...  │  ...     │  ...      │   ...     │ Mar 05    │ 🚚 int │ [✓del] [✗]│
│ ...  │  ...     │  ...      │   ...     │ Mar 12    │ 📋 cnf │ [🚚] [✓] [✗]│
│ ...  │  ...     │  ...      │   ...     │ Mar 19    │ ⏳ pnd │ [📋] [✗]│
│ ...  │  ...     │  ...      │   ...     │ Feb 20    │ ❌ can │ (reason shown on hover)│
└──────┴──────────┴───────────┴───────────┴───────────┴────────┘
```

### Status badges (colored):
| Status | Color | Label |
|--------|-------|-------|
| `pending` | gray | Pending |
| `confirmed` | blue | Confirmed |
| `in_transit` | yellow | In Transit |
| `delivered` | green | Delivered |
| `cancelled` | red | Cancelled |

### Action buttons per row:
- `pending`: [Confirm] [Cancel]
- `confirmed`: [Mark In Transit] [Mark Delivered] [Cancel]
- `in_transit`: [Mark Delivered] [Cancel]
- `delivered`: — (no actions)
- `cancelled`: shows `cancelled_reason` as tooltip or inline text

### Cancellation modal:
```
┌─────────────────────────────────────────┐
│  Cancel Order #ORD-2026-025             │
│                                         │
│  Station: Станция Рыбачье               │
│  Fuel: Diesel B7 — 45,000 L             │
│  Delivery: Mar 05, 2026                 │
│                                         │
│  Reason for cancellation: *             │
│  ┌─────────────────────────────────┐   │
│  │ e.g. Supplier truck broke down  │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ⚠️ This will update the forecast chart │
│  and may trigger new shortage alerts.   │
│                                         │
│         [Cancel Order]  [Go Back]       │
└─────────────────────────────────────────┘
```

### Create/Edit order form:
Fields:
- Station (select from stations)
- Fuel Type (select — filtered by what that station uses)
- Quantity (liters, shown as tons preview)
- Supplier (select from suppliers)
- Delivery Date (date picker)
- Status (default: pending)
- Notes (optional)

---

## Forecast Integration

**Current behavior (already works):**
```php
AND o.status IN ('confirmed', 'in_transit')
```

When an order is cancelled → status changes → next forecast load excludes it automatically.

**No changes needed in ForecastService.**

However, consider adding a **visual indicator** on the forecast chart for cancelled orders:
- Faint dashed vertical line on the day of the cancelled delivery
- Tooltip: "Order cancelled: [reason]"
- Helps the user see WHERE the gap appeared and why

---

## Procurement Advisor Integration

After cancellation, ProcurementAdvisor should immediately show:
- The station+fuel now at higher risk (the cancelled delivery was covering it)
- New recommended order with adjusted quantities and urgency

**Current ProcurementAdvisorService** recalculates on each page load → cancellation automatically surfaces new recommendations. No extra code needed unless we want real-time push notifications.

---

## Implementation Order

1. **DB migration** — add `cancelled_reason`, `cancelled_at`, extend `status` ENUM
2. **Backend** — `POST /api/orders/{id}/cancel` endpoint
3. **Backend** — ensure `GET /api/orders` supports filtering by status, station, date
4. **Frontend** — Orders list page with table + status badges + action buttons
5. **Frontend** — Cancel modal with reason input
6. **Frontend** — Create/Edit order form
7. **Frontend** — Connect to forecast chart (verify bump disappears after cancel)
8. **Frontend** — Connect to Procurement Advisor (verify new shortage surfaces)

---

## Estimated Scope

- Backend: ~1-2h (migration + cancel endpoint + filter improvements)
- Frontend: ~3-4h (list page + actions + modal + form)
- Testing: ~1h (create → confirm → cancel → verify forecast)
