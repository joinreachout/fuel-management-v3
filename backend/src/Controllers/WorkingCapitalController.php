<?php

namespace App\Controllers;

use App\Services\WorkingCapitalService;
use App\Core\Response;

class WorkingCapitalController
{
    public function getSummary(): void
    {
        try {
            $data = WorkingCapitalService::getSummary();
            Response::json($data);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch working capital data: ' . $e->getMessage()
            ], 500);
        }
    }
}
