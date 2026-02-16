<?php
/**
 * Reset script - stash local changes and pull fresh code
 * Access: https://fuel.kittykat.tech/rev3/reset.php?key=fuel2026deploy
 */

$DEPLOY_KEY = 'fuel2026deploy';

if (!isset($_GET['key']) || $_GET['key'] !== $DEPLOY_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

header('Content-Type: text/plain');

echo "🔄 Resetting repository...\n\n";

$projectDir = __DIR__;
chdir($projectDir);

// Stash local changes
echo "📦 Stashing local changes...\n";
$stashOutput = shell_exec('git stash 2>&1');
echo $stashOutput . "\n";

// Pull latest code
echo "\n📥 Pulling latest code...\n";
$pullOutput = shell_exec('git pull origin main 2>&1');
echo $pullOutput . "\n";

echo "\n✅ Reset complete!\n";
echo "⏰ Reset at: " . date('Y-m-d H:i:s') . "\n";
