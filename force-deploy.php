<?php
/**
 * Force Deploy Script - Hard reset and pull
 * Access: https://fuel.kittykat.tech/rev3/force-deploy.php?key=fuel2026deploy
 */

$DEPLOY_KEY = 'fuel2026deploy';

if (!isset($_GET['key']) || $_GET['key'] !== $DEPLOY_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

header('Content-Type: text/plain');

echo "🔥 FORCE DEPLOYMENT - Hard Reset\n\n";

$projectDir = __DIR__;
chdir($projectDir);

echo "📁 Project directory: $projectDir\n\n";

// Hard reset to remote
echo "⚠️  Resetting to remote state (discarding ALL local changes)...\n";
$resetOutput = shell_exec('git fetch origin && git reset --hard origin/main 2>&1');
echo $resetOutput . "\n";

// Pull latest
echo "\n📥 Pulling latest code...\n";
$pullOutput = shell_exec('git pull origin main 2>&1');
echo $pullOutput . "\n";

// Check frontend
if (file_exists('frontend/dist/index.html')) {
    echo "\n✅ Frontend found!\n";
    echo "📊 Frontend dist contents:\n";
    $files = scandir('frontend/dist');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "\n❌ Frontend dist not found!\n";
}

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n♻️ Opcache cleared\n";
}

echo "\n✅ FORCE DEPLOYMENT COMPLETE!\n";
echo "🌐 Frontend URL: https://fuel.kittykat.tech/rev3/frontend/dist/\n";
echo "⏰ Deployed at: " . date('Y-m-d H:i:s') . "\n";
