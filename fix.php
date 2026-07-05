<?php
if (file_exists('index.html')) {
    if (rename('index.html', 'pwa.html')) {
        echo "SUCCESS: index.html renamed to pwa.html\n";
    } else {
        echo "FAILED: Could not rename index.html\n";
        echo "Trying to delete instead...\n";
        if (unlink('index.html')) {
            echo "SUCCESS: index.html deleted\n";
        } else {
            echo "FAILED: Could not delete index.html\n";
            echo "Error: " . error_get_last()['message'] . "\n";
        }
    }
} else {
    echo "index.html not found (already renamed or deleted)\n";
}

if (file_exists('pwa.html')) {
    echo "pwa.html exists\n";
}

if (file_exists('index.php')) {
    echo "index.php exists\n";
}

unlink(__FILE__);
echo "cleanup script self-deleted\n";
?>