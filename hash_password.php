<?php
// Special for admin account password - need hashed the password from here
// Copy the generated hashed password and then paste into database for admin_pw
// For the first admin in the system only
// Function to generate hashed password
function generateHashedPassword($plainPassword) {
    return password_hash($plainPassword, PASSWORD_DEFAULT);
}

// Example usage: Generate hashed password for 'admin1'
$plainPassword = 'Admin@123';
$hashedPassword = generateHashedPassword($plainPassword);

// Output the hashed password
echo "Plain Password: " . $plainPassword . "\n";
echo "Hashed Password: " . $hashedPassword . "\n";
?>
