<?php
/**
 * REV 3.0 Deployment API
 * Executes deployment commands via SSH
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Security: Only allow from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Disable IP check for now (enable in production)
// if (!in_array($client_ip, $allowed_ips)) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'error' => 'Access denied']);
//     exit;
// }

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'deploy':
        deployProject();
        break;

    case 'status':
        getStatus();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function deployProject() {
    $output = [];
    $return_code = 0;

    // Get project directory
    $project_dir = dirname(__FILE__);

    $output[] = "========================================";
    $output[] = "🚀 REV 3.0 Deployment Starting...";
    $output[] = "========================================";
    $output[] = "";
    $output[] = "📂 Project directory: $project_dir";
    $output[] = "";

    // Step 1: Git Pull
    $output[] = "📥 Step 1/4: Pulling latest changes...";
    chdir($project_dir);
    exec('git pull origin main 2>&1', $git_output, $git_code);
    $output = array_merge($output, $git_output);

    if ($git_code !== 0) {
        respondWithError($output, "Git pull failed");
        return;
    }

    $output[] = "✓ Git pull completed";
    $output[] = "";

    // Step 2: Backend (already updated by git pull)
    $output[] = "🔧 Step 2/4: Backend update...";
    $output[] = "   Backend files updated from git";
    $output[] = "✓ Backend updated";
    $output[] = "";

    // Step 3: Frontend Build
    $output[] = "🏗️  Step 3/4: Building frontend...";
    $frontend_dir = $project_dir . '/frontend';

    if (!is_dir($frontend_dir)) {
        respondWithError($output, "Frontend directory not found");
        return;
    }

    chdir($frontend_dir);

    // Check if node_modules exists
    if (!is_dir('node_modules')) {
        $output[] = "   📦 Installing dependencies...";
        exec('npm install 2>&1', $npm_install_output, $npm_install_code);
        $output = array_merge($output, $npm_install_output);

        if ($npm_install_code !== 0) {
            respondWithError($output, "npm install failed");
            return;
        }
    }

    $output[] = "   🔨 Building with Vite...";
    exec('npm run build 2>&1', $build_output, $build_code);
    $output = array_merge($output, $build_output);

    if ($build_code !== 0) {
        respondWithError($output, "Frontend build failed");
        return;
    }

    $output[] = "✓ Frontend build completed";
    $output[] = "";

    // Step 4: Verify
    $output[] = "📋 Step 4/4: Verifying build...";
    $dist_dir = $frontend_dir . '/dist';

    if (!file_exists($dist_dir . '/index.html')) {
        respondWithError($output, "Build verification failed: index.html not found");
        return;
    }

    $js_files = glob($dist_dir . '/assets/*.js');
    $css_files = glob($dist_dir . '/assets/*.css');

    $output[] = "✓ index.html exists";
    $output[] = "✓ Found " . count($js_files) . " JS file(s)";
    $output[] = "✓ Found " . count($css_files) . " CSS file(s)";
    $output[] = "";

    // Success
    $output[] = "========================================";
    $output[] = "✅ Deployment completed successfully!";
    $output[] = "========================================";
    $output[] = "";
    $output[] = "🌐 Dashboard: https://fuel.kittykat.tech/rev3/frontend/dist/";
    $output[] = "   Remember to hard refresh (Ctrl+Shift+R)";

    echo json_encode([
        'success' => true,
        'output' => $output,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}

function getStatus() {
    $project_dir = dirname(__FILE__);
    $frontend_dist = $project_dir . '/frontend/dist';

    $status = [
        'backend' => 'Ready',
        'frontend' => file_exists($frontend_dist . '/index.html') ? 'Built' : 'Needs Build',
        'last_deploy' => file_exists($frontend_dist . '/index.html') ?
            date('Y-m-d H:i:s', filemtime($frontend_dist . '/index.html')) :
            'Never',
        'project_dir' => $project_dir
    ];

    echo json_encode([
        'success' => true,
        'status' => $status
    ], JSON_PRETTY_PRINT);
}

function respondWithError($output, $error_message) {
    $output[] = "";
    $output[] = "❌ ERROR: $error_message";

    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'output' => $output
    ], JSON_PRETTY_PRINT);
}
