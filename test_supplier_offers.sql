-- Test queries for supplier_station_offers table
-- Run these in phpMyAdmin after migration

-- 1. Check total offers in the system
SELECT COUNT(*) as total_offers FROM supplier_station_offers WHERE is_active = 1;

-- 2. View all offers for supplier 8 (НПЗ Кара май Ойл-Тараз)
SELECT
    s.name as supplier_name,
    st.name as station_name,
    sso.delivery_days,
    sso.distance_km,
    sso.price_diesel_b7,
    sso.price_gas_92,
    sso.price_gas_95,
    sso.currency
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.supplier_id = 8
  AND sso.is_active = 1
ORDER BY sso.delivery_days ASC;

-- 3. Find best supplier for Station Бишкек (id=250) with Diesel B7
SELECT
    s.id,
    s.name as supplier_name,
    s.departure_station,
    s.priority,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.currency
FROM suppliers s
INNER JOIN supplier_station_offers sso ON s.id = sso.supplier_id
WHERE sso.station_id = 250
  AND sso.is_active = 1
  AND s.is_active = 1
  AND sso.price_diesel_b7 IS NOT NULL
ORDER BY
    s.priority ASC,
    sso.delivery_days ASC,
    s.auto_score DESC
LIMIT 1;

-- 4. Compare delivery times to different stations
SELECT
    st.name as station_name,
    MIN(sso.delivery_days) as fastest_delivery,
    AVG(sso.delivery_days) as avg_delivery,
    MAX(sso.delivery_days) as slowest_delivery,
    COUNT(DISTINCT sso.supplier_id) as supplier_count
FROM supplier_station_offers sso
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.is_active = 1
GROUP BY st.name
ORDER BY fastest_delivery ASC;

-- 5. Price comparison across stations for Diesel B7
SELECT
    st.name as station_name,
    s.name as supplier_name,
    sso.price_diesel_b7,
    sso.delivery_days,
    sso.currency
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.is_active = 1
  AND sso.price_diesel_b7 IS NOT NULL
ORDER BY st.name, sso.price_diesel_b7 ASC;

-- 6. Verify no duplicate active offers per supplier-station pair
-- This should return 0 rows (the UNIQUE constraint prevents duplicates)
SELECT
    supplier_id,
    station_id,
    COUNT(*) as duplicate_count
FROM supplier_station_offers
WHERE is_active = 1
GROUP BY supplier_id, station_id
HAVING COUNT(*) > 1;
