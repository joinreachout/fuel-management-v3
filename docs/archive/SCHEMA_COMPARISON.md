# Schema Design Comparison

## Problem Statement
We need to store supplier data including:
- Base info (name, rating, priority)
- Prices per fuel type
- Delivery times per station

## Option 1: Three Separate Tables (Current) ❌ TOO COMPLEX

```
suppliers (11 rows)
supplier_prices (11 suppliers × 10 fuels = 110 rows)
supplier_delivery_routes (11 suppliers × 9 stations = 99 rows)
Total: 3 tables, 220 rows
```

**Issues:**
- Complex JOINs for procurement logic
- Need 3 tables for one supplier query
- Over-engineering for our use case

## Option 2: One Flat Table ❌ TOO MUCH DUPLICATION

```
supplier_catalog:
- supplier_id, station_id, fuel_type_id
- price_per_ton, delivery_days
Total: 11 × 9 × 10 = 990 rows!
```

**Issues:**
- MASSIVE duplication
- If supplier name changes → update 90 rows
- If price changes → update 9 rows
- High risk of data inconsistency

## Option 3: Two Tables (RECOMMENDED) ✅

```
suppliers - Base info (11 rows)
├── id, name, departure_station
├── priority, auto_score
└── is_active

supplier_station_prices - Combined pricing & delivery (11 × 9 = 99 rows)
├── supplier_id
├── station_id
├── delivery_days, distance_km
├── price_per_ton_diesel, price_per_ton_gasoline_92, price_per_ton_gasoline_95
├── currency
├── is_active
└── valid_from, valid_until
```

**Advantages:**
- ✅ Simple queries: 1 JOIN instead of 3
- ✅ Minimal duplication
- ✅ Easy to update delivery times (1 row per supplier-station)
- ✅ Easy to update prices (1 row per supplier-station)
- ✅ Logical grouping: delivery and pricing go together

**Why this works:**
- Delivery time depends on **supplier → station** route
- Prices usually similar for one supplier (volume discount tier)
- 99 rows total is manageable
- Query pattern: "Get supplier offers for Station X"

## Recommended Schema

```sql
CREATE TABLE suppliers (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    departure_station VARCHAR(100),
    priority INT,
    auto_score DECIMAL(3,2),
    is_active TINYINT(1),
    created_at TIMESTAMP
);

CREATE TABLE supplier_station_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT,
    station_id INT,

    -- Delivery info
    delivery_days INT NOT NULL,
    distance_km DECIMAL(10,2),
    route_notes TEXT,

    -- Pricing (store common fuels as columns)
    price_diesel_b7 DECIMAL(10,2),
    price_diesel_b10 DECIMAL(10,2),
    price_gas_92 DECIMAL(10,2),
    price_gas_95 DECIMAL(10,2),
    price_gas_98 DECIMAL(10,2),
    price_jet DECIMAL(10,2),
    price_lpg DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',

    -- Constraints
    min_order_tons DECIMAL(10,2),
    max_order_tons DECIMAL(10,2),

    -- Validity
    is_active TINYINT(1) DEFAULT 1,
    valid_from DATE NOT NULL,
    valid_until DATE,
    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (station_id) REFERENCES stations(id),
    UNIQUE KEY unique_offer (supplier_id, station_id, is_active),
    INDEX idx_station_active (station_id, is_active)
);
```

## Usage Examples

### Get best offers for station
```sql
SELECT
    s.name as supplier_name,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.price_gas_95,
    s.priority,
    s.auto_score
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
WHERE sso.station_id = 249
  AND sso.is_active = 1
  AND s.is_active = 1
ORDER BY s.priority ASC, sso.price_diesel_b7 ASC;
```

### Get supplier offers for specific fuel
```sql
SELECT
    s.name,
    st.name as station_name,
    sso.price_diesel_b7,
    sso.delivery_days,
    (sso.price_diesel_b7 * 200) as total_cost_for_200t
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.price_diesel_b7 IS NOT NULL
  AND sso.is_active = 1
ORDER BY sso.price_diesel_b7 ASC;
```

## Migration from Option 1 to Option 3

If we already created 3 tables:

```sql
-- Consolidate into supplier_station_offers
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, price_diesel_b7, price_gas_95, is_active, valid_from)
SELECT
    sdr.supplier_id,
    sdr.station_id,
    sdr.delivery_days,
    MAX(CASE WHEN sp.fuel_type_id = 25 THEN sp.price_per_ton END) as price_diesel_b7,
    MAX(CASE WHEN sp.fuel_type_id = 31 THEN sp.price_per_ton END) as price_gas_95,
    1 as is_active,
    CURDATE() as valid_from
FROM supplier_delivery_routes sdr
LEFT JOIN supplier_prices sp ON sdr.supplier_id = sp.supplier_id
WHERE sdr.is_active = 1
GROUP BY sdr.supplier_id, sdr.station_id, sdr.delivery_days;

-- Then drop old tables
DROP TABLE supplier_prices;
DROP TABLE supplier_delivery_routes;
```

## Conclusion

**Use Option 3: Two Tables**
- suppliers (base info)
- supplier_station_offers (combined pricing + delivery)

This is the sweet spot between normalization and practicality.
