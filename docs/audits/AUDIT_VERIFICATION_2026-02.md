# 🔍 REV 3.0 – Prüfbericht nach AUDIT_RESPONSE_2026-02.md

**Prüfdatum:** 2026-02  
**Basis:** AUDIT_RESPONSE_2026-02.md (Behobene Punkte aus AUDIT_ANALYSIS_2026-02.md)  
**Ziel:** Verifizierung der behaupteten Fixes und Identifikation offener Punkte

---

## 1. Verifizierte Fixes (✅ im Code bestätigt)

### 1.1 Backend – Schema / SQL

| Fix | Datei | Prüfung | Status |
|-----|-------|---------|--------|
| `change_type` → `change_reason`, `created_at` → `changed_at` | DepotTank.php (getStockHistory) | Zeilen 140, 146, 150: `sa.change_reason`, `sa.changed_at`, `ORDER BY sa.changed_at` | ✅ Korrekt |
| `SUM(transfer_amount)` → `SUM(transfer_amount_liters)` | TransferService.php | Zeile 67: `COALESCE(SUM(transfer_amount_liters), 0)` | ✅ Korrekt |

### 1.2 Frontend – API-Felder

| Fix | Datei | Prüfung | Status |
|-----|-------|---------|--------|
| `t.transfer_amount` → `t.transfer_amount_liters` | TransferActivity.vue | Zeile 168: `formatAmount(t.transfer_amount_liters)` | ✅ Korrekt |
| `Math.random()` → feste Werte | TransferActivity.vue | Zeile 171: `progress: t.status === 'in_progress' ? 50 : 0` | ✅ Korrekt |

### 1.3 Dashboard KPIs (aus API)

| KPI | Quelle | Prüfung | Status |
|-----|--------|---------|--------|
| `kpiLowStations` | criticalTanks | computed: `new Set(criticalTanks.value.map(t => t.station_name)).size` | ✅ Aus API |
| `kpiMandatoryOrders` | procurementApi.getSummary() | `ps.mandatory_orders ?? ps.critical_shortages ?? 0` | ✅ Aus API |
| `kpiRecommendedOrders` | procurementApi.getSummary() | `ps.recommended_orders ?? ps.upcoming_shortages ?? 0` | ✅ Aus API |
| `kpiActiveTransfers` | transfersApi.getAll() | `stats.in_progress_transfers + stats.pending_transfers` | ✅ Aus API |

### 1.4 NULLIF-Schutz vor Division durch Null

| Datei | Verwendung |
|-------|------------|
| AlertService.php | `NULLIF(dt.capacity_liters, 0)` |
| ReportService.php | `NULLIF(SUM(dt.capacity_liters), 0)` an mehreren Stellen |
| RegionalComparisonService.php | `NULLIF(SUM(dt.capacity_liters), 0)` |
| StationTanksService.php | `NULLIF(dt.capacity_liters, 0)` |
| FuelStockService.php | `NULLIF(dt.capacity_liters, 0)` |

---

## 2. Offene Punkte aus AUDIT_RESPONSE (Sektion 6)

### 2.1 TransferService – NULL in Stats (noch offen)

**Problem:** Bei leerer `transfers`-Tabelle liefern die Aggregate NULL statt 0:

```json
{
  "pending_transfers": null,
  "in_progress_transfers": null,
  "completed_transfers": null
}
```

**Aktueller Code (TransferService.php, Zeilen 61–65):**
```php
SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transfers,
SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_transfers,
...
```

**Empfehlung:** Mit COALESCE absichern:
```php
COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_transfers,
COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_transfers,
COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_transfers,
COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_transfers,
```

**Frontend:** Verwendet bereits `parseInt(...) || 0`, funktioniert also. Der Fix wäre für konsistentes API-Design sinnvoll.

---

### 2.2 TopSuppliers – `supplier_id` vs. `id` (Bug vorhanden)

**Problem:** TopSuppliers.vue mappt `id: s.supplier_id`, das API liefert aber nur `id`.

**API (SupplierController::top()):**
```php
'id' => (int)$row['id'],
'name' => $row['name'],
'location' => $row['location'] ?? '',
...
```

**Frontend (TopSuppliers.vue, Zeile 196):**
```javascript
id: s.supplier_id,
```

**Folge:** `id` wird `undefined`, da `supplier_id` nicht in der Response vorkommt. Dadurch funktionieren `:key="supplier.id"` und `toggleDetails(supplier.id)` nicht zuverlässig.

**Fix:** In TopSuppliers.vue:
```javascript
id: s.id ?? s.supplier_id,
```

bzw. nur
```javascript
id: s.id,
```

---

### 2.3 Dashboard KPI „Low Stock Stations“ – Definition

**Aktuell:** `criticalTanks` liefert Tanks unter dem Schwellenwert. `kpiLowStations` zählt eindeutige Stationen daraus.

**Offen:** Welcher Schwellenwert exakt verwendet wird (fix vs. `stock_policies.min_stock_percent`) und ob das fachlich zur Kennzahl „Low Stock Stations“ passt.

---

## 3. Verbundene Komponenten (aus AUDIT_RESPONSE Sektion 3)

| Komponente | API | Status |
|------------|-----|--------|
| OrdersCalendar.vue | `ordersApi.getAll()` | ✅ Verbunden |
| TopSuppliers.vue | `suppliersApi.getTop()` | ✅ Verbunden (Bug: siehe 2.2) |
| FuelTypeDistribution.vue | `fuelTypesApi.getDistribution()` | ✅ Verbunden |
| WorkingCapital.vue | `workingCapitalApi.get()` | ✅ Verbunden |

---

## 4. Bewusst nicht behobene Punkte (laut AUDIT_RESPONSE)

| Thema | Grund |
|-------|-------|
| API-Pfad-Diskrepanzen in api.js | Toter Code, wird nicht verwendet |
| Mock-Daten (RiskExposure, InventoryTurnover, etc.) | Komplexe Logik oder externe Daten, bewusst beibehalten |
| Composer Autoload | Architekturentscheidung |
| api.js Fallback-URL | Übliches Muster mit Env-Fallback |
| .htaccess nicht in Git | Muss auf dem Server geprüft werden |

---

## 5. Aktueller Stand – Zusammenfassung

| Kategorie | Fix behauptet | Verifiziert | Noch offen |
|-----------|---------------|-------------|------------|
| DepotTank Schema | Ja | ✅ Ja | — |
| TransferService transfer_amount | Ja | ✅ Ja | — |
| TransferActivity amount/progress | Ja | ✅ Ja | — |
| Dashboard KPIs | Ja | ✅ Ja | — |
| NULLIF Division | Ja | ✅ Ja | — |
| TransferService NULL in stats | Nein (Empfehlung) | — | ⚠️ Offen |
| TopSuppliers supplier_id vs id | Nein (Empfehlung) | — | ❌ Bug |
| Low Stock Definition | — | — | 📝 Zur Klärung |

---

## 6. Empfohlene nächste Schritte

1. **TopSuppliers.vue:** `id: s.supplier_id` → `id: s.id` (behebt expand/collapse).
2. **TransferService.php:** COALESCE für alle Stats-Aggregate ergänzen.
3. **Fachlich:** Definition von „Low Stock Stations“ klären und ggf. dokumentieren.

---

*Prüfung durchgeführt am 2026-02 auf Basis von AUDIT_RESPONSE_2026-02.md.*
