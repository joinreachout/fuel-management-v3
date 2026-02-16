<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Models\Station;

/**
 * Station Controller
 * Handles HTTP requests for station resources
 */
class StationController
{
    private Station $stationModel;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->stationModel = new Station($pdo);
    }

    /**
     * GET /api/stations
     * Get all stations
     */
    public function index(): void
    {
        try {
            $stations = $this->stationModel->getAll();

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
            $station = $this->stationModel->find($id);

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
            $depots = $this->stationModel->getDepots($id);

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
}
