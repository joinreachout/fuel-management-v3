<?php

namespace App\Controllers;

use App\Services\RegionalComparisonService;
use App\Core\Response;

class RegionalComparisonController
{
    public function getRegionalComparison(): void
    {
        try {
            $data = RegionalComparisonService::getRegionalComparison();
            Response::json($data);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch regional comparison: ' . $e->getMessage()
            ], 500);
        }
    }
}
