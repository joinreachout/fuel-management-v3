#!/bin/bash

# Database Migration Script
# Applies supplier_station_offers table migration

echo "=================================="
echo "Database Migration: Supplier Schema"
echo "=================================="
echo ""

# Database credentials
DB_HOST="d105380.mysql.zonevs.eu"
DB_NAME="d105380_fuelv3"
DB_USER="d105380_fuelv3"

echo "Target Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""

# Check if password is provided
if [ -z "$DB_PASSWORD" ]; then
    echo "Enter MySQL password for user $DB_USER:"
    read -s DB_PASSWORD
    echo ""
fi

# Function to execute SQL file
execute_sql() {
    local SQL_FILE=$1
    local DESCRIPTION=$2

    echo "[$DESCRIPTION]"
    echo "Executing: $SQL_FILE"

    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$SQL_FILE"

    if [ $? -eq 0 ]; then
        echo "✓ Success"
        echo ""
    else
        echo "✗ Failed"
        echo ""
        exit 1
    fi
}

# Step 1: Create table
echo "Step 1/3: Creating supplier_station_offers table..."
execute_sql "database/migrations/create_supplier_station_offers_table.sql" "Create Table"

# Step 2: Insert seed data
echo "Step 2/3: Inserting seed data..."
execute_sql "database/seeds/supplier_station_offers_seed.sql" "Seed Data"

# Step 3: Verify
echo "Step 3/3: Verifying migration..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "
SELECT 'Table created:' as status, COUNT(*) as offer_count
FROM supplier_station_offers;

SELECT
    s.name as supplier,
    st.name as station,
    sso.delivery_days,
    sso.price_diesel_b7
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.is_active = 1
LIMIT 5;
"

echo ""
echo "=================================="
echo "Migration Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Verify data looks correct"
echo "2. Update ProcurementAdvisorService.php"
echo "3. Test API endpoints"
echo ""
