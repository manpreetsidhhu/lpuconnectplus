<!-- //password_hashing.php -->
<?php
// Hash the password
$password = "password123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: $hashed_password";
?>