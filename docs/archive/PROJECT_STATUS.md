# Fuel Management System REV 3.0 — Project Status
> Last updated: 2026-02-23

---

## 🟢 System Status: LIVE
**URL:** https://fuel.kittykat.tech/rev3/
**DB:** d105380_fuelv3 (MySQL, shared hosting)
**Deployment:** local build → git push → update.html on server

---

## ✅ Completed Features

### 1. Core Infrastructure
| Компонент | Статус | Описание |
|-----------|--------|----------|
| PHP REST API | ✅ Live | Custom router, no framework |
| Vue 3 SPA | ✅ Live | Composition API, `<script setup>` |
| MySQL DB | ✅ Live | 9 stations, 11 suppliers, 10 fuel types |
| Git deploy pipeline | ✅ Live | `npm run build` → `git add -f dist/` → push → update.html |

---

### 2. Dashboard
- Real-time stock overview across 9 stations
- Fuel level forecast chart (Station Level, Tons)
- KPI cards: Total Stations, Shortages Predicted, Below Threshold, Mandatory Orders
- Critical alert banner when stations below threshold
- Filter by: Region / Station / Fuel Type / Forecast horizon (30/60/90 days)

---

### 3. Parameters (System Configuration)

#### Infrastructure Tab
- Hierarchy view: Regions → Stations → Depots → Tanks
- Shows current stock (L) and capacity (L) per tank with fill % bar
- Expandable tree (Expand all / Collapse)

#### Supply Offers Tab ⭐ (major redesign 2026-02-18)
- **Card layout** — one card per supplier (replaces 497-row flat table)
- Each card shows:
  - Fuel types supplied + prices (USD/ton) — inline editable
  - Delivery days per station — inline editable
- **Smart save logic:**
  - Editing price → updates ALL `supplier_station_offers` rows for that `supplier_id + fuel_type_id` (all stations at once)
  - Editing delivery days → updates ALL rows for that `supplier_id + station_id` (all fuel types at once)
- **Add Supplier** button + modal (name → POST `/api/suppliers` → card appears)
- 11 suppliers × 9 stations migrated from old DB

#### Sales Params Tab
- Daily consumption rate (liters/day) per depot per fuel type
- Inline editable — feeds all forecast and procurement calculations

#### Stock Policies Tab
- Per-depot thresholds: Critical Level / Min Level / Target Level (liters)
- Inline editable

#### Fuel Types Tab ⭐ (redesigned 2026-02-18)
- Color-coded cards (one per fuel type, 5 per row)
- Color scheme by fuel grade:
  - A-98 → purple, A-95 → dark blue, A-92 Euro → blue, A-92 → blue
  - A-80 → cyan, Diesel B10 → dark yellow, Diesel B7 → yellow
  - Jet Fuel → sky, LPG/GAZ → green, MTBE → pink
- Density (kg/L) — inline editable

#### System Parameters Tab
- Global planning thresholds (planning_horizon_days, safety_stock_days, etc.)
- Inline editable, grouped by category

#### Depot Tanks Tab
- Physical tank inventory — reference view
- Current stock + capacity + fill % bar per tank

---

### 4. Forecast Engine

**Algorithm:**
```
for each depot+fuel_type with active sales_param:
    days_until_empty = current_stock_liters / liters_per_day
    days_until_minimum = (current_stock_liters - min_level_liters) / liters_per_day
    days_until_critical = (current_stock_liters - critical_level_liters) / liters_per_day

Alert levels:
    stock ≤ critical_level  →  CATASTROPHE
    stock ≤ min_level       →  CRITICAL
    days_until_min ≤ 3      →  MUST_ORDER
    otherwise               →  NORMAL
```

**Chart data:**
```
for each day i in [0 .. horizon]:
    projected_kL = max(0, (current_stock_L - liters_per_day × i) / 1000)
```
Displayed in **kiloliters (kL)** on Y-axis.

---

### 5. Procurement Advisor

**Order quantity algorithm (fixed 2026-02-18):**
```
consumption_during_delivery = liters_per_day × delivery_days
stock_at_arrival = current_stock_liters - consumption_during_delivery
needed = target_level_liters - stock_at_arrival
order_liters = min(needed, available_tank_capacity)
order_tons = order_liters × density / 1000
```

> ⚠️ Previous (wrong) formula was `order = target - current`, which ignored consumption during transit — fuel ran out before delivery arrived.

**Supplier selection:** picks best offer from `supplier_station_offers` by price and delivery days.

**Outputs:**
- Recommended order quantity (tons)
- Estimated cost (USD)
- Expected delivery date
- Warnings: insufficient capacity, stock below critical

---

### 6. Unit System

| Контекст | Единица |
|----------|---------|
| DB storage (stock) | Liters (L) |
| DB column | `liters_per_day` |
| Forecast chart | Kiloliters (kL) |
| Procurement orders | Tons |
| Supplier prices | USD / ton |
| Density | kg/L |

**Conversion formulas:**
```
tons   = liters × density / 1000
liters = tons × 1000 / density
kL     = L / 1000
```

---

### 7. Data Migration (2026-02-18)

**Source:** `d105380_fueloptimization` (old system)
**Target:** `d105380_fuelv3` (new system)

**Old DB structure:**
- `suppliers` — 10 suppliers
- `delivery_times` — supplier_id × station_id → delivery_days
- `fuel_prices` — supplier_id × fuel_type_id → price_per_ton

**New DB structure:**
- `supplier_station_offers` — supplier_id × station_id × fuel_type_id → price + days (unified)

**Migration script:** `migration_suppliers.sql`
- 9 missing suppliers inserted (id=8 НПЗ Кара май already existed)
- ~497 `supplier_station_offers` rows generated
- Station IDs identical between old and new DB (249–257) — no mapping needed
- Fuel type IDs identical between old and new DB (24–35) — no mapping needed
- Станция Аламедин (256) absent from old DB → delivery_days = same as Бишкек (250)

---

## 📊 Database Reference

### Stations
| ID | Name | Code |
|----|------|------|
| 249 | Станция Каинда | KAIN |
| 250 | Станция Бишкек | BISH |
| 251 | Станция Рыбачье | RYB |
| 252 | Станция ОШ | OSH |
| 253 | Станция Жалал-Абад | JALAL |
| 254 | Станция Кызыл-Кыя | KYZYL |
| 255 | Станция Шопоков | SHOPO |
| 256 | Станция Аламедин | ALAM |
| 257 | Станция Токмок | TOKMOK |

### Fuel Types
| ID | Name | Code | Density |
|----|------|------|---------|
| 24 | A-92 | GAS92 | 0.735 |
| 25 | Diesel B7 | DIESB7 | 0.830 |
| 26 | LPG | GAZ | 0.535 |
| 27 | Jet Fuel | JET | 0.800 |
| 28 | MTBE | MTBE | 0.740 |
| 31 | A-95 | GAS95 | 0.750 |
| 32 | A-98 | GAS98 | 0.760 |
| 33 | Diesel B10 | DIESB10 | 0.850 |
| 34 | A-80 | GAS80 | 0.728 |
| 35 | A-92 Euro | GAS92EUR | 0.735 |

### Suppliers (11 total)
| ID | Name |
|----|------|
| 1 | ОРСК |
| 2 | Тайф-НК |
| 3 | Нижневартовск |
| 4 | ОАО Газпром нефтехим Салават |
| 5 | Пурнефтепеработка (Роснефть) |
| 6 | ООО НС Ойл |
| 7 | КрНПЗ |
| 8 | НПЗ Кара май Ойл-Тараз |
| 9 | ТОО Актобе нефтепереработка |
| 10 | ООО Лукоил-Пермнефтеоргсинтез |
| 21 | Мозырский НПЗ |

---

## 🗂 Key Files

### Backend (`backend/src/`)
| Файл | Назначение |
|------|-----------|
| `Controllers/ParametersController.php` | CRUD для всех config таблиц |
| `Controllers/SupplierController.php` | GET/POST suppliers |
| `Controllers/ForecastController.php` | Прогноз запасов |
| `Controllers/ProcurementController.php` | Рекомендации по закупкам |
| `Services/ForecastService.php` | Алгоритм прогноза, критические танки |
| `Services/ParametersService.php` | SQL для всех config таблиц |
| `Services/ProcurementAdvisorService.php` | Алгоритм расчёта заказа |
| `Services/AlertService.php` | Определение уровней тревоги |
| `Models/Supplier.php` | all(), find(), getActive(), create() |
| `Models/Depot.php` | getConsumptionForecast() |
| `Core/Database.php` | PDO wrapper: fetchAll, fetchOne, execute, lastInsertId |
| `public/index.php` | Router — все маршруты API |

### Frontend (`frontend/src/`)
| Файл | Назначение |
|------|-----------|
| `views/Parameters.vue` | Все вкладки параметров (7 tabs) |
| `views/Dashboard.vue` | Дашборд + прогноз |
| `views/Procurement.vue` | Советник по закупкам |
| `views/Forecast.vue` | Детальный прогноз |
| `components/InlineEdit.vue` | Клик → редактирование → сохранение |
| `components/HierarchyManager.vue` | Дерево инфраструктуры |
| `services/api.js` | Все Axios вызовы к API |

### Root
| Файл | Назначение |
|------|-----------|
| `migration_suppliers.sql` | Миграция 11 поставщиков + 497 offers из старой БД |
| `update.html` | Триггер `git pull` на сервере |
| `SYSTEM_KNOWLEDGE_BASE.md` | Подробная техническая документация |

---

## 🔧 API Endpoints

### Parameters
| Method | Path | Action |
|--------|------|--------|
| GET | `/api/parameters/supplier-offers` | Все офферы (497 строк) |
| PUT | `/api/parameters/supplier-offers/:id` | Обновить цену / дни |
| GET | `/api/parameters/fuel-types` | Все виды топлива |
| PUT | `/api/parameters/fuel-types/:id` | Обновить density |
| GET | `/api/parameters/sales-params` | Потребление L/day |
| PUT | `/api/parameters/sales-params/:id` | Обновить L/day |
| GET | `/api/parameters/stock-policies` | Пороговые уровни |
| PUT | `/api/parameters/stock-policies/:id` | Обновить пороги |
| GET | `/api/parameters/system` | Системные параметры |
| PUT | `/api/parameters/system/:key` | Обновить параметр |

### Suppliers
| Method | Path | Action |
|--------|------|--------|
| GET | `/api/suppliers` | Все поставщики |
| POST | `/api/suppliers` | Создать нового |
| GET | `/api/suppliers/active` | Только активные |
| GET | `/api/suppliers/:id` | Один поставщик |

### Forecast & Procurement
| Method | Path | Action |
|--------|------|--------|
| GET | `/api/forecast/station` | Прогноз по станциям |
| GET | `/api/forecast/critical` | Критические танки |
| GET | `/api/procurement/advisor` | Рекомендации заказов |

---

## 📐 Architecture Decisions

### Почему нет русского в коде
Все имена переменных, комментарии, API ключи, алиасы в SQL — только на английском. Данные в БД могут быть на русском (названия станций и т.д.) — это нормально.

### Почему dist/ коммитится в git
Сервер на shared hosting без Node.js. `npm run build` выполняется локально, `frontend/dist/` добавляется через `git add -f` и коммитится. Сервер делает `git pull` — и сразу видит готовые статические файлы.

### Почему liters в DB, tons в UI
- Физические измерения (остатки в танках) — в литрах (реальные датчики дают литры)
- Закупки и цены — в тоннах (отраслевой стандарт)
- Конвертация: `tons = liters × density / 1000`

### Почему supplier_station_offers объединяет старые delivery_times + fuel_prices
В старой БД было 2 таблицы: `delivery_times` (маршрут→дни) и `fuel_prices` (поставщик→цена). Новая структура: одна таблица `supplier_station_offers` = поставщик × станция × вид топлива → цена + дни. Это позволяет в будущем иметь разные цены на разные станции.

### Почему цена в UI — единая для всех станций
Бизнес-правило подтверждено: "Цена у каждого поставщика своя. Независимо от станции." При редактировании цены на карточке — система обновляет ВСЕ строки для данного `supplier_id + fuel_type_id` одним Promise.all.

### InlineEdit компонент
Паттерн: отображает значение → при клике превращается в `<input>` → при blur или Enter → вызывает `@save` → родитель делает API вызов → обновляет локальный реактивный объект без перезагрузки.

---

## 🚧 Known Gaps / TODO

| Задача | Приоритет | Описание |
|--------|-----------|----------|
| Stock Policies: 0 записей | 🔴 Высокий | Таблица пустая — нужно заполнить пороговые уровни |
| Цены поставщиков | 🟡 Средний | Данные из старой БД (могут быть устаревшими) — обновить вручную |
| Добавление fuel type offer | 🟡 Средний | Сейчас можно добавить поставщика, но нельзя через UI добавить ему новый вид топлива / станцию |
| Аламедин delivery days | 🟡 Средний | Скопированы с Бишкека — проверить реальные |
| Import module: ERP sync UI | 🟠 Средний | Import.vue — UI для подключения к erp.kittykat.tech + createErpOrder() |
| PO expiry warnings on Dashboard | 🟡 Низкий | Бейдж на дашборде для истёкших PO |
| Transfers module | 🟠 Средний | Перемещения между станциями |
| Import module | 🟠 Средний | Импорт данных из 1C / Excel |

---

## 🗓 Changelog

### 2026-02-23 (сессия 5)
- ✅ Migration 008: добавлен `order_type` ENUM, `erp_order_id`, `matched_at`; расширен статус ENUM (`matched`, `expired`)
- ✅ Order.php: `baseSelect` + фильтр `order_type` + новые методы: `matchWithErp`, `markExpiredPOs`, `findActivePO`, `createErpOrder`
- ✅ ForecastService: доставки на графике только от `erp_order` (PO не влияют)
- ✅ ProcurementAdvisorService: поле `po_pending` + `active_po` в рекомендациях
- ✅ Orders.vue: два таба — Purchase Orders (created by user) + ERP Deliveries (read-only, from ERP)
- ✅ ProcurementAdvisor.vue: бейдж "PO Issued — Awaiting ERP" + кнопки с router.push

### 2026-02-23 (сессия 4)
- ✅ Orders module: полная реализация (Orders.vue, OrderController, Order.php, migration 007)
- ✅ Маршруты POST/PUT/DELETE/cancel в index.php
- ✅ Print PO: секция печати с @media print CSS (3 подписи, шапка, таблица позиций)
- ✅ Исправлены баги: Database::execute() → Database::query(), lastInsertId() через getConnection()
- ✅ Migration 007: добавлены cancelled_reason + cancelled_at

### 2026-02-18 (сессия 3 — продолжение)
- ✅ Supply Offers: таблица 497 строк → карточки поставщиков
- ✅ Fuel Types: таблица → цветные карточки (5 в ряд)
- ✅ Add Supplier: кнопка + модалка + POST `/api/suppliers`
- ✅ Smart save: цена обновляется сразу для всех станций; дни — для всех видов топлива
- ✅ Запущена миграция из старой БД: 11 поставщиков, 497 офферов

### 2026-02-18 (сессия 2)
- ✅ Исправлен алгоритм расчёта заказа (учёт потребления за время доставки)
- ✅ Исправлен баг переполнения ёмкости (заказ не превышает объём танка)
- ✅ Добавлены предупреждения при нехватке ёмкости
- ✅ Удалён `cost_per_ton` из Fuel Types (цены → в Supply Offers)
- ✅ Полный revert `tons_per_day` → `liters_per_day` во всех файлах

### 2026-02-18 (сессия 1)
- ✅ Полная разработка REV 3.0 с нуля
- ✅ PHP backend: Router, Controllers, Services, Models
- ✅ Vue 3 frontend: Dashboard, Parameters, Procurement, Forecast
- ✅ Все 7 вкладок Parameters с InlineEdit
- ✅ Настроен деплой pipeline

---

## 💡 Developer Notes

### Запуск деплоя
```bash
cd frontend && npm run build
cd ..
git add -f frontend/dist/
git add backend/ frontend/src/
git commit -m "feat: описание"
git push
# Затем открыть: https://fuel.kittykat.tech/rev3/update.html
```

### Структура API ответа
```json
{ "success": true, "data": [...], "count": 42 }
{ "success": false, "error": "message" }
```

### InlineEdit — как использовать
```vue
<InlineEdit
  :value="item.price"
  type="number"
  step="1"
  suffix=" $/ton"
  @save="val => savePrice(item.id, val)"
/>
```

### Добавить новый API endpoint
1. Метод в `Model` или `Service`
2. Метод в `Controller`
3. Route в `backend/public/index.php`
4. Метод в `frontend/src/services/api.js`
