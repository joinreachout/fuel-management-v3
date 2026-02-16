<?php
/**
 * Simple Git Pull Deploy Script
 *
 * Access: https://fuel.kittykat.tech/rev3/deploy.php?key=deploy123
 * This will pull latest changes from GitHub
 */

// Security key (change this!)
$DEPLOY_KEY = 'fuel2026deploy';

// Check security key
if (!isset($_GET['key']) || $_GET['key'] !== $DEPLOY_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

header('Content-Type: text/plain');

echo "🚀 Starting deployment...\n\n";

// Change to project directory
$projectDir = __DIR__;
chdir($projectDir);

echo "📁 Project directory: $projectDir\n";

// Execute git pull
echo "\n📥 Pulling latest code from GitHub...\n";
$output = shell_exec('git pull origin main 2>&1');
echo $output;

// Check if frontend dist exists
if (file_exists('frontend/dist/index.html')) {
    echo "\n✅ Frontend found!\n";
    echo "📊 Frontend files:\n";
    $files = scandir('frontend/dist');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "\n⚠️ Warning: frontend/dist/index.html not found\n";
}

echo "\n✅ Deployment complete!\n";
echo "\n🌐 Frontend URL: https://fuel.kittykat.tech/rev3/frontend/dist/\n";
echo "🌐 Backend API: https://fuel.kittykat.tech/rev3/backend/public/api/\n";

// Clear opcode cache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n♻️ Opcache cleared\n";
}

echo "\n⏰ Deployed at: " . date('Y-m-d H:i:s') . "\n";
