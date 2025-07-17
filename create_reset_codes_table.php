<?php
/**
 * Script to create the reset_codes table in the database
 * This script reads the SQL from reset_codes.sql and executes it
 * It provides feedback on the success or failure of the operation
 * Run this script once to set up the password reset functionality
 * Uses East African Time (EAT/UTC+3) for consistent timezone handling
 */

// Set timezone to East African Time (Arusha, Tanzania)
date_default_timezone_set('Africa/Dar_es_Salaam');

// Include database connection
require_once 'includes/db_connect.php';

// Function to execute SQL file
function executeSqlFile($pdo, $file) {
    // Check if file exists
    if (!file_exists($file)) {
        return "Error: SQL file not found: $file";
    }
    
    // Read SQL file content
    $sql = file_get_contents($file);
    if (!$sql) {
        return "Error: Could not read SQL file: $file";
    }
    
    try {
        // Execute SQL queries
        $result = $pdo->exec($sql);
        return "Success: SQL file executed. Table created successfully.";
    } catch (PDOException $e) {
        return "Error executing SQL: " . $e->getMessage();
    }
}

// Path to SQL file
$sqlFile = __DIR__ . '/reset_codes.sql';

// Execute the SQL file
$result = executeSqlFile($pdo, $sqlFile);

// Output result
echo "<h1>Reset Codes Table Setup</h1>";
echo "<p>{$result}</p>";
echo "<p><a href='login.php'>Return to Login Page</a></p>";
?>
