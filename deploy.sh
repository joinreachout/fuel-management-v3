#!/bin/bash
# REV 3.0 Deployment Script
# Automatically updates backend and rebuilds frontend

set -e  # Exit on any error

echo "=========================================="
echo "🚀 REV 3.0 Deployment Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get project directory (where this script is located)
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "📂 Project directory: $PROJECT_DIR"
echo ""

# Step 1: Git Pull
echo "📥 Step 1/4: Pulling latest changes from GitHub..."
cd "$PROJECT_DIR"
git pull origin main
echo -e "${GREEN}✓${NC} Git pull completed"
echo ""

# Step 2: Update Backend
echo "🔧 Step 2/4: Updating backend files..."
echo "   Backend files are already in place from git pull"
echo -e "${GREEN}✓${NC} Backend updated"
echo ""

# Step 3: Rebuild Frontend
echo "🏗️  Step 3/4: Rebuilding frontend..."
cd "$PROJECT_DIR/frontend"

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "   📦 Installing dependencies (first time)..."
    npm install
fi

# Clean old build files
echo "   🧹 Cleaning old build files..."
rm -rf dist

# Build frontend
echo "   🔨 Building frontend with Vite..."
npm run build

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Frontend build completed successfully"
else
    echo -e "${RED}✗${NC} Frontend build failed!"
    exit 1
fi
echo ""

# Step 4: Verify build
echo "📋 Step 4/4: Verifying build..."
DIST_DIR="$PROJECT_DIR/frontend/dist"

if [ -f "$DIST_DIR/index.html" ]; then
    echo -e "${GREEN}✓${NC} index.html exists"
else
    echo -e "${RED}✗${NC} index.html missing!"
    exit 1
fi

# Count JS files in assets
JS_COUNT=$(find "$DIST_DIR/assets" -name "*.js" -type f | wc -l)
CSS_COUNT=$(find "$DIST_DIR/assets" -name "*.css" -type f | wc -l)

echo -e "${GREEN}✓${NC} Found $JS_COUNT JS file(s) and $CSS_COUNT CSS file(s)"
echo ""

# Summary
echo "=========================================="
echo "✅ Deployment completed successfully!"
echo "=========================================="
echo ""
echo "📊 Summary:"
echo "   • Backend: Updated from git"
echo "   • Frontend: Rebuilt and ready"
echo "   • Dist folder: $DIST_DIR"
echo ""
echo "🌐 Next steps:"
echo "   1. Frontend is ready at: $DIST_DIR"
echo "   2. Access dashboard at: https://fuel.kittykat.tech/rev3/frontend/dist/"
echo "   3. Hard refresh browser (Ctrl+Shift+R) to see changes"
echo ""
echo "📁 Build files:"
ls -lh "$DIST_DIR" | grep -E "index.html|assets"
echo ""

exit 0
