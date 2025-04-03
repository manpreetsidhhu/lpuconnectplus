<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($data['receiver_id']) ? intval($data['receiver_id']) : null;
    $message = isset($data['message']) ? trim($data['message']) : '';

    if ($receiver_id && !empty($message)) {
        $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to insert message."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "Invalid input data."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
}

$conn->close();
?>
