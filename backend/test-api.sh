#!/bin/bash
# Quick API test script
# Tests the new stations endpoint

echo "Testing API Endpoints..."
echo ""

# Base URL (adjust if needed)
BASE_URL="http://localhost/backend/public/api"

echo "1. GET /api/stations - Get all stations"
curl -s "$BASE_URL/stations" | json_pp
echo ""
echo "---"
echo ""

echo "2. GET /api/stations/1 - Get single station"
curl -s "$BASE_URL/stations/1" | json_pp
echo ""
echo "---"
echo ""

echo "3. GET /api/stations/1/stock - Get station stock levels"
curl -s "$BASE_URL/stations/1/stock" | json_pp
echo ""
echo "---"
echo ""

echo "4. GET /api/stations/1/depots - Get station depots"
curl -s "$BASE_URL/stations/1/depots" | json_pp
echo ""
