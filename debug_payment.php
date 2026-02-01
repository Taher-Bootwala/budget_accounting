<?php
require_once __DIR__ . '/config/db.php';
$inv = dbFetchOne("SELECT * FROM documents WHERE doc_type='CustomerInvoice' AND id=11");
$payments = dbFetchAll("SELECT * FROM payments WHERE document_id=11");
$totalPaid = dbFetchValue("SELECT SUM(paid_amount) FROM payments WHERE document_id=11");

print_r($inv);
echo "Total Paid (calculated): " . $totalPaid . "\n";
print_r($payments);
