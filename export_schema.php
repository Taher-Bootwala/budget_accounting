<?php
/**
 * Export database schema to JSON file
 */

$host = 'localhost';
$dbname = 'budget_accounting';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = ['users', 'contacts', 'products', 'cost_centers', 'auto_analytical_rules', 
               'budgets', 'budget_revisions', 'documents', 'document_lines', 
               'transactions', 'payments', 'portal_access'];
    
    $schema = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $schema[$table] = $columns;
    }
    
    // Save to JSON file
    $json = json_encode($schema, JSON_PRETTY_PRINT);
    file_put_contents('schema_export.json', $json);
    
    echo "Schema exported successfully!\n";
    print_r($schema);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
