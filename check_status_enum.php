<?php
require_once __DIR__ . '/config/db.php';
$cols = dbFetchAll("SHOW COLUMNS FROM documents");
foreach ($cols as $col) {
    if ($col['Field'] == 'status') {
        echo "Status Column Type: " . $col['Type'] . "\n";
    }
}
