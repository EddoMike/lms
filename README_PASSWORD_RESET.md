# Phone-Based Password Reset Implementation

## Overview
This document explains the implementation of phone-based password reset functionality in the Laundry Management System (LMS). The system now allows users to reset their passwords using their registered phone numbers instead of email.

## Files Modified/Created

1. **login.php**
   - Modified to redirect to forgot_password.php for password reset
   - Updated the reset form UI to request phone number instead of email
   - Added handling of success messages from password reset process

2. **forgot_password.php**
   - Updated to pre-fill phone number when redirected from login page
   - Uses the reset_codes table to store temporary reset codes
   - Sends reset codes via SMS and allows users to set new passwords

3. **reset_password_phone.php**
   - New file for changing password after logging in with a temporary password
   - Allows users to set a new permanent password

4. **reset_codes.sql**
   - SQL script to create the `reset_codes` table in the database
   - Stores temporary reset codes with expiration timestamps

5. **create_reset_codes_table.php**
   - PHP script to execute the SQL and create the reset_codes table
   - Provides feedback on the success or failure of the operation

6. **includes/cleanup_reset_codes.php**
   - Utility script to clean up expired reset codes
   - Can be run manually or scheduled as a cron job

## Database Changes

A new table `reset_codes` has been added with the following structure:
```sql
CREATE TABLE IF NOT EXISTS `reset_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reset_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
);
```

## Setup Instructions

1. **Create the reset_codes table**:
   There are two ways to create the required database table:

   a. **Using the PHP script (recommended)**:
      - Navigate to http://localhost/lms/create_reset_codes_table.php in your browser
      - You should see a success message if the table was created successfully

   b. **Using SQL directly**:
      ```
      mysql -u username -p laundry_db < reset_codes.sql
      ```
      Or import the SQL file using phpMyAdmin.

2. **Test the password reset functionality**:
   - Go to the login page (http://localhost/lms/login.php)
   - Click "Forgot Password?"
   - Enter a registered phone number
   - Click "Send Reset Code"
   - Check for the SMS with the reset code (in a production environment)
   - Enter the code and your new password
   - Log in with your new password

## Password Reset Flow

1. User clicks "Forgot Password?" on the login page
2. User enters their registered phone number
3. System verifies the phone number exists in the database
4. System generates a random 6-digit code
5. System stores the code in the reset_codes table with an expiration time
6. System sends the code via SMS
7. User enters the code and a new password
8. System verifies the code is valid and not expired
9. System updates the user's password and deletes the used code
10. User is redirected to login with a success message

## Security Considerations

- Reset codes expire after 15 minutes
- Reset codes are deleted after use
- SMS delivery is logged for audit purposes
- Failed reset attempts are logged
- Expired reset codes are automatically cleaned up
- Password hashing uses secure bcrypt algorithm

## Troubleshooting

If users are not receiving SMS messages:
1. Check SMS API credentials in the code (forgot_password.php)
2. Verify the phone number format is correct
3. Check server error logs for API response details
4. Ensure the SMS API service is operational

If the reset_codes table is not being created:
1. Check database connection settings in includes/db_connect.php
2. Ensure the database user has CREATE TABLE privileges
3. Check PHP error logs for any database connection issues

## Future Improvements

1. Add phone number validation
2. Implement rate limiting for reset attempts
3. Add option to configure SMS API credentials through admin panel
4. Create an admin interface to view and manage reset attempts
