# Supplier Prices Schema Documentation

## Overview
The `supplier_prices` table stores fuel pricing information from suppliers. This design follows database normalization principles by separating pricing data from supplier base information.

## Design Rationale

### Why separate `suppliers` and `supplier_prices`?

1. **No Data Duplication**: Supplier information (name, delivery time, rating) stored once
2. **Price History**: Track price changes over time with `valid_from` and `valid_until`
3. **Multiple Fuel Types**: Each supplier can have different prices for different fuels
4. **Flexible Pricing**: Support min/max order quantities, different currencies
5. **Easy Updates**: Update prices without touching supplier records

## Schema

```sql
suppliers (base table)
├── id
├── name
├── departure_station
├── priority
├── auto_score
├── avg_delivery_days
└── is_active

supplier_prices (pricing table)
├── id
├── supplier_id → suppliers.id
├── fuel_type_id → fuel_types.id
├── price_per_ton
├── currency
├── min_quantity_tons
├── max_quantity_tons
├── is_active
├── valid_from
├── valid_until
└── notes
```

## Key Fields

### `price_per_ton`
- Price in specified currency per metric ton
- DECIMAL(10,2) for precision

### `min_quantity_tons` / `max_quantity_tons`
- Optional order quantity constraints
- NULL = no limit
- Used for volume discounts or minimum order requirements

### `is_active`
- 1 = current/active price
- 0 = historical/inactive price
- Allows price history without deletion

### `valid_from` / `valid_until`
- Date range for price validity
- `valid_until` = NULL means indefinite
- Supports future pricing and scheduled price changes

## Usage Examples

### Get current price for specific supplier and fuel
```sql
SELECT price_per_ton, currency, min_quantity_tons, max_quantity_tons
FROM supplier_prices
WHERE supplier_id = 1
  AND fuel_type_id = 25
  AND is_active = 1
  AND valid_from <= CURDATE()
  AND (valid_until IS NULL OR valid_until >= CURDATE())
ORDER BY valid_from DESC
LIMIT 1;
```

### Get best price for fuel type (considering quantity)
```sql
SELECT s.name, sp.price_per_ton, sp.currency, s.avg_delivery_days
FROM supplier_prices sp
INNER JOIN suppliers s ON sp.supplier_id = s.id
WHERE sp.fuel_type_id = 25
  AND sp.is_active = 1
  AND s.is_active = 1
  AND (sp.min_quantity_tons IS NULL OR sp.min_quantity_tons <= 200)
  AND (sp.max_quantity_tons IS NULL OR sp.max_quantity_tons >= 200)
ORDER BY sp.price_per_ton ASC
LIMIT 5;
```

### Get supplier with prices for all available fuels
```sql
SELECT
    s.name,
    ft.name as fuel_name,
    sp.price_per_ton,
    sp.currency,
    sp.min_quantity_tons,
    sp.max_quantity_tons
FROM suppliers s
INNER JOIN supplier_prices sp ON s.id = sp.supplier_id
INNER JOIN fuel_types ft ON sp.fuel_type_id = ft.id
WHERE s.id = 8
  AND sp.is_active = 1
  AND s.is_active = 1
ORDER BY ft.name;
```

## Integration with Procurement Advisor

The ProcurementAdvisorService will use this table to:

1. **Calculate total order cost**: `recommended_order_tons * price_per_ton`
2. **Rank suppliers**: Consider price + delivery time + reliability
3. **Validate order quantities**: Check min/max constraints
4. **Show price trends**: Compare historical vs current prices
5. **Multi-currency support**: Handle different currencies

## Migration Steps

1. **Create table**: Run `create_supplier_prices_table.sql`
2. **Seed data**: Run `supplier_prices_seed.sql`
3. **Update ProcurementAdvisorService**: Use LEFT JOIN to supplier_prices
4. **Test**: Verify prices are returned correctly

## API Changes Required

### ProcurementAdvisorService.php
Update `getBestSupplier()` and `getSupplierRecommendations()` to:
- JOIN with `supplier_prices` table
- Filter by `is_active = 1` and valid date range
- Return `price_per_ton` and `currency`
- Calculate `total_value_estimate` in summary

### Frontend
Update display to show:
- Price per ton
- Currency
- Total estimated cost
- Price history (future enhancement)

## Maintenance

### Adding New Prices
```sql
INSERT INTO supplier_prices
(supplier_id, fuel_type_id, price_per_ton, currency, is_active, valid_from)
VALUES (1, 25, 860.00, 'USD', 1, CURDATE());
```

### Updating Price (expire old, create new)
```sql
-- Deactivate old price
UPDATE supplier_prices
SET is_active = 0, valid_until = CURDATE()
WHERE supplier_id = 1 AND fuel_type_id = 25 AND is_active = 1;

-- Insert new price
INSERT INTO supplier_prices
(supplier_id, fuel_type_id, price_per_ton, currency, is_active, valid_from)
VALUES (1, 25, 870.00, 'USD', 1, CURDATE());
```

### Price History Query
```sql
SELECT
    valid_from,
    valid_until,
    price_per_ton,
    currency,
    is_active
FROM supplier_prices
WHERE supplier_id = 1 AND fuel_type_id = 25
ORDER BY valid_from DESC;
```

## Future Enhancements

1. **Price Alerts**: Notify when prices change significantly
2. **Volume Discounts**: Tiered pricing based on quantity
3. **Contract Management**: Link prices to contracts
4. **Multi-currency conversion**: Automatic currency conversion
5. **Price Forecasting**: ML-based price prediction
6. **Seasonal Pricing**: Different prices for different seasons
