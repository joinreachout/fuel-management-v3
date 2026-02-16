# 📚 REV 3.0 API Documentation

**Base URL:** `https://fuel.kittykat.tech/rev3/backend/public`

All responses are in JSON format with consistent structure:
```json
{
    "success": true/false,
    "data": [...],
    "count": N,
    "error": "Error message if failed"
}
```

## 🎯 Data Units Standard

**⚠️ IMPORTANT:** All fuel quantities use **LITERS** as the source of truth.

- **Storage:** `depot_tanks.current_stock_liters` (SOURCE OF TRUTH)
- **Conversion:** Tons calculated on-the-fly: `liters * density / 1000`
- **Density mapping:**
  - АИ-92, АИ-95: `0.75`
  - Diesel (ДТ): `0.84`
  - ТС-1 (Керосин): `0.80`
  - Мазут: `0.92`

---

## 📍 Stations

### GET `/api/stations`
Get all stations.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 249,
            "name": "Станция Каинда",
            "code": "KAIN",
            "region_id": 1,
            "region_name": "Алматинская область",
            "depot_count": 5,
            "created_at": "2026-02-16 06:42:03"
        }
    ],
    "count": 9
}
```

### GET `/api/stations/{id}`
Get single station by ID.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 249,
        "name": "Станция Каинда",
        "code": "KAIN",
        "region_id": 1,
        "region_name": "Алматинская область",
        "created_at": "2026-02-16 06:42:03"
    }
}
```

### GET `/api/stations/{id}/depots`
Get all depots for a station.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 148,
            "name": "Каинда-1",
            "code": "KAIN1",
            "station_id": 249,
            "created_at": "2026-02-16 06:42:03"
        }
    ],
    "count": 5,
    "station_id": 249
}
```

---

## 🏭 Depots

### GET `/api/depots`
Get all depots.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 148,
            "station_id": 249,
            "name": "Каинда-1",
            "code": "KAIN1",
            "station_name": "Станция Каинда",
            "station_code": "KAIN",
            "created_at": "2026-02-16 06:42:03"
        }
    ],
    "count": 19
}
```

### GET `/api/depots/{id}`
Get single depot by ID.

### GET `/api/depots/{id}/tanks`
Get all tanks for a depot with current stock levels.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 116,
            "tank_number": "№1",
            "fuel_type_id": 33,
            "fuel_type_name": "Diesel B10",
            "fuel_type_code": "DIESB10",
            "density": "0.800",
            "capacity_liters": "1985690.00",
            "current_stock_liters": "618886.89",
            "current_stock_tons": "495.11",
            "fill_percentage": "31.2"
        }
    ],
    "count": 6,
    "depot_id": 148
}
```

### GET `/api/depots/{id}/stock`
Get aggregated stock by fuel type for a depot.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "fuel_type_id": 35,
            "fuel_type_name": "A-92 Euro",
            "fuel_type_code": "GAS92EUR",
            "density": "0.800",
            "tank_count": 1,
            "total_capacity_liters": "2057209.00",
            "total_stock_liters": "1575648.35",
            "total_stock_tons": "1260.52"
        }
    ],
    "depot_id": 148
}
```

### GET `/api/depots/{id}/forecast`
Get consumption forecast for a depot (requires stock_policies configuration).

---

## ⛽ Fuel Types

### GET `/api/fuel-types`
Get all fuel types with density values.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 31,
            "name": "A-95",
            "code": "GAS95",
            "density": "0.800",
            "unit": "liter",
            "fuel_group": "Gasoline",
            "created_at": "2026-02-16 06:39:52"
        }
    ],
    "count": 10
}
```

### GET `/api/fuel-types/{id}`
Get single fuel type by ID.

### GET `/api/fuel-types/{id}/stock`
Get total stock across all depots for a specific fuel type.

---

## 🚚 Suppliers

### GET `/api/suppliers`
Get all suppliers.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "OPCK",
            "departure_station": "Никель",
            "priority": 3,
            "auto_score": "2.00",
            "avg_delivery_days": 26,
            "is_active": 1,
            "created_at": "2026-02-16 06:55:41"
        }
    ],
    "count": 11
}
```

### GET `/api/suppliers/active`
Get only active suppliers.

### GET `/api/suppliers/{id}`
Get single supplier by ID.

### GET `/api/suppliers/{id}/orders`
Get all orders for a specific supplier.

### GET `/api/suppliers/{id}/stats`
Get statistics for a supplier (total orders, completed, pending, cancelled, volumes, amounts).

---

## 📦 Orders

### GET `/api/orders`
Get all fuel orders from suppliers.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "order_number": "ORD-2026-001",
            "supplier_id": 1,
            "supplier_name": "OPCK",
            "station_id": 249,
            "station_name": "Станция Каинда",
            "fuel_type_id": 31,
            "fuel_type_name": "A-95",
            "fuel_type_code": "GAS95",
            "quantity_liters": "50000.00",
            "quantity_tons": "40.00",
            "price_per_ton": "850.00",
            "total_amount": "34000.00",
            "order_date": "2026-02-10",
            "delivery_date": "2026-02-25",
            "status": "planned",
            "created_at": "2026-02-10 10:00:00"
        }
    ],
    "count": 0
}
```

**Status values:** `planned`, `confirmed`, `in_transit`, `delivered`, `cancelled`

### GET `/api/orders/{id}`
Get single order by ID.

### GET `/api/orders/pending`
Get pending orders (status = 'planned').

### GET `/api/orders/summary`
Get orders summary grouped by fuel type.

### GET `/api/orders/recent?days=30`
Get recent orders (default: last 30 days).

---

## 🔄 Transfers

**Note:** Transfers work between **STATIONS**, not depots.

### GET `/api/transfers`
Get all fuel transfers between stations.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "from_station_id": 249,
            "from_station_name": "Станция Каинда",
            "from_station_code": "KAIN",
            "to_station_id": 250,
            "to_station_name": "Станция Бишкек",
            "to_station_code": "BISH",
            "fuel_type_id": 31,
            "fuel_type_name": "A-95",
            "fuel_type_code": "GAS95",
            "transfer_amount_liters": "30000.00",
            "transfer_amount_tons": "24.00",
            "status": "pending",
            "urgency": "NORMAL",
            "estimated_days": "5.0",
            "created_at": "2026-02-15 14:00:00"
        }
    ],
    "count": 0
}
```

**Status values:** `pending`, `in_progress`, `in_process`, `completed`, `cancelled`

**Urgency levels:** `NORMAL`, `MUST_ORDER`, `CRITICAL`, `CATASTROPHE`

### GET `/api/transfers/{id}`
Get single transfer by ID.

### GET `/api/transfers/pending`
Get pending transfers.

### GET `/api/transfers/recent?days=30`
Get recent transfers (default: last 30 days).

---

## 📊 Data Summary

| Resource | Count | Description |
|----------|-------|-------------|
| Stations | 9 | Fuel distribution stations |
| Depots | 19 | Fuel storage depots |
| Depot Tanks | 95 | Individual storage tanks |
| Fuel Types | 10 | Types of fuel (gasoline, diesel, etc) |
| Suppliers | 11 | Fuel suppliers |
| Total Stock | 139,165 m³ | Current fuel inventory |

---

## ⚠️ Important Notes

1. **Units:** Always use LITERS as primary unit. Tons are calculated on-the-fly.
2. **Source of Truth:** `depot_tanks.current_stock_liters`
3. **Transfers:** Work between stations, not depots
4. **Orders:** Track purchases from suppliers
5. **No Authentication:** Currently no auth required (add middleware later)

---

## 🔧 Error Handling

All endpoints return consistent error format:

```json
{
    "success": false,
    "error": "Error description"
}
```

HTTP Status Codes:
- `200` - Success
- `404` - Resource not found
- `500` - Server error
