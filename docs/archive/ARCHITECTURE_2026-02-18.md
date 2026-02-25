# Units, Conversions & Data Architecture
## Fuel Management System REV 3.0

> **Purpose:** Prevent confusion, bugs, and re-discussion of fundamental decisions.
> **Mandatory reading** before touching any calculation, DB schema, or API field.

---

## ⚠️ The Core Problem

Fuel business uses THREE different measurement systems simultaneously:
- **Volume** (литры, м³) — for tanks, stock levels
- **Mass** (тонны) — for orders, prices, supplier contracts
- **Time** (дни) — for delivery, planning

These are NOT interchangeable without density. **1000 litres of LPG ≠ 1000 litres of Diesel in tons.**

---

## 📐 Conversion Rules (IMMUTABLE)

### Volume conversions (density-independent)
```
м³ → литры:   multiply by 1000       (1 м³ = 1000 L)
литры → м³:   divide by 1000         (1000 L = 1 м³)
```
> ✅ Volume conversions do NOT need density. 1000L of LPG and 1000L of Diesel
> occupy the same physical space in a tank.

### Mass ↔ Volume conversions (density-REQUIRED)
```
литры → тонны:  litres × density_kg_per_litre / 1000
тонны → литры:  tons   × 1000 / density_kg_per_litre
```

### Real examples (why density matters):

| Fuel     | Density (kg/L) | 1000 litres = ? tons | 1 ton = ? litres |
|----------|---------------|---------------------|-----------------|
| LPG      | 0.535         | **0.535 tons**      | 1,869 L         |
| А-92     | 0.735         | **0.735 tons**      | 1,361 L         |
| Diesel B7| 0.830         | **0.830 tons**      | 1,205 L         |
| Diesel B10| 0.850        | **0.850 tons**      | 1,176 L         |

> ❌ NEVER assume 1 ton = 1000 litres. It is NEVER true for any fuel.

### Density source
Always use `fuel_types.density` (kg/litre). Never hardcode density values.

---

## 🗄️ Database Storage Standard

### Rule: Store volume in LITRES, mass in TONS

| What | Unit | Why |
|------|------|-----|
| Tank capacity | **litres** | Physical tank measurement |
| Current stock | **litres** | Physical measurement |
| Daily consumption (sales_params) | **litres/day** | Physical flow measurement |
| Tank unloading capacity | **litres/day** | Physical pump/pipe capacity, fuel-type-independent |
| Order volume | **tons** | Industry standard, supplier contracts |
| Price | **USD (or local) per ton** | Industry standard |
| Minimum order | **tons** | Supplier contract terms |
| Working capital value | **USD** | Financial reporting |

### Rule: NEVER store the same data in two units
```
❌ BAD:  capacity_liters + capacity_m3 + capacity_tons (3 columns for same data)
✅ GOOD: capacity_liters only → convert to m³ or tons on-the-fly via API/frontend
```

---

## 🔧 Conversion in PHP (UnitConverter utility)

```php
// Always use UnitConverter — never inline conversion formulas

UnitConverter::litreToTon(float $litres, float $density): float
// = litres * density / 1000

UnitConverter::tonToLitre(float $tons, float $density): float
// = tons * 1000 / density

UnitConverter::litreToM3(float $litres): float
// = litres / 1000  (NO density needed — volume conversion)

UnitConverter::m3ToLitre(float $m3): float
// = m3 * 1000      (NO density needed — volume conversion)
```

---

## 📋 Table-by-Table Reference

### `depot_tanks` — SOURCE OF TRUTH for stock
```
capacity_liters       → litres  ✅
current_stock_liters  → litres  ✅
```
Display conversions (on-the-fly):
- UI показывает тонны: `current_stock_liters × density / 1000`
- UI показывает %: `current_stock_liters / capacity_liters × 100`

### `depots` — Physical depot info
```
daily_unloading_capacity_liters → litres/day ✅
```
> Why litres? Because unloading capacity is a physical pipe/pump limit.
> It applies regardless of fuel type. Converting to tons would require
> knowing which fuel is being unloaded — impractical.
>
> ❌ REMOVED: capacity_m3 (redundant — sum of depot_tanks.capacity_liters)

### `sales_params` — Daily consumption rates
```
liters_per_day → litres/day ✅
```
Display: `liters_per_day × density / 1000` → tons/day for UI

### `fuel_types` — Fuel definitions
```
density         → kg/litre (e.g. 0.830 for Diesel B7) ✅
cost_per_ton    → USD/ton ✅ (updated manually, used for Working Capital)
```

### `supplier_station_offers` — Prices & delivery
```
price_per_ton   → USD/ton per fuel_type_id ✅
delivery_days   → integer days ✅
min_order_tons  → tons ✅
max_order_tons  → tons ✅
```

### `orders` — Actual orders placed
```
volume_tons     → tons ✅ (supplier documents use tons)
price_per_ton   → USD/ton ✅
```

### `system_parameters` — Global config
```
planned_fill_pct        → % (0-100, stored as float)
critical_fill_pct       → % (0-100, stored as float)
max_useful_volume_pct   → % (0-100, stored as float)
delivery_buffer_days    → integer days
order_step_tons         → tons (1 wagon = 60 tons)
min_order_tons          → tons
opportunity_cost_rate   → % per year (e.g. 8.0)
```

---

## 🖥️ Frontend Display Rules

| Context | Display unit | Conversion |
|---------|-------------|-----------|
| Tank fill bar | % | `stock / capacity × 100` |
| Stock level (large tanks) | тонны | `litres × density / 1000` |
| Stock level (small tanks) | литры | as-is |
| Daily consumption | тонны/день | `liters_per_day × density / 1000` |
| Order volume | тонны | as-is (stored in tons) |
| Tank capacity | тонны | `capacity_liters × density / 1000` |
| Price | USD/тонна | as-is |
| Working capital | USD | `stock_litres × density / 1000 × cost_per_ton` |

> Rule: Show tons to users for business decisions.
> Show % for visual indicators.
> Store litres internally.

---

## ❓ Open Questions (answered)

**Q: Why not store everything in tons?**
A: Tanks are physical objects measured in volume (litres/m³). Converting to tons
requires knowing which fuel is in the tank — but tanks can be re-filled with
different fuel grades. Volume is fuel-type-agnostic for capacity purposes.

**Q: Why not store everything in m³?**
A: Litres are more common in operational documents in this region.
m³ = litres/1000, trivial to convert. No practical advantage.

**Q: Can I add a `capacity_tons` column for convenience?**
A: NO. It would be derived data and would get out of sync.
Calculate on-the-fly: `capacity_liters × density / 1000`.

**Q: Supplier says "500 m³ order" — how to handle?**
A: Convert at import boundary: `m3 × 1000 × density / 1000 = m3 × density` tons.
Store as tons in orders table.

**Q: daily_unloading_capacity — why litres not tons?**
A: Physical constraint of pumps/pipes is volume-based (flow rate).
Different fuels have different densities, so the same pipe delivers
different tonnage for different fuels. Litres/day is the correct unit.

---

## 🚨 Common Mistakes to Avoid

```
❌ tons = litres / 1000          (WRONG — ignores density)
❌ litres = tons * 1000          (WRONG — ignores density)
✅ tons = litres * density / 1000
✅ litres = tons * 1000 / density

❌ m3 = tons / density           (WRONG unit mix)
✅ m3 = litres / 1000            (volume conversion, no density needed)

❌ Storing capacity in both litres AND m³
✅ Store in litres only, show m³ = litres/1000 when needed

❌ Hardcoding density values (e.g. 0.83)
✅ Always JOIN fuel_types to get density dynamically
```

---

## 📅 Decision Log

| Date | Decision | Reason |
|------|----------|--------|
| 2026-02-18 | Internal storage: volume in litres, mass in tons | Tanks measured in litres; orders in tons per industry standard |
| 2026-02-18 | Remove `depots.capacity_m3` | Redundant — derivable from sum of depot_tanks |
| 2026-02-18 | `daily_unloading_capacity` → litres/day | Physical pump capacity is volume-based, fuel-type-agnostic |
| 2026-02-18 | `fuel_types.cost_per_ton` added | For Working Capital module; updated manually |
| 2026-02-18 | `supplier_station_offers` prices in USD/ton per fuel_type row | Industry standard; avoids column-per-fuel-type anti-pattern |

---

*Last updated: 2026-02-18*
*Owner: System Architecture*
