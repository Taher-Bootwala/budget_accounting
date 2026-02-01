<?php
require_once __DIR__ . '/config/db.php';

echo "Updating database schema...\n";

try {
    // Add password_hash column
    dbExecute("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL");
    echo "Added password_hash column.\n";
} catch (Exception $e) {
    echo "password_hash column might already exist or error: " . $e->getMessage() . "\n";
}

try {
    // Add username column
    dbExecute("ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL UNIQUE");
    echo "Added username column.\n";
} catch (Exception $e) {
    echo "username column might already exist or error: " . $e->getMessage() . "\n";
}

// Update existing users with a default password (e.g., 'password123') for testing
// AND set a default username based on email
$users = dbFetchAll("SELECT * FROM users");
foreach ($users as $user) {
    $defaultPass = password_hash('password123', PASSWORD_DEFAULT);
    $username = explode('@', $user['email'])[0]; // simple username from email
    
    // check if username exists to avoid dupes
    $count = 0;
    $originalUsername = $username;
    while(dbFetchValue("SELECT count(*) FROM users WHERE username = ? AND id != ?", [$username, $user['id']]) > 0) {
        $count++;
        $username = $originalUsername . $count;
    }

    dbExecute("UPDATE users SET password_hash = ?, username = ? WHERE id = ?", [$defaultPass, $username, $user['id']]);
    echo "Updated user {$user['email']} with default password and username: $username\n";
}

echo "Database update complete.\n";
