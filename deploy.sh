#!/bin/bash

# Deploy REV 3.0 to production server
# Usage: ./deploy.sh

echo "🚀 Deploying REV 3.0 to kittykat.tech..."

# Step 1: Push to GitHub
echo "📤 Pushing to GitHub..."
git push origin main

# Step 2: SSH to server and pull changes
echo "📥 Pulling changes on server..."
ssh kittykat << 'EOF'
cd /data01/virt105026/domeenid/www.kittykat.tech/fuel/rev3
git pull origin main
echo "✅ Code updated on server!"
EOF

echo "🎉 Deployment complete!"
