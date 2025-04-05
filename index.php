<?php
session_start();
include 'logic/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];
$query = "SELECT username, fullname FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Generate initials from username
$nameParts = explode(" ", trim($user['fullname']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LPU Connect+</title>
    <link rel="icon" href="media/lpuicon.svg">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/chat.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/users.css">
    <link rel="stylesheet" href="css/darkmode.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <header class="header-container">
        <div class="header-left">
            <img src="media/lpuicon.svg" alt="LPU Logo">
            <h1 class="header-title">
                <span class="full-title">LPU<br>Connect+</span>
                <span class="short-title">LC+</span>
            </h1>
        </div>
        <div class="header-right">
            <span class="username">
                <?php echo htmlspecialchars($user['fullname']); ?><br>
                <span style="color: grey;">( @<?php echo htmlspecialchars($user['username']); ?> )</span>
            </span>
            <div class="profile-placeholder"><?php echo $initials; ?> </div>
            <div class="options-menu">
                <button class="options-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="options-dropdown">
                    <button class="popup-option" id="visitProfile">Visit Profile</button>
                    <div class="popup-option">
                        <span>Dark Mode</span>
                        <label class="switch">
                            <input type="checkbox" id="themeToggleCheckbox">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <a href="logic/logout.php" class="logout-button">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="chat-container">


        <!-- Left: User List -->
        <div class="user-list">
            <!-- Chat Controls -->
            <div class="chat-controls">
                <button id="chatButton" class="control-button">Chats</button>
                <button id="statusButton" class="control-button">Status</button>
                <button id="addFriendButton" class="control-button">Add Friend</button>
            </div>
            <input type="text" id="searchUsers" placeholder="Search users...">
            <ul id="userList">
                <?php
                include 'logic/db_connect.php';

                // Fetch users and their latest messages using a subquery
                $query = "
                    SELECT u.id, u.username, u.fullname, 
                           lm.message, lm.sent_at, lm.sender_id
                    FROM users u
                    LEFT JOIN (
                        SELECT m1.sender_id, m1.receiver_id, m1.message, m1.sent_at
                        FROM messages m1
                        INNER JOIN (
                            SELECT 
                                CASE 
                                    WHEN sender_id = ? THEN receiver_id 
                                    ELSE sender_id 
                                END AS user_id,
                                MAX(sent_at) AS latest_time
                            FROM messages
                            WHERE sender_id = ? OR receiver_id = ?
                            GROUP BY user_id
                        ) m2 ON ((m1.sender_id = ? AND m1.receiver_id = m2.user_id) OR (m1.receiver_id = ? AND m1.sender_id = m2.user_id))
                        AND m1.sent_at = m2.latest_time
                    ) lm ON (u.id = lm.sender_id AND lm.receiver_id = ?) OR (u.id = lm.receiver_id AND lm.sender_id = ?)
                    ORDER BY lm.sent_at DESC
                ";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $name = htmlspecialchars($row['fullname']);
                    if (empty($name)) {
                        $name = htmlspecialchars($row['username']);
                    }
                    $isYou = ($row['id'] == $_SESSION['user_id']);
                    $displayName = $isYou ? "$name (You)" : $name;
                    $profileColor = $isYou ? '#333' : '#f47d1c';
                    $nameletter = strtoupper($name);

                    // Format the latest message
                    $latestMessage = htmlspecialchars($row['message']);
                    $latestMessage = $row['sender_id'] == $user_id ? "You: $latestMessage" : $latestMessage;

                    // Truncate the message to 20 characters
                    if (strlen($latestMessage) > 20) {
                        $latestMessage = substr($latestMessage, 0, 20) . "...";
                    }

                    // Format the time
                    $latestTime = $row['sent_at'] ? date("h:i A", strtotime($row['sent_at'])) : "";

                    echo "<li class='user-item' data-user='{$row['id']}'>
                        <span class='profile-pic' style='background-color: {$profileColor};'>{$nameletter[0]}</span>
                        <div class='user-info'>
                            <span class='user-name'>{$displayName}</span>
                            <span class='latest-message'>{$latestMessage}</span>
                        </div>
                        <span class='latest-time'>{$latestTime}</span>
                    </li>";
                }
                $conn->close();
                ?>
            </ul>
        </div>

        <!-- Right: Chat Area -->
        <div class="chat-area">
            <button id="backToUsers" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="chat-header">
                <span class="chat-profile-pic"></span>
                <span class="chat-username">Continue to Chat</span>
            </div>
            <div class="chat-content">
            </div>

            <!-- Message Input Box (Visible only when chat is active) -->
            <div class="chat-input-container">
                <button id="extraOptions" class="extra-options-btn">
                    <i class="fas fa-plus"></i>
                </button>
                <div id="extraOptionsPopup" class="extra-options-popup">
                    <button id="sendLocation" class="popup-option">Send Location</button>
                    <!-- Add more options here -->
                </div>
                <input type="text" id="messageInput" placeholder="Type a message..." class="message-input">
                <button id="sendMessage" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

    </main>
    <script src="script/script.js"></script>
    
</body>

</html>