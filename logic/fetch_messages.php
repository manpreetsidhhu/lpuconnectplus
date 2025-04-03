<?php
session_start();
include 'db_connect.php';

$sender_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];

$query = "SELECT 
            message, 
            sender_id = ? AS is_sender, 
            is_read, 
            DATE(sent_on) AS sent_on,
            DATE_FORMAT(sent_at, '%H:%i') AS sent_at 
          FROM messages 
          WHERE (sender_id = ? AND receiver_id = ?) 
             OR (sender_id = ? AND receiver_id = ?) 
          ORDER BY sent_on DESC, sent_at ASC";  // Date DESC, Time ASC

$stmt = $conn->prepare($query);
$stmt->bind_param("iiiii", $sender_id, $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(["messages" => $messages]);

$stmt->close();
$conn->close();

?>