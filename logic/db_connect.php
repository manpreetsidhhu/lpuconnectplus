<?php
$servername = "localhost:3307";
$username = "root";
$password = "mysql@preet2549c1c9";
$database = "lpuconnectplus";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    echo "Connection failed";
}
?>
