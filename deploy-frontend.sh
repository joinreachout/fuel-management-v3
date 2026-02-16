#!/bin/bash

# Frontend Deployment Script
# Run this on the server via SSH or web terminal

echo "🚀 Starting Frontend Deployment..."

# Navigate to project directory
cd ~/fuel.kittykat.tech/rev3 || exit 1

echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Check if frontend/dist exists
if [ -d "frontend/dist" ]; then
    echo "✅ Frontend dist folder found"
    echo "📁 Contents:"
    ls -lh frontend/dist/
    echo ""
    echo "✅ Frontend deployed successfully!"
    echo "🌐 URL: https://fuel.kittykat.tech/rev3/frontend/dist/"
else
    echo "❌ Error: frontend/dist folder not found"
    echo "Try: cd frontend && npm install && npm run build"
    exit 1
fi

echo ""
echo "✅ Deployment complete!"
