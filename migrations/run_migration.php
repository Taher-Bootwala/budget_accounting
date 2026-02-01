<?php
/**
 * Run this file to execute database migrations
 * Access via: http://localhost/Furniture/migrations/run_migration.php
 */

require_once __DIR__ . '/../config/db.php';

echo "<h2>Database Migration Runner</h2>";

try {
    // Migration 1: Add archived column to products
    $result = dbFetchAll("SHOW COLUMNS FROM products LIKE 'archived'");
    if (empty($result)) {
        dbExecute("ALTER TABLE products ADD COLUMN archived TINYINT(1) DEFAULT 0");
        echo "<p style='color: green;'>✓ Added 'archived' column to products table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'archived' column already exists in products table</p>";
    }
    
    // Migration 2: Add archived column to contacts
    $result = dbFetchAll("SHOW COLUMNS FROM contacts LIKE 'archived'");
    if (empty($result)) {
        dbExecute("ALTER TABLE contacts ADD COLUMN archived TINYINT(1) DEFAULT 0");
        echo "<p style='color: green;'>✓ Added 'archived' column to contacts table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'archived' column already exists in contacts table</p>";
    }
    
    // Set all existing records as active
    dbExecute("UPDATE products SET archived = 0 WHERE archived IS NULL");
    dbExecute("UPDATE contacts SET archived = 0 WHERE archived IS NULL");
    echo "<p style='color: green;'>✓ Set all existing records as active (archived = 0)</p>";
    
    // Migration 3: Create contact_tags table
    $result = dbFetchAll("SHOW TABLES LIKE 'contact_tags'");
    if (empty($result)) {
        dbExecute("
            CREATE TABLE contact_tags (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "<p style='color: green;'>✓ Created 'contact_tags' table</p>";
        
        // Insert sample tags
        dbExecute("INSERT INTO contact_tags (name) VALUES ('Premium Partner'), ('Regular Partner'), ('Wholesale'), ('Retail')");
        echo "<p style='color: green;'>✓ Inserted sample contact tags</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'contact_tags' table already exists</p>";
    }
    
    // Migration 4: Add tag_id to contacts
    $result = dbFetchAll("SHOW COLUMNS FROM contacts LIKE 'tag_id'");
    if (empty($result)) {
        dbExecute("ALTER TABLE contacts ADD COLUMN tag_id INT(11) NULL AFTER type");
        echo "<p style='color: green;'>✓ Added 'tag_id' column to contacts table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'tag_id' column already exists in contacts table</p>";
    }
    
    // Migration 5: Create auto_analytical_models table
    $result = dbFetchAll("SHOW TABLES LIKE 'auto_analytical_models'");
    if (empty($result)) {
        dbExecute("
            CREATE TABLE auto_analytical_models (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                status ENUM('draft','confirmed','cancelled') NOT NULL DEFAULT 'draft',
                partner_tag_id INT(11) NULL,
                product_category VARCHAR(100) NULL,
                partner_id INT(11) NULL,
                product_id INT(11) NULL,
                cost_center_id INT(11) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_cost_center (cost_center_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "<p style='color: green;'>✓ Created 'auto_analytical_models' table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'auto_analytical_models' table already exists</p>";
    }
    
    // Migration 6: Add name column to budgets table
    $result = dbFetchAll("SHOW COLUMNS FROM budgets LIKE 'name'");
    if (empty($result)) {
        dbExecute("ALTER TABLE budgets ADD COLUMN name VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
        echo "<p style='color: green;'>✓ Added 'name' column to budgets table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'name' column already exists in budgets table</p>";
    }
    
    // Migration 7: Add revised_from_id to budgets
    $result = dbFetchAll("SHOW COLUMNS FROM budgets LIKE 'revised_from_id'");
    if (empty($result)) {
        dbExecute("ALTER TABLE budgets ADD COLUMN revised_from_id INT(11) NULL AFTER status");
        echo "<p style='color: green;'>✓ Added 'revised_from_id' column to budgets table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'revised_from_id' column already exists in budgets table</p>";
    }
    
    // Migration 8: Update budgets status enum to include confirmed, revised, cancelled
    dbExecute("ALTER TABLE budgets MODIFY COLUMN status ENUM('draft','active','confirmed','revised','cancelled') NOT NULL DEFAULT 'draft'");
    echo "<p style='color: green;'>✓ Updated budgets status enum</p>";
    
    // Migration 9: Create budget_lines table
    $result = dbFetchAll("SHOW TABLES LIKE 'budget_lines'");
    if (empty($result)) {
        dbExecute("
            CREATE TABLE budget_lines (
                id INT(11) NOT NULL AUTO_INCREMENT,
                budget_id INT(11) NOT NULL,
                analytical_model_id INT(11) NOT NULL,
                type ENUM('income','expense') NOT NULL,
                budgeted_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_budget (budget_id),
                KEY idx_analytical_model (analytical_model_id),
                KEY idx_type (type),
                CONSTRAINT fk_budget_lines_budget FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
                CONSTRAINT fk_budget_lines_model FOREIGN KEY (analytical_model_id) REFERENCES auto_analytical_models(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "<p style='color: green;'>✓ Created 'budget_lines' table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'budget_lines' table already exists</p>";
    }
    
    echo "<h3 style='color: green;'>All migrations completed successfully!</h3>";
    echo "<p><a href='/Furniture/views/analytical_models/index.php'>Go to Analytical Models</a> | <a href='/Furniture/views/contacts/index.php'>Go to Contacts</a> | <a href='/Furniture/views/budgets/index.php'>Go to Budgets</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
