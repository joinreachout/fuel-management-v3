# 🎯 REV 3.0 Development Principles

## Core Philosophy
**Build a professional, clean, and maintainable fuel management system**

---

## 1. ❌ NO HARDCODE

**Never hardcode values in the code**

✅ **DO:**
```php
// From .env or config
$threshold = Config::get('stock.critical_level');
$density = $this->fuelType->density;
```

❌ **DON'T:**
```php
// Hardcoded values
$threshold = 5000;
$density = 0.84;
```

**Implementation:**
- All configuration in `.env` file
- Constants in `/src/Config/` files
- Database-driven settings (future: admin panel)
- No magic numbers in code

---

## 2. ❌ NO DUPLICATES

**DRY Principle - Don't Repeat Yourself**

✅ **DO:**
```php
// Single source of truth
class StockService {
    public function getCurrentStock(int $tankId): float {
        return $this->tankModel->getStock($tankId);
    }
}
```

❌ **DON'T:**
```php
// Same logic in multiple places
// Controller: $stock = $db->query("SELECT current_stock...");
// Service: $stock = $db->query("SELECT current_stock...");
// Model: $stock = $db->query("SELECT current_stock...");
```

**Rules:**
- **Single Source of Truth**: `depot_tanks.current_stock_liters`
- One method = one responsibility
- Reusable components
- No copy-paste code
- Extract common logic into utilities/services

---

## 3. 🌍 ENGLISH ONLY

**All code, comments, and documentation in English**

✅ **DO:**
```php
/**
 * Calculate days until stock runs out
 *
 * @param int $depotId Depot identifier
 * @param int $fuelTypeId Fuel type identifier
 * @return int Days until stockout
 */
public function getDaysUntilStockout(int $depotId, int $fuelTypeId): int
{
    $currentStock = $this->getCurrentStock($depotId, $fuelTypeId);
    $dailyConsumption = $this->getDailyConsumption($depotId, $fuelTypeId);

    return (int) ceil($currentStock / $dailyConsumption);
}
```

❌ **DON'T:**
```php
// Получить дни до истощения запасов
public function getDniDoIstosheniya($depotId, $tipTopliva)
{
    $tekushieZapasi = $this->getTekushieZapasi($depotId, $tipTopliva);
    // ...
}
```

**Rules:**
- Class names: English
- Method names: English
- Variable names: English
- Comments: English
- Documentation: English
- Only database content (station names, etc.) can be in Russian

---

## 4. 📦 MODULAR ARCHITECTURE

**MVC + Services Pattern**

```
/src
  /Core           - Database, Response, Router
  /Models         - Data access layer (CRUD operations)
  /Services       - Business logic layer
  /Controllers    - HTTP request handlers
  /Utils          - Reusable utilities
  /Config         - Configuration files
```

**Separation of Concerns:**

| Layer | Responsibility | Example |
|-------|---------------|---------|
| **Models** | Database operations | `Station::find($id)` |
| **Services** | Business logic | `ForecastService::predictConsumption()` |
| **Controllers** | HTTP handling | `StationController::index()` |
| **Utils** | Pure functions | `UnitConverter::litersToTons()` |

**Example Flow:**
```
HTTP Request
    ↓
Controller (validate input, call service)
    ↓
Service (business logic, orchestrate models)
    ↓
Model (database operations)
    ↓
Database
```

---

## 5. 🔄 GITHUB WORKFLOW

**All code versioned and tracked**

✅ **Good commit messages:**
```bash
git commit -m "Add ForecastService with consumption prediction"
git commit -m "Fix stock calculation bug in DepotTank model"
git commit -m "Refactor UnitConverter to support new fuel types"
```

❌ **Bad commit messages:**
```bash
git commit -m "fix"
git commit -m "update"
git commit -m "changes"
```

**Workflow:**
1. Make changes locally
2. Test changes
3. Commit with meaningful message
4. Push to GitHub
5. Tag releases: `v3.0.0`, `v3.1.0`, etc.

---

## 6. 🧪 TESTING

**Test critical functionality**

```php
// tests/UnitConverterTest.php
class UnitConverterTest extends TestCase
{
    public function testLitersToTons(): void
    {
        $tons = UnitConverter::litersToTons(1000, 'АИ-92');
        $this->assertEquals(0.75, $tons);
    }
}
```

**Testing strategy:**
- Unit tests for Utils (UnitConverter, Validator)
- Integration tests for Services (ForecastService)
- PHPUnit framework
- Run tests before commits

---

## 7. 📚 CLEAN CODE

**PSR-12 coding standards**

✅ **DO:**
```php
class StationService
{
    private StationModel $stationModel;

    public function __construct(StationModel $stationModel)
    {
        $this->stationModel = $stationModel;
    }

    public function getActiveStations(): array
    {
        return $this->stationModel->findWhere(['is_active' => true]);
    }

    public function getStationById(int $id): ?Station
    {
        return $this->stationModel->find($id);
    }
}
```

❌ **DON'T:**
```php
class station_service {
    function get($id) {
        $db = new PDO(...);
        $result = $db->query("SELECT * FROM stations WHERE id = $id");
        return $result->fetch();
    }
}
```

**Rules:**
- Meaningful names (not `$data`, but `$stations`)
- Type hints everywhere: `function getStation(int $id): ?Station`
- Small functions (one function = one task)
- No god classes (max 200-300 lines per class)
- Proper indentation (4 spaces)

---

## 8. 🔐 SECURITY

**Security first**

✅ **DO:**
```php
// Prepared statements
$stmt = $db->prepare("SELECT * FROM stations WHERE id = :id");
$stmt->execute(['id' => $id]);

// Input validation
public function createOrder(array $data): Order
{
    Validator::required($data, ['depot_id', 'fuel_type_id', 'quantity']);
    Validator::numeric($data, ['quantity']);

    return $this->orderModel->create($data);
}
```

❌ **DON'T:**
```php
// SQL injection vulnerability
$query = "SELECT * FROM stations WHERE id = $id";

// No validation
public function createOrder($data) {
    return $this->orderModel->create($data);
}
```

**Security checklist:**
- ✅ Prepared statements (PDO)
- ✅ Input validation
- ✅ Output escaping
- ✅ Password hashing (bcrypt)
- ✅ HTTPS only
- ✅ Rate limiting (future)

---

## 9. 📊 PERFORMANCE

**Optimize database queries**

✅ **DO:**
```php
// Single query with JOIN
public function getStationsWithDepots(): array
{
    return $this->db->query("
        SELECT s.*, d.id as depot_id, d.name as depot_name
        FROM stations s
        LEFT JOIN depots d ON d.station_id = s.id
    ")->fetchAll();
}
```

❌ **DON'T:**
```php
// N+1 query problem
public function getStationsWithDepots(): array
{
    $stations = $this->db->query("SELECT * FROM stations")->fetchAll();

    foreach ($stations as &$station) {
        $station['depots'] = $this->db->query(
            "SELECT * FROM depots WHERE station_id = " . $station['id']
        )->fetchAll(); // Query in loop!
    }

    return $stations;
}
```

**Performance rules:**
- Avoid N+1 queries (use JOINs)
- Use indexes on foreign keys
- Lazy loading for heavy data
- Cache expensive calculations
- Limit result sets (pagination)

---

## 10. 🎨 PROFESSIONAL API

**RESTful API with proper responses**

✅ **DO:**
```php
// GET /api/stations
public function index(): void
{
    $stations = $this->stationService->getAll();

    Response::json([
        'success' => true,
        'data' => $stations,
        'count' => count($stations)
    ], 200);
}

// GET /api/stations/999 (not found)
public function show(int $id): void
{
    $station = $this->stationService->getById($id);

    if (!$station) {
        Response::json([
            'success' => false,
            'error' => 'Station not found'
        ], 404);
        return;
    }

    Response::json([
        'success' => true,
        'data' => $station
    ], 200);
}
```

**API Standards:**
- RESTful endpoints (`GET /api/stations`, `POST /api/orders`)
- JSON responses
- Proper HTTP status codes:
  - `200` - Success
  - `201` - Created
  - `400` - Bad Request
  - `404` - Not Found
  - `500` - Server Error
- Consistent response format
- Error messages in English
- API documentation (Swagger/OpenAPI later)

---

## 11. 🎯 SINGLE SOURCE OF TRUTH

**Critical principle for data integrity**

```
depot_tanks.current_stock_liters
           ↓
    SOURCE OF TRUTH
           ↓
All calculations derive from this
```

**Never store calculated values:**
- ❌ Don't store tons (calculate from liters using density)
- ❌ Don't store total stock per depot (calculate by summing tanks)
- ❌ Don't store days until stockout (calculate on demand)

**Always calculate on-the-fly:**
```php
// ✅ Calculate when needed
public function getTotalStockInTons(int $depotId): float
{
    $tanks = $this->tankModel->getByDepot($depotId);
    $totalLiters = array_sum(array_column($tanks, 'current_stock_liters'));

    return UnitConverter::litersToTons($totalLiters, $fuelTypeId);
}
```

---

## Summary Checklist

Before committing code, ask:

- [ ] No hardcoded values?
- [ ] No duplicate code?
- [ ] All English?
- [ ] Proper layer (Model/Service/Controller)?
- [ ] Tests written?
- [ ] Clean, readable code?
- [ ] Type hints added?
- [ ] Security checks (prepared statements, validation)?
- [ ] No N+1 queries?
- [ ] Meaningful commit message?

---

## 🚀 Goal

**Create a professional fuel management system that:**
- Is easy to maintain
- Is easy to extend
- Is secure and performant
- Has clean, readable code
- Can scale with business needs
- Makes developers happy to work with

---

**Last updated:** Feb 16, 2025
**Version:** 3.0
