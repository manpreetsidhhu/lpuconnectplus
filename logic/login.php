<?php
session_start();
include 'db_connect.php'; // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=emptyfields");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $db_password);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            // Store user data in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $db_username;

            // Redirect to the dashboard
            header("Location: ../index.php");
            exit();
        } else {
            header("Location: ../login.php?error=invalidpassword");
            exit();
        }
    } else {
        header("Location: ../login.php?error=usernotfound");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../login.php");
    exit();
}
?>
