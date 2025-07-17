-- SQL script to create the reset_codes table for phone-based password reset
-- This table stores temporary reset codes sent via SMS with expiration timestamps
-- Each code is linked to a specific user_id from the users table

CREATE TABLE IF NOT EXISTS `reset_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reset_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index on code for faster lookups during verification
CREATE INDEX IF NOT EXISTS `idx_reset_codes_code` ON `reset_codes` (`code`);

-- Add index on expires_at to help with cleanup of expired codes
CREATE INDEX IF NOT EXISTS `idx_reset_codes_expires` ON `reset_codes` (`expires_at`);
