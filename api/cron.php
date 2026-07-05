<?php
// Photon Bounce SaaS — Web Cron Endpoint
// Can be triggered by external cron services (e.g., cron-job.org, UptimeRobot)
// Or by cPanel cron: curl -s https://photon-bounce.com/leads/cron.php?key=YOUR_SECRET

$SECRET = getenv('PB_CRON_KEY') ?: 'photon-bounce-cron-' . filemtime(__FILE__);

// Check secret key
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden. Use ?key=...\n";
    exit;
}

header('Content-Type: text/plain');

$cron_script = __DIR__ . '/cron.py';
if (!file_exists($cron_script)) {
    echo "ERROR: cron.py not found at $cron_script\n";
    exit(1);
}

// Find python3
$python = trim(shell_exec('which python3 2>/dev/null') ?: shell_exec('which python 2>/dev/null'));
if (!$python) {
    // Try common paths
    foreach (['/usr/bin/python3', '/usr/local/bin/python3', '/opt/python3/bin/python3'] as $p) {
        if (file_exists($p)) { $python = $p; break; }
    }
}

if (!$python) {
    echo "ERROR: Python3 not found on server\n";
    exit(1);
}

echo "Photon Bounce SaaS Cron Runner\n";
echo "================================\n";
echo "Python: $python\n";
echo "Script: $cron_script\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// Run the cron wrapper
chdir(__DIR__);
passthru(escapeshellcmd($python) . ' ' . escapeshellarg($cron_script) . ' 2>&1', $exit_code);

echo "\n================================\n";
echo "Exit code: $exit_code\n";
echo "Finished: " . date('Y-m-d H:i:s') . "\n";

exit($exit_code);
