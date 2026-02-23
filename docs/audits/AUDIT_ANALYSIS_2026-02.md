# 🔍 REV 3.0 – Vollständiger Analyse- und Prüfbericht

**Audit-Datum:** 2026-02  
**Projekt:** Fuel Management System REV 3.0  
**Geprüft:** Code, MD-Dokumentation, API-Konsistenz, Schema, Frontend-Komponenten

---

## 1. Fehler (Behebung erforderlich)

### 1.1 Backend – Schema / SQL-Fehler

| Datei | Zeile | Problem | Auswirkung |
|-------|-------|---------|------------|
| **DepotTank.php** | 140, 146 | `getStockHistory()` nutzt `sa.change_type` und `sa.created_at` – im Schema existieren `change_reason` und `changed_at` | Laufzeitfehler, sobald `/api/depots/{id}/history` verwendet wird |
| **TransferService.php** | 67 | `SUM(transfer_amount)` – Spalte existiert nicht, korrekt ist `transfer_amount_liters` | SQL-Fehler bei Transfers-Statistiken |

### 1.2 Frontend – API-Felder

| Datei | Zeile | Problem | Auswirkung |
|-------|-------|---------|------------|
| **TransferActivity.vue** | 168 | Verwendet `t.transfer_amount` – die API liefert `transfer_amount_liters` und `transfer_amount_tons` | Anzeige bleibt leer oder `undefined` |
| **TransferActivity.vue** | 172 | `progress: Math.floor(Math.random() * 40 + 40)` – zufällige Werte statt sinnvoller Berechnung | Nutzer sieht unbrauchbare Zufallswerte |

---

## 2. API-Pfad-Diskrepanzen (Frontend vs. Backend)

### 2.1 Nicht existierende oder falsche Endpoints in api.js

| api.js-Methode | Frontend-Pfad | Backend-Status |
|----------------|---------------|----------------|
| `depotsApi.getHistory(id)` | `GET /api/depots/{id}/history` | Nicht implementiert |
| `fuelTypesApi.getTotalStock(id)` | `GET /api/fuel-types/{id}/total-stock` | Backend nutzt `/api/fuel-types/{id}/stock` |
| `suppliersApi.getPerformance(id)` | `GET /api/suppliers/{id}/performance` | Backend nutzt `/api/suppliers/{id}/stats` |
| `ordersApi.getByStatus(status)` | `GET /api/orders/status/{status}` | Keine entsprechende Route |
| `ordersApi.getBySupplier(id)` | `GET /api/orders/supplier/{id}` | Keine entsprechende Route |
| `transfersApi.getByStatus(status)` | `GET /api/transfers/status/{status}` | Keine entsprechende Route |
| `transfersApi.getByStation(id)` | `GET /api/transfers/station/{id}` | Keine entsprechende Route |

**Hinweis:** Diese Methoden werden im Frontend aktuell nicht aufgerufen (toter Code). Trotzdem sollten sie entweder entfernt oder ans Backend angepasst werden.

### 2.2 Backend: Filter über Query-Parameter

Für Transfers und Orders werden bereits Filter über Query-Parameter unterstützt:

- **Transfers:** `?status=`, `?from_station_id=`, `?to_station_id=`, `?fuel_type_id=`
- **Orders:** Ähnliche Filter können ergänzt werden

---

## 3. Hardcodierte und Mock-Daten in Produktionskomponenten

### 3.1 Dashboard.vue – KPIs

| Zeile | Feld | Aktueller Wert | Sollte kommen von |
|-------|------|----------------|-------------------|
| 104 | `kpi-low-stations` | `0` (fix) | API / Dashboard Summary |
| 118 | `kpi-mandatory-orders` | `2` (fix) | Procurement Summary |
| 126 | `kpi-recommended-orders` | `0` (fix) | Procurement Summary |
| 134 | `kpi-active-transfers` | `0` (fix) | Transfers-API |

### 3.2 Komponenten mit festen Demo- bzw. Mock-Daten

| Komponente | Problem |
|------------|---------|
| **RiskExposure.vue** | Vollständig feste Szenarien (`scenarioData`) und Orte (z. B. „Station Alpha“, „Station Beta“) |
| **InventoryTurnover.vue** | Vollständig feste `fuelData` für Diesel, Petrol 95, Petrol 98, Kerosene |
| **ProcurementAdvisor.vue** | Statische `marketPrices`, feste Preiswarnungen (z. B. „Diesel prices expected to increase by 3%“) |
| **CostAnalysis.vue** | Texte wie „-2.3% vs last month“, „+5.1% vs last month“, „€45K Via bulk ordering“ im Template |
| **WorkingCapital.vue** | Statische Szenarien (Stock Value, Days of Cover) und Opportunities (z. B. Depot Alpha, Station Beta) |

---

## 4. Dokumentations-Abweichungen

### 4.1 API_DOCUMENTATION.md

- Enthält nicht die Endpoints für:
  - Procurement (upcoming-shortages, summary, supplier-recommendations)
  - Parameters (system, fuel-types, sales-params, stock-policies, supplier-offers, depot-tanks)
  - Infrastructure (hierarchy, stations, depots, tanks)
- Dokumentiert `depots/{id}/history` – dieser Endpoint ist im Backend nicht implementiert.

### 4.2 PROJECT_STATUS.md

- Nennt Forecast- und Procurement-Controller – tatsächlich sind die relevanten Controller:
  - `DashboardController`
  - `ProcurementAdvisorController` (oder vergleichbare Logik in Services)

### 4.3 CODE_AUDIT_RESULTS.md

- Gibt an, der DepotTank-Fix sei bereits erledigt – in DepotTank.php ist jedoch weiterhin `change_type` und `created_at` statt `change_reason` und `changed_at` vorhanden.

---

## 5. Weitere technische Befunde

### 5.1 Architektur

| Bereich | Befund |
|---------|--------|
| **Composer Autoload** | Nicht vorhanden – viele manuelle `require_once` in `backend/public/index.php` |
| **api.js Fallback-URL** | Hardcodiert: `https://fuel.kittykat.tech/rev3/backend/public/api` – besser ausschließlich über Umgebungsvariable steuern |

### 5.2 Hardcodierte Werte im Backend

| Datei | Beschreibung |
|-------|--------------|
| **FuelTypeController.php** | Farben für Fuel-Type-Codes (z. B. GAS92 → #3b82f6) fest hinterlegt |
| **UnitConverter.php** | Fallback-Dichten für „gasoline“, „diesel“ usw. |

### 5.3 Deployment

| Thema | Befund |
|-------|--------|
| **.htaccess** | Nicht im Git – wird beim Deploy möglicherweise nicht hochgeladen und ist damit auf dem Server nicht aktiv |

---

## 6. Priorisierte Empfehlungen

### Priorität hoch

| # | Maßnahme |
|---|----------|
| 1 | **TransferService.php** (Zeile 67): `transfer_amount` → `transfer_amount_liters` |
| 2 | **DepotTank.php** (Zeilen 140, 146): `change_type` → `change_reason`, `created_at` → `changed_at` |
| 3 | **TransferActivity.vue** (Zeile 168): `t.transfer_amount` → `t.transfer_amount_tons` oder `t.transfer_amount_liters` |
| 4 | **TransferActivity.vue** (Zeile 172): Progress sinnvoll berechnen statt `Math.random()` |

### Priorität mittel

| # | Maßnahme |
|---|----------|
| 5 | **Dashboard.vue**: KPIs aus API beziehen (Procurement Summary, Transfers-Statistiken) |
| 6 | **RiskExposure.vue**, **InventoryTurnover.vue**: auf echte API-Daten umstellen oder klar als Demo/Mock kennzeichnen |
| 7 | **CostAnalysis.vue**, **ProcurementAdvisor.vue**: statische Texte und Werte durch API-Daten ersetzen oder entfernen |

### Priorität niedrig

| # | Maßnahme |
|---|----------|
| 8 | **api.js**: Nicht genutzte oder falsche Methoden entfernen oder ans Backend anpassen |
| 9 | **API_DOCUMENTATION.md**: Vollständigkeit und Aktualität sicherstellen |
| 10 | **PROJECT_STATUS.md**, **CODE_AUDIT_RESULTS.md**: mit aktuellem Code abgleichen |
| 11 | **.htaccess** ins Repository aufnehmen und im Deploy-Prozess berücksichtigen |

---

## 7. Funktionierende Bereiche

### Backend

- StationController, DepotController, FuelTypeController
- SupplierController, OrderController, TransferController
- DashboardController (Summary, Alerts, Critical Tanks, Forecast)
- ReportController (Daily Stock, Inventory Summary, Station Performance, Low Stock, Capacity Utilization)
- CostAnalysisController, RegionalComparisonController
- ProcurementAdvisorController (Upcoming Shortages, Summary, Supplier Recommendations)
- ParametersController (System, Fuel Types, Sales Params, Stock Policies, Supplier Offers, Depot Tanks)
- Infrastructure (Hierarchy, Stations, Depots, Tanks)
- Trennung FACTS vs. RULES (sales_params vs. stock_policies)
- Single Source of Truth: `depot_tanks.current_stock_liters`
- Einheitliche JSON-Response-Struktur

### Frontend

- Vue 3 SPA mit Router
- Dashboard mit Forecast-Chart, Station Fill Levels, Stock by Fuel Type
- Parameters-Seite mit allen Tabs (Infrastructure, Supply Offers, Sales Params, Stock Policies, Fuel Types, System, Depot Tanks)
- Orders, Transfers, Import, How It Works
- OptimusAI-Assistent
- Integration mit der bestehenden API-Struktur

---

## 8. Schema-Referenz (stock_audit)

```sql
-- Korrekte Spalten in stock_audit (001_create_core_tables.sql):
change_reason ENUM('order', 'transfer', 'adjustment', 'consumption', 'manual')
changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

**DepotTank::getStockHistory()** muss diese Spaltennamen verwenden.

---

## 9. Schema-Referenz (transfers)

```sql
-- Korrekte Spalte in transfers:
transfer_amount_liters DECIMAL(12,2) NOT NULL
```

**TransferService** muss `transfer_amount_liters` statt `transfer_amount` verwenden.

---

## 10. Zusammenfassung

| Kategorie | Anzahl | Beispiele |
|-----------|--------|-----------|
| Kritische Fehler (Schema/SQL) | 2 | DepotTank, TransferService |
| Frontend-API-Feld-Fehler | 1 | TransferActivity: `transfer_amount` |
| Mock/Zufallsdaten | 1 | TransferActivity: `Math.random()` für Progress |
| API-Pfad-Diskrepanzen | 7 | getHistory, getTotalStock, getPerformance, getByStatus, getBySupplier, getByStation |
| Hardcodierte KPIs | 4 | Dashboard: Low Stock, Mandatory Orders, Recommended Orders, Active Transfers |
| Komponenten mit Demo-Daten | 5 | RiskExposure, InventoryTurnover, ProcurementAdvisor, CostAnalysis, WorkingCapital |
| Dokumentations-Abweichungen | 3+ | API_DOCUMENTATION, PROJECT_STATUS, CODE_AUDIT_RESULTS |
| Sonstige | 3 | Composer Autoload, .htaccess nicht in Git, hardcodierte API-URL |

---

*Erstellt am 2026-02 im Rahmen der REV 3.0 Code-Analyse.*
