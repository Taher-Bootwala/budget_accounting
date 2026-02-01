<?php
require_once __DIR__ . '/config/db.php';
$inv = dbFetchOne("SELECT total_amount FROM documents WHERE id=11");
$totalPaid = dbFetchValue("SELECT SUM(paid_amount) FROM payments WHERE document_id=11");
echo "Invoice Total: " . $inv['total_amount'] . "\n";
echo "Total Paid: " . $totalPaid . "\n";
