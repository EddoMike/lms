<?php
/**
 * Script to add profile_picture field to users table
 * This script reads the SQL from sql/add_profile_picture_field.sql and executes it
 * It provides feedback on the success or failure of the operation
 * Run this script once to set up the profile picture functionality
 */

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
        return "Success: SQL file executed. Profile picture field added successfully.";
    } catch (PDOException $e) {
        return "Error executing SQL: " . $e->getMessage();
    }
}

// Path to SQL file
$sqlFile = __DIR__ . '/sql/add_profile_picture_field.sql';

// Execute the SQL file
$result = executeSqlFile($pdo, $sqlFile);

// Output result
echo "<h1>Profile Picture Field Setup</h1>";
echo "<p>{$result}</p>";
echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
?>
