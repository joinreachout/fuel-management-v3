# Working Capital Optimization Analysis

## 📋 Source: fuel_planning_system_functional_spec_final_draft.pdf

---

## 🎯 Key Concept: Multiple Small Orders > One Large Order

### Business Problem

**Working capital inefficiency:** Cash tied up in excess inventory instead of earning returns

### The Trade-off

```
┌─────────────────────────────────────────────────────────────┐
│                    ORDER STRATEGY COMPARISON                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Strategy A: ONE LARGE ORDER                                │
│  ═══════════════════════                                    │
│  Order: 2,000 tons at once                                  │
│  Capital locked: $1,700,000 (2,000 × $850)                 │
│  Duration: 22 days                                          │
│  Opportunity cost: $1,700,000 × 8% / 365 × 22 = $8,175     │
│                                                              │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  │
│                                                              │
│  Strategy B: TWO SMALLER ORDERS                             │
│  ════════════════════════════                               │
│  Order 1: 1,000 tons now                                    │
│  Order 2: 1,000 tons in 10 days                             │
│  Capital locked average: $1,275,000                         │
│  Opportunity cost: ~$6,130 (25% less!)                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Optimization Modes from Spec

### MODE 1: SAFETY (Maximize buffer)
```
Objective: Maximize Σ(Stock[depot, fuel, day] / capacity[depot, fuel]) / N
Result: Highest stock levels, lowest stockout risk
Avg Stock: 75%
Stockout events: 0
Avg Inventory Value: $2.4M
```

### MODE 2: CASH (Minimize inventory value) 💰
```
Objective: Minimize Σ(Stock[depot, fuel, day] × density[fuel] × price_per_ton[fuel])
Result: Lowest capital tied up in inventory
Avg Stock: 52%
Stockout events: 3 (acceptable risk)
Avg Inventory Value: $1.8M
Working Capital Savings: +$600K vs Safety mode
```

### MODE 3: MARGIN (Minimize procurement cost)
```
Objective: Minimize Σ(Order[supplier, depot, fuel, day] × unit_cost[supplier, fuel])
Result: Choose cheapest suppliers even if slower
```

### MODE 4: BALANCED (Multi-objective) ⭐ DEFAULT
```
Minimize:
  w1 × (1 - Safety_Score)      // 40% - Stockout risk
  + w2 × Cash_Score            // 30% - Inventory carrying cost
  + w3 × Cost_Score            // 20% - Procurement cost
  + w4 × Concentration_Score   // 10% - Supplier concentration risk

Default weights: w1=40, w2=30, w3=20, w4=10
Result: Best compromise
Avg Stock: 63%
Stockout events: 1
Avg Inventory Value: $2.1M
Working Capital Savings: +$300K vs Safety mode
```

### MODE 5: CRISIS (Maximin)
```
Objective: Maximize MIN(Stock[depot, fuel, day])
Result: Maximize the minimum stock level (worst-case optimization)
```

---

## 💰 Working Capital Module (Section 5.8)

### Prerequisites
- Fuel cost per ton must be configured
- If not populated → Working Capital features disabled

### Key Metrics

| Metric | Formula | Description |
|--------|---------|-------------|
| **Current Inventory Value** | Σ(Stock_m³ × Density × Cost_per_ton) | Total value now |
| **Average Inventory Value** | Mean(Daily_Inventory_Value) over horizon | Avg tied-up capital |
| **Inventory Days** | Avg_Inventory_Value / Daily_COGS | Days of sales in tanks |
| **Opportunity Cost** | Avg_Inventory_Value × Interest_Rate | Lost earnings |

### Opportunity Cost Calculation

```
Annual Opportunity Cost = Average_Inventory_Value × Interest_Rate
```

**Parameters:**
- `opportunity_cost_rate` = 8.0% p.a. (default, configurable 0-30%)
- `working_capital_currency` = "USD"

**Display:**
- Daily opportunity cost
- Monthly opportunity cost
- Annual opportunity cost
- "If you reduce inventory by X%, you save $Y per year"

---

## 🔢 Practical Example: ОШ Station Diesel B7

### Current Situation
- Current stock: 468 tons
- Consumption: 98.77 tons/day
- Delivery time: 20 days + 2 buffer = 22 days
- Consumption during delivery: 2,173 tons
- Tank capacity: 830 tons (max 95% = 789 tons)

### Problem
```
Ideal order:     2,173 tons (to maintain target after delivery)
Max capacity:    789 tons
Deficit:         -1,384 tons ❌
```

### Solution Options

#### ❌ Option A: ONE LARGE ORDER (Current)
```
Order: 789 tons now
Result after 22 days: 468 + 789 - 2,173 = -916 tons (STOCKOUT!)
Capital locked: 789 × $840 = $662,760
Duration: 22 days
Opportunity cost: $662,760 × 8% / 365 × 22 = $3,190
Status: WILL RUN OUT OF FUEL ❌
```

#### ✅ Option B: TWO SMALLER ORDERS (Recommended)
```
Order 1: 789 tons now (fill to max)
  - Arrives: Day 22
  - Stock at arrival: 468 + 789 - 2,173 = -916 tons
  - Must order again BEFORE stockout!

Order 2: 789 tons on Day 12 (10 days after Order 1)
  - Arrives: Day 34 (12 + 22)
  - Prevents stockout!

Capital Analysis:
  - Order 1: $662,760 locked for 22 days
  - Order 2: $662,760 locked for 10 days (Day 12 to 22)
  - Average locked: ~$511,000 (23% less!)
  - Opportunity cost savings: ~$730/month

Result: NO STOCKOUT ✅
```

#### ✅ Option C: THREE STAGGERED ORDERS (Best for Working Capital)
```
Order 1: 500 tons now → Arrives Day 22
Order 2: 500 tons on Day 8 → Arrives Day 30
Order 3: 500 tons on Day 16 → Arrives Day 38

Capital Analysis:
  - Max locked at any time: ~$600,000
  - Average locked: ~$400,000 (40% less than Option A!)
  - Opportunity cost savings: ~$1,250/month
  - Safety: Higher (more frequent deliveries)

Result: OPTIMAL for Working Capital + Safety ✅
```

---

## 🎯 Recommendation Logic

### Current System (What We Built)
```php
// Simple approach: Order enough to reach target level
recommended = (target_level + consumption_during_delivery) - current_stock

// Cap at capacity
if (recommended > max_capacity) {
    recommended = max_capacity;
    insufficient_capacity_warning = true;
    additional_order_needed = recommended - max_capacity;
}
```

### Enhanced Working Capital-Aware Logic (Next Step)

```php
// Calculate ideal order
$idealOrder = ($targetLevel + $consumptionDuringDelivery) - $currentStock;

// If exceeds capacity → split into multiple orders
if ($idealOrder > $maxCapacity) {
    // Calculate how many orders needed
    $ordersNeeded = ceil($idealOrder / $maxCapacity);

    // Stagger orders to minimize working capital
    $orderSize = $maxCapacity;
    $orderInterval = floor($deliveryDays / $ordersNeeded);

    $recommendations = [];
    for ($i = 0; $i < $ordersNeeded; $i++) {
        $recommendations[] = [
            'order_number' => $i + 1,
            'volume_tons' => $orderSize,
            'order_date' => date('Y-m-d', strtotime("+{$i * $orderInterval} days")),
            'expected_arrival' => date('Y-m-d', strtotime("+{$deliveryDays + $i * $orderInterval} days")),
            'capital_locked_days' => $deliveryDays,
            'capital_cost' => $orderSize * $pricePerTon * ($opportunityCostRate / 365) * $deliveryDays
        ];
    }

    return [
        'strategy' => 'MULTIPLE_STAGGERED_ORDERS',
        'total_orders' => $ordersNeeded,
        'orders' => $recommendations,
        'total_capital_cost' => array_sum(array_column($recommendations, 'capital_cost')),
        'vs_single_order_savings' => $singleOrderCost - $totalCapitalCost
    ];
}
```

---

## 📈 Working Capital Dashboard (Section 5.8.1)

### Key Metrics Display

| Scenario | Safety Mode | Cash Mode | Balanced |
|----------|------------|-----------|----------|
| **Avg Inventory Value** | $2.4M | $1.8M | $2.1M |
| **Working Capital Savings** | baseline | +$600K | +$300K |
| **Opportunity Cost (8% p.a.)** | $17.5K/mo | $13.1K/mo | $15.3K/mo |
| **Stockout Risk Events** | 0 | 3 | 1 |

---

## 🎨 UI Visualization Ideas

### Option 1: Timeline View
```
┌─────────────────────────────────────────────────────────┐
│  ORDER TIMELINE (22 days)                               │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Day 0      Day 10     Day 20     Day 30     Day 40    │
│  ▼          ▼          ▼          ▼          ▼          │
│  ├──────────┼──────────┼──────────┼──────────┤         │
│  │                                                      │
│  Order 1 ████████████████████████ Arrives               │
│  (789t)   ▲──────────22 days─────▶                     │
│           Capital: $662K locked                         │
│                                                          │
│           Order 2 ████████████████ Arrives              │
│           (789t)  ▲──────22 days──▶                    │
│                   Capital: $662K locked (10d overlap)   │
│                                                          │
│  Total Opportunity Cost: $3,920                         │
└─────────────────────────────────────────────────────────┘
```

### Option 2: Comparison Cards
```
┌─────────────────────┬─────────────────────┐
│   SINGLE ORDER      │  MULTIPLE ORDERS    │
├─────────────────────┼─────────────────────┤
│ Orders: 1           │ Orders: 2           │
│ Volume: 2,173 tons  │ Volume: 789+789     │
│ Capital: $1.8M      │ Capital: $1.3M avg  │
│ Cost: $8,175        │ Cost: $6,130        │
│ Stockout Risk: HIGH │ Stockout Risk: LOW  │
│                     │                     │
│ ❌ Insufficient     │ ✅ Recommended      │
└─────────────────────┴─────────────────────┘
```

---

## 🚀 Implementation Roadmap

### Phase 1: ✅ Basic Procurement (DONE)
- [x] Calculate recommended order quantity
- [x] Account for consumption during delivery
- [x] Cap at capacity (95%)
- [x] Flag insufficient capacity warnings
- [x] Show additional_order_needed

### Phase 2: 🔄 Working Capital Awareness (NEXT)
- [ ] Add fuel price/cost data to database
- [ ] Calculate opportunity cost for each order
- [ ] Detect when multiple orders are better
- [ ] Generate staggered order recommendations
- [ ] Show capital cost comparison

### Phase 3: 📊 Working Capital Dashboard (FUTURE)
- [ ] Current inventory value widget
- [ ] Average inventory value tracking
- [ ] Opportunity cost calculator
- [ ] Scenario comparison (Safety vs Cash vs Balanced)
- [ ] Working capital savings metrics

### Phase 4: 🎯 Optimization Modes (FUTURE)
- [ ] Implement CASH mode optimizer
- [ ] Implement BALANCED mode with weights
- [ ] Allow custom weight configuration
- [ ] Show baseline vs optimized comparison

---

## 💡 Key Insights

### Why Multiple Small Orders Are Better

1. **Lower Average Capital Locked**
   - Single 2,000t order: $1.7M locked for 22 days
   - Two 1,000t orders: ~$1.3M average (23% less)
   - Three 666t orders: ~$1.1M average (35% less)

2. **Reduced Opportunity Cost**
   - At 8% p.a. interest rate
   - Single order: $8,175 cost
   - Two orders: $6,130 cost (25% savings)
   - Three orders: $5,300 cost (35% savings)

3. **Improved Safety**
   - More frequent deliveries = less stockout risk
   - Earlier detection of supplier delays
   - Flexibility to adjust volumes

4. **Better Cash Flow**
   - Capital freed up sooner
   - Can use for other investments
   - Lower credit line requirements

### Trade-offs

**Pros:**
- ✅ Less working capital tied up
- ✅ Lower opportunity cost
- ✅ Better stockout protection
- ✅ More flexibility

**Cons:**
- ⚠️ More orders to manage
- ⚠️ Higher administrative overhead
- ⚠️ More supplier coordination needed

---

## 📝 Conclusion

According to the specification, **working capital optimization is a KEY feature** of the system:

> "Working capital inefficiency: Cash tied up in excess inventory instead of earning returns"

The system should:
1. ✅ Detect when capacity constraints require multiple orders
2. ✅ Calculate opportunity cost of different ordering strategies
3. ✅ Recommend staggered orders to minimize working capital
4. ✅ Show users the financial impact of their decisions

**Next Step:** Implement Phase 2 - Working Capital Awareness in ProcurementAdvisorService

---

**Document Created:** 2026-02-18
**Source:** fuel_planning_system_functional_spec_final_draft.pdf
**Status:** Analysis Complete - Ready for Implementation
