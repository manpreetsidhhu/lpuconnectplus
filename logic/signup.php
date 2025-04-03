<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    if (empty($name) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        header("Location: ../signup.php?error=emptyfields");
        exit();
    }

    if ($password !== $confirmPassword) {
        header("Location: ../signup.php?error=passwordmismatch");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: ../signup.php?error=emailtaken");
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        header("Location: ../login.php?signup=success");
        exit();
    } else {
        header("Location: ../signup.php?error=signupfailed");
        exit();
    }
}
header("Location: ../signup.php");
exit();
