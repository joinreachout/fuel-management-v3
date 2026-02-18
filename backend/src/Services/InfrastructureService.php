<?php

namespace App\Services;

use App\Core\Database;

/**
 * Infrastructure Service
 * Provides the full Station → Depot → Tank hierarchy with aggregated stats.
 * Also handles CRUD for stations, depots, and tanks.
 */
class InfrastructureService
{
    // ─────────────────────────────────────────────
    // READ — full hierarchy
    // ─────────────────────────────────────────────

    /**
     * Returns full tree: regions → stations → depots → tanks
     * Each level has aggregated stats (capacity, stock, fill %)
     */
    public static function getHierarchy(): array
    {
        // Load all stations with region info
        $stations = Database::fetchAll(
            "SELECT s.id, s.name, s.code, s.is_active,
                    r.id as region_id, r.name as region_name
             FROM stations s
             LEFT JOIN regions r ON s.region_id = r.id
             ORDER BY r.name, s.name"
        );

        // Load all depots
        $depots = Database::fetchAll(
            "SELECT id, station_id, name, code, category, is_active
             FROM depots
             ORDER BY name"
        );

        // Load all tanks with fuel type info + aggregated per depot
        $tanks = Database::fetchAll(
            "SELECT
                dt.id,
                dt.depot_id,
                dt.fuel_type_id,
                ft.name  AS fuel_type_name,
                ft.code  AS fuel_type_code,
                ft.density,
                dt.current_stock_liters,
                dt.capacity_liters,
                ROUND(dt.current_stock_liters / dt.capacity_liters * 100, 1) AS fill_pct,
                dt.updated_at
             FROM depot_tanks dt
             INNER JOIN fuel_types ft ON dt.fuel_type_id = ft.id
             ORDER BY dt.depot_id, ft.name"
        );

        // Index tanks by depot_id
        $tanksByDepot = [];
        foreach ($tanks as $tank) {
            $tanksByDepot[$tank['depot_id']][] = $tank;
        }

        // Index depots by station_id, attach tanks + depot stats
        $depotsByStation = [];
        foreach ($depots as $depot) {
            $depotTanks = $tanksByDepot[$depot['id']] ?? [];

            $totalCapacity = array_sum(array_column($depotTanks, 'capacity_liters'));
            $totalStock    = array_sum(array_column($depotTanks, 'current_stock_liters'));
            $fillPct = $totalCapacity > 0
                ? round($totalStock / $totalCapacity * 100, 1)
                : 0;

            $depotsByStation[$depot['station_id']][] = array_merge($depot, [
                'tanks'          => $depotTanks,
                'tanks_count'    => count($depotTanks),
                'total_capacity_liters' => round($totalCapacity),
                'total_stock_liters'    => round($totalStock),
                'fill_pct'       => $fillPct,
            ]);
        }

        // Build region → station tree
        $regions = [];
        foreach ($stations as $station) {
            $regionId   = $station['region_id']   ?? 0;
            $regionName = $station['region_name'] ?? 'Без региона';

            $stationDepots = $depotsByStation[$station['id']] ?? [];

            // Station-level aggregation
            $stationCapacity = array_sum(array_column($stationDepots, 'total_capacity_liters'));
            $stationStock    = array_sum(array_column($stationDepots, 'total_stock_liters'));
            $stationFill = $stationCapacity > 0
                ? round($stationStock / $stationCapacity * 100, 1)
                : 0;

            $stationData = array_merge($station, [
                'depots'                => $stationDepots,
                'depots_count'          => count($stationDepots),
                'tanks_count'           => array_sum(array_column($stationDepots, 'tanks_count')),
                'total_capacity_liters' => round($stationCapacity),
                'total_stock_liters'    => round($stationStock),
                'fill_pct'              => $stationFill,
            ]);

            if (!isset($regions[$regionId])) {
                $regions[$regionId] = [
                    'id'       => $regionId,
                    'name'     => $regionName,
                    'stations' => [],
                ];
            }
            $regions[$regionId]['stations'][] = $stationData;
        }

        // Region-level aggregation
        foreach ($regions as &$region) {
            $region['stations_count'] = count($region['stations']);
            $region['depots_count']   = array_sum(array_column($region['stations'], 'depots_count'));
            $region['tanks_count']    = array_sum(array_column($region['stations'], 'tanks_count'));
            $region['total_capacity_liters'] = array_sum(array_column($region['stations'], 'total_capacity_liters'));
            $region['total_stock_liters']    = array_sum(array_column($region['stations'], 'total_stock_liters'));
            $cap = $region['total_capacity_liters'];
            $region['fill_pct'] = $cap > 0
                ? round($region['total_stock_liters'] / $cap * 100, 1)
                : 0;
        }

        return array_values($regions);
    }

    // ─────────────────────────────────────────────
    // STATIONS — CRUD
    // ─────────────────────────────────────────────

    public static function updateStation(int $id, string $name, string $code, int $isActive): bool
    {
        $affected = Database::execute(
            "UPDATE stations SET name = ?, code = ?, is_active = ? WHERE id = ?",
            [$name, $code, $isActive, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // DEPOTS — CRUD
    // ─────────────────────────────────────────────

    public static function updateDepot(int $id, string $name, string $code, ?string $category, int $isActive): bool
    {
        $affected = Database::execute(
            "UPDATE depots SET name = ?, code = ?, category = ?, is_active = ? WHERE id = ?",
            [$name, $code, $category, $isActive, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // TANKS — CRUD
    // ─────────────────────────────────────────────

    /**
     * Update tank capacity and current stock
     * current_stock_liters is the live operational value — updated here for manual corrections
     */
    public static function updateTank(
        int $id,
        float $capacityLiters,
        float $currentStockLiters
    ): bool {
        if ($capacityLiters <= 0) {
            throw new \InvalidArgumentException("Capacity must be positive");
        }
        if ($currentStockLiters < 0) {
            throw new \InvalidArgumentException("Stock cannot be negative");
        }
        if ($currentStockLiters > $capacityLiters) {
            throw new \InvalidArgumentException("Stock cannot exceed capacity");
        }

        $affected = Database::execute(
            "UPDATE depot_tanks
             SET capacity_liters = ?, current_stock_liters = ?, updated_at = NOW()
             WHERE id = ?",
            [$capacityLiters, $currentStockLiters, $id]
        );
        return $affected > 0;
    }

    /**
     * Add a new tank to a depot
     */
    public static function addTank(
        int $depotId,
        int $fuelTypeId,
        float $capacityLiters,
        float $currentStockLiters = 0
    ): int {
        if ($capacityLiters <= 0) {
            throw new \InvalidArgumentException("Capacity must be positive");
        }

        // Verify depot exists
        $depot = Database::fetchAll("SELECT id FROM depots WHERE id = ?", [$depotId]);
        if (empty($depot)) {
            throw new \InvalidArgumentException("Depot not found: $depotId");
        }

        // Verify fuel type exists
        $ft = Database::fetchAll("SELECT id FROM fuel_types WHERE id = ?", [$fuelTypeId]);
        if (empty($ft)) {
            throw new \InvalidArgumentException("Fuel type not found: $fuelTypeId");
        }

        Database::execute(
            "INSERT INTO depot_tanks (depot_id, fuel_type_id, capacity_liters, current_stock_liters, updated_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$depotId, $fuelTypeId, $capacityLiters, $currentStockLiters]
        );

        return (int)Database::lastInsertId();
    }
}
