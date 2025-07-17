<?php
/**
 * Cleanup script for expired reset codes
 * This script removes expired reset codes from the reset_codes table
 * It can be called periodically or included in other scripts
 * 
 * The script connects to the database, deletes expired codes, and logs the cleanup operation
 * It helps maintain database cleanliness and security by removing unused codes
 * Uses East African Time (EAT/UTC+3) for consistent timezone handling
 */

// Set timezone to East African Time (Arusha, Tanzania)
date_default_timezone_set('Africa/Dar_es_Salaam');

// Include database connection
require_once 'db_connect.php';

/**
 * Function to clean up expired reset codes
 * Removes all reset codes that have passed their expiration time
 * Returns the number of deleted codes
 * 
 * @return int Number of deleted codes
 */
function cleanupExpiredResetCodes() {
    global $pdo;
    
    try {
        // Delete all expired reset codes
        $stmt = $pdo->prepare("DELETE FROM reset_codes WHERE expires_at < NOW()");
        $stmt->execute();
        
        // Get count of deleted rows
        $count = $stmt->rowCount();
        
        // Log the cleanup operation
        if ($count > 0) {
            error_log("Cleaned up $count expired reset codes");
        }
        
        return $count;
    } catch (PDOException $e) {
        // Log any errors that occur during cleanup
        error_log("Error cleaning up expired reset codes: " . $e->getMessage());
        return 0;
    }
}

// Execute cleanup if this script is called directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $deleted = cleanupExpiredResetCodes();
    echo "Cleaned up $deleted expired reset codes.";
}
?>
