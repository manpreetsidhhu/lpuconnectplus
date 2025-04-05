<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    // Verify password
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
        exit();
    }

    // Update user details
    $update_query = "UPDATE users SET fullname = ?, email = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $fullname, $email, $user_id);

    if ($update_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
    }

    $update_stmt->close();
}

$conn->close();
?>