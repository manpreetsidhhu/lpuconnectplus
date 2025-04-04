<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

$query = "
    SELECT u.id, u.username, u.fullname,
           m.message AS latest_message, m.sent_at AS latest_message_time, m.sender_id
    FROM users u
    LEFT JOIN (
        SELECT sender_id, receiver_id, message, sent_at
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
        ORDER BY sent_at DESC
    ) m ON (u.id = m.sender_id AND m.receiver_id = ?) OR (u.id = m.receiver_id AND m.sender_id = ?)
    GROUP BY u.id
    ORDER BY MAX(m.sent_at) DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        "id" => $row["id"],
        "username" => $row["username"],
        "fullname" => $row["fullname"],
        "latest_message" => $row["latest_message"] ?? "",
        "latest_message_time" => $row["latest_message_time"] ?? "",
        "sender_id" => $row["sender_id"]
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["users" => $users]);
?>
