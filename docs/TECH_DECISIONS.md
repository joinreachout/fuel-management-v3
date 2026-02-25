# Technical Decisions & Architecture
## Fuel Management System REV 3.0

> **Purpose:** Prevent confusion, bugs, and re-discussion of decisions already made.
> Read this before touching any calculation, unit conversion, PDF, or library choice.

---

## 1. Units & Conversions (IMMUTABLE RULES)

### The problem
Fuel uses THREE measurement systems simultaneously:
- **Volume** (liters, m³) — tanks, stock levels
- **Mass** (tons) — orders, prices, supplier contracts
- **Time** (days) — delivery, planning

**1000L of LPG ≠ 1000L of Diesel in tons.** They are NOT interchangeable without density.

### Storage standard

| What | Stored as | Displayed as |
|------|-----------|-------------|
| Tank capacity | **liters** | liters or tons (on-the-fly) |
| Current stock | **liters** | tons for business, % for fill indicator |
| Daily consumption (`sales_params`) | **liters/day** | tons/day for display |
| Order quantity | **liters** in DB | **tons** in UI (entered by user) |
| Order price | **USD/ton** | USD/ton |

### Conversion formulas
```
tons   = liters × density_kg_per_L / 1000
liters = tons   × 1000 / density_kg_per_L
m³     = liters / 1000    (no density needed — volume conversion)
liters = m³     × 1000    (no density needed — volume conversion)
```

> ❌ NEVER: `tons = liters / 1000` (WRONG — ignores density)
> ❌ NEVER: hardcode density values like `0.84`
> ✅ ALWAYS: JOIN `fuel_types` to get `density` dynamically

### PHP: UnitConverter utility
```php
// backend/src/Utils/UnitConverter.php — ALWAYS use these, never inline
UnitConverter::litreToTon(float $litres, float $density): float   // = litres * density / 1000
UnitConverter::tonToLitre(float $tons,   float $density): float   // = tons * 1000 / density
UnitConverter::litreToM3(float $litres): float                    // = litres / 1000
UnitConverter::m3ToLitre(float $m3): float                        // = m3 * 1000
```

### Real density values (reference only — always use DB)
| Fuel | Density (kg/L) | 1000L = ? tons |
|------|---------------|---------------|
| LPG | 0.535 | 0.535 t |
| A-92 | 0.735 | 0.735 t |
| Diesel B7 | 0.830 | 0.830 t |
| Diesel B10 | 0.850 | 0.850 t |

### Decision log
| Date | Decision | Reason |
|------|----------|--------|
| 2026-02-18 | Stock in liters, orders in tons | Tanks measured in liters physically; orders in tons per industry standard |
| 2026-02-18 | `daily_unloading_capacity` → liters/day | Physical pump capacity is volume-based, fuel-type-agnostic |
| 2026-02-18 | Removed `depots.capacity_m3` | Redundant — derivable from `SUM(depot_tanks.capacity_liters)` |
| 2026-02-18 | `sales_params.liters_per_day` (reverted from `tons_per_day`) | Liters is the measurement unit of physical flow |
| 2026-02-18 | Removed `fuel_types.cost_per_ton` | Pricing belongs in `supplier_station_offers.price_per_ton` per supplier |

---

## 2. Key Calculations

### Days until empty
```
days_until_empty = current_stock_liters / liters_per_day
```

### Procurement order quantity (correct formula)
```
consumption_during_delivery = liters_per_day × delivery_days
stock_at_arrival             = current_stock_liters - consumption_during_delivery
needed_to_reach_target       = target_level_liters - stock_at_arrival
order_liters                 = needed_to_reach_target  (capped at tank capacity)
order_tons                   = order_liters × density / 1000
```
> Key insight: must order enough for transit consumption AND reach target on arrival.
> The naive `target - current` formula is WRONG.

### Forecast chart data
```
for each day i in [0..horizon]:
    projected_kL = (current_stock_liters - liters_per_day × i) / 1000
    floor at 0 (stock can't go negative)
```
Y-axis displayed in **kL** for readability.

### Working capital
```
stock_value_USD = current_stock_liters × density / 1000 × price_per_ton
```

---

## 3. PDF Generation

### Stack
- **Library:** jsPDF **v2.5.1** (in `package.json` — do NOT upgrade to v4.x)
- **Font:** Roboto-Regular.ttf embedded as base64 in `frontend/src/utils/robotoBase64.js`
- **Code:** `downloadPoPdf(order)` in `Orders.vue`

### Why jsPDF v2.5.1 (NOT v4.x)
jsPDF v4 has stricter TTF parsing and throws `No unicode cmap for font` for our Roboto
even though the font has correct format-4 cmap tables. v2.5.1 is stable with this font.
`package.json` must stay `"jspdf": "^2.5.1"`.

### Why font is embedded as base64 module (NOT fetched at runtime)
**The broken approach** (do NOT use):
```js
// CORRUPTS bytes > 127:
binary += String.fromCharCode(...arr.subarray(j, j + 8192))
_cache = btoa(binary)
```
`String.fromCharCode` with spread on Uint8Array produces wrong Latin-1 mapping for
non-ASCII bytes → `btoa()` encodes the wrong thing → jsPDF gets a corrupted font.

**The correct approach:**
```bash
# Run once when font changes:
python3 -c "
import base64
with open('frontend/public/fonts/Roboto-Regular.ttf', 'rb') as f: data = f.read()
b64 = base64.b64encode(data).decode('ascii')
with open('frontend/src/utils/robotoBase64.js', 'w') as out:
    out.write('// Roboto-Regular base64 — pre-encoded by Python, Cyrillic support\n')
    out.write(f'export const robotoBase64 = \"{b64}\";\n')
"
```

**Usage in Vue:**
```js
const [{ jsPDF }, { robotoBase64: fontB64 }] = await Promise.all([
  import('jspdf'),
  import('../utils/robotoBase64.js'),   // lazy chunk ~168KB, only on first PDF click
])
doc.addFileToVFS('Roboto-Regular.ttf', fontB64)
doc.addFont('Roboto-Regular.ttf', 'Roboto', 'normal')
```
Vite splits `robotoBase64.js` into a separate lazy chunk. No CDN, no fetch, no runtime conversion.

### PDF layout (REV 2.0 style)
1. Navy header bar (42mm): `KITTY KAT TECHNOLOGIES` + PO number badge + status dot
2. Dates row: ORDER DATE, DELIVERY DATE | ORDER ID, CURRENCY
3. SUPPLIER box (light blue bg) + DELIVERY DESTINATION box (light green bg)
4. Items table: navy header → PRODUCT | QTY(T) | VOLUME(L) | PRICE/TON | TOTAL
5. Totals: Subtotal → VAT 12% → Grand Total (navy bar)
6. Notes (if present)
7. Terms & Conditions (5 standard clauses)
8. Signature blocks (Buyer + Supplier)
9. Footer (gray bg): company + URL left | Generated date + Page 1 of 1 right

---

## 4. Vue 3: ref() vs reactive() in Templates

### The bug
```js
// BROKEN — causes TypeError in sort functions:
const poSort = ref({ key: 'id', dir: 'desc' })
```
```html
<!-- Vue auto-unwraps ref when passed as function arg from template: -->
<th @click="toggleSort(poSort, 'id')">
```
Inside `toggleSort(sortObj, key)`, `sortObj` receives the unwrapped plain `{key, dir}` object.
`sortObj.value` → `undefined` → `TypeError: Cannot read properties of undefined`.

### The fix
```js
// CORRECT — reactive() is never auto-unwrapped by Vue templates:
const poSort  = reactive({ key: 'id', dir: 'desc' })
const erpSort = reactive({ key: 'id', dir: 'desc' })
```
Access properties directly as `sortObj.key` / `sortObj.dir` — no `.value` needed.

**Rule:** Use `reactive()` for objects that are passed as function arguments from templates.

---

## 5. Development Principles

### Code standards
- PSR-12 (PHP), English only in code/comments
- No hardcoded values — all config from DB or env
- DRY — single source of truth for every piece of data
- MVC+Services: Model=DB, Service=logic, Controller=HTTP, Utils=pure functions
- No N+1 queries (use JOINs)
- Prepared statements everywhere (PDO)
- Type hints everywhere in PHP: `function get(int $id): ?Station`
- Small functions (one responsibility), no god classes (max ~200 lines)

### Commit checklist
- [ ] No hardcoded values?
- [ ] No duplicate code?
- [ ] All English?
- [ ] Correct layer (Model/Service/Controller/Utils)?
- [ ] No N+1 queries?
- [ ] Meaningful commit message?
- [ ] `npm run build` done before pushing?

### API response format (consistent everywhere)
```json
{ "success": true,  "data": [...], "count": N }
{ "success": false, "error": "Error message in English" }
```
HTTP codes: `200` success, `201` created, `400` bad request, `404` not found, `500` server error.

---

## 6. Deploy Pipeline

```bash
# From worktree: /REV 3.0/.claude/worktrees/optimistic-chebyshev
npm run build                           # build frontend
git add -f frontend/dist/              # force-add built dist
git add backend/ frontend/src/         # source changes
git commit -m "descriptive message"

# Merge worktree branch → main and push
cd /REV 3.0
git merge claude/optimistic-chebyshev --ff-only
git push origin main

# Trigger server pull (no SSH on shared hosting)
# Open: https://fuel.kittykat.tech/rev3/update.html
```

> `main` worktree is at `/REV 3.0/` (not in `.claude/worktrees/`).
> `claude/optimistic-chebyshev` worktree is at `/REV 3.0/.claude/worktrees/optimistic-chebyshev/`.
> All development happens in the worktree. Merge to main only to deploy.
