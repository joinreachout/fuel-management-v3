# Final Database Schema Plan

## ❌ Problem with Current Schema

Table `suppliers` has field `avg_delivery_days` which is **WRONG**!

Why it's wrong:
- Delivery time depends on **supplier → station** route
- Орск → Каинда ≠ Орск → Бишкек
- Average is meaningless when planning specific orders
- Can lead to incorrect procurement decisions

## ✅ Correct Schema

### Table 1: `suppliers` (Base Information)
```sql
suppliers
├── id
├── name
├── departure_station  (where supplier ships from)
├── priority           (1=highest)
├── auto_score         (reliability rating)
└── is_active
```

**Remove:** `avg_delivery_days` ❌ (this is misleading!)

### Table 2: `supplier_station_offers` (Offers per Station)
```sql
supplier_station_offers
├── id
├── supplier_id → suppliers.id
├── station_id → stations.id
├── delivery_days          ✅ (SPECIFIC to this route!)
├── distance_km
├── price_diesel_b7       (price at THIS station)
├── price_diesel_b10
├── price_gas_92
├── price_gas_95
├── price_gas_98
├── price_jet
├── price_lpg
├── currency
├── min_order_tons
├── max_order_tons
├── is_active
├── valid_from
└── valid_until
```

## Data Relationships

Example: Supplier 8 (НПЗ Кара май Ойл-Тараз) from Тараз, Kazakhstan

| Station | Delivery Days | Price Diesel B7 | Distance |
|---------|---------------|-----------------|----------|
| Каинда  | 16 days       | $830/ton        | 450 km   |
| Бишкек  | 18 days       | $830/ton        | 520 km   |
| ОШ      | 20 days       | $840/ton        | 680 km   |

**NOT:** Average 18 days to all stations! ❌

## Procurement Logic

### Current (WRONG):
```php
$deliveryDays = $supplier['avg_delivery_days']; // 18 days
// This is wrong for station ОШ (should be 20 days)!
```

### Correct:
```php
SELECT delivery_days, price_diesel_b7
FROM supplier_station_offers
WHERE supplier_id = 8
  AND station_id = 252  -- ОШ
  AND is_active = 1;
// Returns: 20 days, $840/ton (CORRECT for this specific route!)
```

## Migration Steps

### Step 1: Create new table
```sql
-- Run: create_supplier_station_offers_table.sql
```

### Step 2: Populate from existing data
```sql
-- Run: supplier_station_offers_seed.sql
-- OR import from Excel model
```

### Step 3: Remove misleading field
```sql
ALTER TABLE suppliers DROP COLUMN avg_delivery_days;
```

### Step 4: Update ProcurementAdvisorService
```php
// OLD (wrong):
$deliveryDays = $supplier['avg_delivery_days'];

// NEW (correct):
$sql = "
    SELECT
        sso.delivery_days,
        sso.price_diesel_b7,
        sso.price_diesel_b10,
        ...
    FROM supplier_station_offers sso
    WHERE sso.supplier_id = ?
      AND sso.station_id = ?
      AND sso.is_active = 1
    LIMIT 1
";
```

### Step 5: Update getBestSupplier() logic
```php
private static function getBestSupplier(
    int $fuelTypeId,
    int $stationId,  // ADD THIS!
    string $urgency
): ?array {
    $sql = "
        SELECT
            s.id,
            s.name,
            s.departure_station,
            s.priority,
            s.auto_score,
            sso.delivery_days,
            sso.price_diesel_b7,
            sso.price_diesel_b10,
            ...
        FROM suppliers s
        INNER JOIN supplier_station_offers sso
            ON s.id = sso.supplier_id
        WHERE sso.station_id = ?
          AND sso.is_active = 1
          AND s.is_active = 1
        ORDER BY
            s.priority ASC,
            sso.delivery_days ASC,
            sso.price_diesel_b7 ASC
        LIMIT 1
    ";

    $result = Database::fetchAll($sql, [$stationId]);
    ...
}
```

## Benefits of Correct Schema

1. **Accurate calculations**: Correct delivery time for each route
2. **Station-specific pricing**: Prices may vary by destination
3. **Route optimization**: Choose supplier based on actual delivery time
4. **Historical tracking**: Track price/delivery changes over time
5. **Flexibility**: Easy to add new stations or suppliers

## Example Queries

### Get best offer for Diesel B7 to Бишкек (station 250)
```sql
SELECT
    s.name as supplier_name,
    s.departure_station,
    sso.delivery_days,
    sso.price_diesel_b7,
    (sso.price_diesel_b7 * 200) as cost_for_200_tons,
    s.priority,
    s.auto_score
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
WHERE sso.station_id = 250  -- Бишкек
  AND sso.price_diesel_b7 IS NOT NULL
  AND sso.is_active = 1
  AND s.is_active = 1
ORDER BY
    (sso.price_diesel_b7 * 200 + sso.delivery_days * 50) ASC  -- composite score
LIMIT 5;
```

### Get all offers from supplier 8 for all stations
```sql
SELECT
    st.name as station_name,
    sso.delivery_days,
    sso.distance_km,
    sso.price_diesel_b7,
    sso.price_gas_95
FROM supplier_station_offers sso
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.supplier_id = 8
  AND sso.is_active = 1
ORDER BY sso.delivery_days ASC;
```

### Find suppliers for urgent order (< 20 days) to ОШ
```sql
SELECT
    s.name,
    s.departure_station,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.distance_km
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
WHERE sso.station_id = 252  -- ОШ
  AND sso.delivery_days <= 20
  AND sso.price_diesel_b7 IS NOT NULL
  AND sso.is_active = 1
  AND s.is_active = 1
ORDER BY sso.delivery_days ASC, sso.price_diesel_b7 ASC;
```

## Conclusion

✅ **DO**: Use `supplier_station_offers.delivery_days` (specific route)
❌ **DON'T**: Use `suppliers.avg_delivery_days` (meaningless average)

This ensures accurate procurement planning!
