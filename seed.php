<?php
/**
 * Database Seed Script
 * Creates demo data for Budget Accounting ERP
 * Run once: http://localhost/Furniture/seed.php
 * 
 * IMPORTANT: Since the users table has no password column,
 * login will use a simple email-role check for demo purposes.
 */

require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    echo "<h1>🌱 Seeding Database...</h1>";

    // Check if already seeded
    $existing = dbFetchOne("SELECT COUNT(*) as count FROM users");
    if ($existing['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Database already has data. Clearing and re-seeding...</p>";

        // Clear tables in order (respecting foreign keys)
        $tables = [
            'payments',
            'transactions',
            'document_lines',
            'documents',
            'budget_revisions',
            'budgets',
            'auto_analytical_rules',
            'portal_access',
            'products',
            'contacts',
            'cost_centers',
            'users'
        ];
        foreach ($tables as $table) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DELETE FROM $table");
            $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    // 1. Users (no password column in schema - use email for demo login)
    echo "<p>👤 Creating users...</p>";
    dbInsert(
        "INSERT INTO users (name, email, role) VALUES (?, ?, ?)",
        ['Admin User', 'admin@demo.com', 'admin']
    );
    dbInsert(
        "INSERT INTO users (name, email, role) VALUES (?, ?, ?)",
        ['Portal User', 'customer@demo.com', 'portal']
    );

    // 2. Cost Centers (no description column)
    echo "<p>🎯 Creating cost centers...</p>";
    $costCenters = ['Manufacturing', 'Marketing', 'R&D', 'Operations', 'IT Infrastructure'];
    foreach ($costCenters as $cc) {
        dbInsert("INSERT INTO cost_centers (name) VALUES (?)", [$cc]);
    }

    // 3. Contacts (only id, name, type, created_at)
    echo "<p>👥 Creating contacts...</p>";
    $contacts = [
        ['Acme Suppliers', 'vendor'],
        ['Global Materials Ltd', 'vendor'],
        ['Tech Solutions Inc', 'vendor'],
        ['Furniture World', 'customer'],
        ['Home Decor Hub', 'customer'],
        ['Office Interiors', 'customer']
    ];
    foreach ($contacts as $c) {
        dbInsert("INSERT INTO contacts (name, type) VALUES (?, ?)", $c);
    }

    // 4. Products
    echo "<p>📦 Creating products...</p>";
    $products = [
        ['Oak Wood Panels', 'Raw Materials', 2500.00],
        ['Metal Fasteners Pack', 'Raw Materials', 450.00],
        ['Premium Fabric Roll', 'Raw Materials', 3200.00],
        ['Executive Desk', 'Furniture', 25000.00],
        ['Ergonomic Chair', 'Furniture', 12000.00],
        ['Conference Table', 'Furniture', 45000.00],
        ['Storage Cabinet', 'Furniture', 8500.00],
        ['Office Partition', 'Accessories', 5500.00],
        ['LED Desk Lamp', 'Accessories', 1200.00],
        ['Cable Management Kit', 'Accessories', 800.00]
    ];
    foreach ($products as $p) {
        dbInsert("INSERT INTO products (name, category, price) VALUES (?, ?, ?)", $p);
    }

    // 5. Budgets
    echo "<p>💰 Creating budgets...</p>";
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t', strtotime('+2 months'));

    $budgets = [
        [1, 500000.00], // Manufacturing
        [2, 150000.00], // Marketing
        [3, 200000.00], // R&D
        [4, 300000.00], // Operations
        [5, 250000.00]  // IT
    ];
    foreach ($budgets as $b) {
        dbInsert(
            "INSERT INTO budgets (cost_center_id, amount, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')",
            [$b[0], $b[1], $startDate, $endDate]
        );
    }

    // 6. Auto Analytical Rules
    echo "<p>⚙️ Creating auto rules...</p>";
    $rules = [
        ['category', 'Raw Materials', 1], // Manufacturing
        ['category', 'Furniture', 1],     // Manufacturing
        ['category', 'Accessories', 4],   // Operations
        ['product', '1', 1],              // Oak panels → Manufacturing
        ['product', '9', 5]               // LED Lamp → IT
    ];
    foreach ($rules as $r) {
        dbInsert("INSERT INTO auto_analytical_rules (rule_type, rule_value, cost_center_id) VALUES (?, ?, ?)", $r);
    }

    // 7. Documents (no document_number column)
    echo "<p>📄 Creating documents...</p>";

    // Vendor Bill 1
    $billId1 = dbInsert(
        "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) VALUES (?, ?, ?, ?, ?)",
        ['VendorBill', 1, 1, 75000.00, 'posted']
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$billId1, 1, 20, 2500.00, 50000.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$billId1, 2, 50, 450.00, 22500.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$billId1, 10, 3, 833.33, 2500.00]
    );

    // Transactions for Bill 1
    dbInsert(
        "INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) VALUES (?, ?, ?, CURDATE())",
        [$billId1, 1, 72500.00]
    );
    dbInsert(
        "INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) VALUES (?, ?, ?, CURDATE())",
        [$billId1, 4, 2500.00]
    );

    // Vendor Bill 2
    $billId2 = dbInsert(
        "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) VALUES (?, ?, ?, ?, ?)",
        ['VendorBill', 3, 5, 125000.00, 'posted']
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$billId2, 9, 20, 1200.00, 24000.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$billId2, 10, 125, 800.00, 100000.00]
    );

    // Transaction for Bill 2
    dbInsert(
        "INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) VALUES (?, ?, ?, CURDATE())",
        [$billId2, 5, 125000.00]
    );

    // Customer Invoice 1
    $invId1 = dbInsert(
        "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) VALUES (?, ?, ?, ?, ?)",
        ['CustomerInvoice', 5, 1, 82000.00, 'posted']
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$invId1, 4, 2, 25000.00, 50000.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$invId1, 5, 2, 12000.00, 24000.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$invId1, 8, 1, 5500.00, 5500.00]
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$invId1, 10, 3, 833.33, 2500.00]
    );

    // Customer Invoice 2 (Partially paid)
    $invId2 = dbInsert(
        "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) VALUES (?, ?, ?, ?, ?)",
        ['CustomerInvoice', 5, 1, 45000.00, 'posted']
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$invId2, 6, 1, 45000.00, 45000.00]
    );

    // Marketing expenses (linked to invoice for now since document_id is NOT NULL)
    $marketingDoc = dbInsert(
        "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) VALUES (?, ?, ?, ?, ?)",
        ['VendorBill', 2, 2, 57000.00, 'posted']
    );
    dbInsert(
        "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) VALUES (?, ?, ?, ?, ?)",
        [$marketingDoc, 8, 10, 5700.00, 57000.00]
    );
    dbInsert(
        "INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) VALUES (?, ?, ?, ?)",
        [$marketingDoc, 2, 35000.00, date('Y-m-d', strtotime('-5 days'))]
    );
    dbInsert(
        "INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) VALUES (?, ?, ?, ?)",
        [$marketingDoc, 2, 22000.00, date('Y-m-d', strtotime('-10 days'))]
    );

    // 8. Payments
    echo "<p>💳 Creating payments...</p>";
    dbInsert(
        "INSERT INTO payments (document_id, paid_amount, payment_method, razorpay_payment_id, payment_date) VALUES (?, ?, ?, ?, CURDATE())",
        [$billId1, 75000.00, 'bank_transfer', 'pay_demo_001']
    );
    dbInsert(
        "INSERT INTO payments (document_id, paid_amount, payment_method, razorpay_payment_id, payment_date) VALUES (?, ?, ?, ?, CURDATE())",
        [$invId2, 20000.00, 'card', 'pay_demo_002']
    );

    // 9. Portal Access
    echo "<p>🔑 Setting up portal access...</p>";
    dbInsert("INSERT INTO portal_access (user_id, contact_id) VALUES (?, ?)", [2, 5]); // Portal user → Home Decor Hub

    // 10. Budget Revision (no reason column)
    echo "<p>📝 Creating budget revision...</p>";
    dbInsert(
        "INSERT INTO budget_revisions (budget_id, old_amount, new_amount) VALUES (?, ?, ?)",
        [1, 400000.00, 500000.00]
    );

    echo "<h2 style='color: green;'>✅ Database seeded successfully!</h2>";
    echo "<h3>Demo Credentials:</h3>";
    echo "<p><strong>Admin:</strong> admin@demo.com (any password)</p>";
    echo "<p><strong>Portal:</strong> customer@demo.com (any password)</p>";
    echo "<p style='color: orange;'>Note: Since the users table has no password column, login uses email validation only.</p>";
    echo "<p><a href='/Furniture/login.php' style='font-size: 18px;'>→ Go to Login</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
