<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Models\Station;
use App\Services\StationTanksService;

/**
 * Station Controller
 * Handles HTTP requests for station resources
 */
class StationController
{
    /**
     * GET /api/stations
     * Get all stations
     */
    public function index(): void
    {
        try {
            $stations = Station::all();

            Response::json([
                'success' => true,
                'data' => $stations,
                'count' => count($stations)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch stations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/stations/{id}
     * Get single station by ID
     */
    public function show(int $id): void
    {
        try {
            $station = Station::find($id);

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
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch station: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/stations/{id}/depots
     * Get all depots for a station
     */
    public function depots(int $id): void
    {
        try {
            $depots = Station::getDepots($id);

            Response::json([
                'success' => true,
                'data' => $depots,
                'count' => count($depots),
                'station_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch depots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/stations/{id}/tanks
     * Get all tanks for a station
     */
    public function tanks(int $id): void
    {
        try {
            $result = StationTanksService::getStationTanks($id);

            if (!$result['success']) {
                Response::json([
                    'success' => false,
                    'error' => $result['message']
                ], 500);
                return;
            }

            Response::json($result);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch tanks: ' . $e->getMessage()
            ], 500);
        }
    }
}
