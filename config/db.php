<?php
/**
 * ============================================================================
 * DATABASE CONFIGURATION & HELPER FUNCTIONS
 * ============================================================================
 * 
 * PDO-based database abstraction layer with prepared statements
 * 
 * This file provides:
 * - Database connection configuration (MySQL/MariaDB)
 * - Singleton PDO connection with error handling
 * - CRUD helper functions with SQL injection protection
 * 
 * HELPER FUNCTIONS:
 * - getDB()       : Returns singleton PDO connection
 * - dbFetchAll()  : Execute query, return all rows
 * - dbFetchOne()  : Execute query, return single row
 * - dbInsert()    : Execute INSERT, return last insert ID
 * - dbExecute()   : Execute query, return affected row count
 * - dbFetchValue(): Execute query, return single value
 * 
 * SECURITY FEATURES:
 * - All queries use prepared statements (SQL injection safe)
 * - PDO error mode set to ERRMODE_EXCEPTION
 * - Emulate prepares disabled for true prepared statements
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_accounting');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * @return PDO
 */
function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Execute a query and return all results
 * @param string $sql
 * @param array $params
 * @return array
 */
function dbFetchAll($sql, $params = [])
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbFetchOne($sql, $params = [])
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Execute a query and return the last insert ID
 * @param string $sql
 * @param array $params
 * @return string
 */
function dbInsert($sql, $params = [])
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return getDB()->lastInsertId();
}

/**
 * Execute a query and return affected rows count
 * @param string $sql
 * @param array $params
 * @return int
 */
function dbExecute($sql, $params = [])
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Get a single value from query
 * @param string $sql
 * @param array $params
 * @return mixed
 */
function dbFetchValue($sql, $params = [])
{
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}
