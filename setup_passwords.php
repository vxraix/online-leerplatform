<?php
/**
 * Setup Script - Generate Password Hashes
 * 
 * Run this script once to generate proper password hashes for the default accounts.
 * Copy the output and update database.sql or run directly in database.
 * 
 * Usage: php setup_passwords.php
 */

echo "Password Hash Generator\n";
echo "======================\n\n";

$passwords = [
    'admin123' => 'admin',
    'student123' => 'student1'
];

foreach ($passwords as $password => $username) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Username: {$username}\n";
    echo "Password: {$password}\n";
    echo "Hash: {$hash}\n";
    echo "\nSQL Update:\n";
    echo "UPDATE users SET password_hash = '{$hash}' WHERE username = '{$username}';\n";
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "\nNote: The default hash in database.sql corresponds to password 'password'.\n";
echo "Run the UPDATE statements above to set proper passwords.\n";


