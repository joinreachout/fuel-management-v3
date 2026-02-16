<?php
/**
 * Auto-deployment script with FORCE RESET
 * Access: https://fuel.kittykat.tech/rev3/deploy.php?key=fuel2026deploy
 */

$DEPLOY_KEY = 'fuel2026deploy';

if (!isset($_GET['key']) || $_GET['key'] !== $DEPLOY_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

header('Content-Type: text/plain');

echo "🚀 Starting deployment (FORCE MODE)...\n\n";

$projectDir = __DIR__;
chdir($projectDir);

echo "📁 Project directory: $projectDir\n\n";

// FORCE RESET - discard all local changes
echo "⚠️  FORCE RESET: Discarding all local changes...\n";
$fetchOutput = shell_exec('git fetch origin 2>&1');
echo $fetchOutput . "\n";

$resetOutput = shell_exec('git reset --hard origin/main 2>&1');
echo $resetOutput . "\n";

// Pull latest code
echo "\n📥 Pulling latest code from GitHub...\n";
$output = shell_exec('git pull origin main 2>&1');
echo $output;

// Check if frontend dist exists
if (file_exists('frontend/dist/index.html')) {
    echo "\n✅ Frontend found!\n";
    echo "📊 Frontend dist files:\n";
    $files = scandir('frontend/dist');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }

    // Show first few lines of index.html to verify
    echo "\n📄 index.html content preview:\n";
    $indexContent = file_get_contents('frontend/dist/index.html');
    $lines = explode("\n", $indexContent);
    echo implode("\n", array_slice($lines, 0, 10)) . "\n...\n";
} else {
    echo "\n❌ Frontend dist not found!\n";
}

echo "\n✅ Deployment complete!\n";
echo "\n🌐 Frontend URL: https://fuel.kittykat.tech/rev3/frontend/dist/\n";
echo "🌐 Backend API: https://fuel.kittykat.tech/rev3/backend/public/api/\n";

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n♻️ Opcache cleared\n";
}

echo "\n⏰ Deployed at: " . date('Y-m-d H:i:s') . "\n";
