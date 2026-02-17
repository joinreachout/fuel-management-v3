<?php

namespace App\Controllers;

use App\Services\CostAnalysisService;
use App\Core\Response;

class CostAnalysisController
{
    public function getCostAnalysis(): void
    {
        try {
            $data = CostAnalysisService::getCostAnalysis();
            Response::json($data);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch cost analysis: ' . $e->getMessage()
            ], 500);
        }
    }
}
