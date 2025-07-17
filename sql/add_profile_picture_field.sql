-- SQL script to add profile_picture field to users table
-- This adds a VARCHAR field to store the path to the user's profile picture
-- Default value is set to a placeholder image

-- In MySQL, column comments are added directly in the ALTER TABLE statement
ALTER TABLE `users` 
ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT 'assets/images/default-profile.png' COMMENT 'Path to user profile picture image' 
AFTER `role`;
