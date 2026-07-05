<?php
// APK Download endpoint — serves the SteadyPath APK with proper headers

$file = __DIR__ . '/apk/steadypath-release.apk';

if (!file_exists($file)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>APK Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h1>📱 APK Not Found</h1><p>The APK file is being prepared. Please check back soon.</p><a href="index.php">Back to SteadyPath</a></body></html>';
    exit;
}

$filename = 'SteadyPath-Recovery-Companion.apk';

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=86400');

readfile($file);
exit;
