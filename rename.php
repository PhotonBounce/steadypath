<?php
if (file_exists('index.html')) {
    rename('index.html', 'pwa.html');
    echo "Renamed index.html to pwa.html\n";
} else {
    echo "index.html not found\n";
}

if (file_exists('index.html') && !file_exists('pwa.html')) {
    echo "ERROR: rename failed\n";
} else {
    echo "OK: pwa.html exists\n";
}
?>