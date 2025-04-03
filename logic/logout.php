<?php
session_start(); // Destroy all session variables
session_unset(); // Destroy the session itself
session_destroy(); // Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
// Redirect to login page
header("Location: ../index.php");
exit();
?>
