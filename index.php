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
    <link rel="stylesheet" href="css/chat.css">
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
        </div>
        </div>
    </header>

    <main class="chat-container">
        <!-- Left: User List -->
        <div class="user-list">
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

    <!-- Rearranged scripts -->
    <script>
        // Script for handling user list and chat area toggle
        document.addEventListener("DOMContentLoaded", function() {
            const userList = document.querySelector(".user-list");
            const chatArea = document.querySelector(".chat-area");
            const backButton = document.getElementById("backToUsers");
            const userItems = document.querySelectorAll(".user-item");

            userItems.forEach(item => {
                item.addEventListener("click", () => {
                    document.querySelector(".chat-container").classList.add("show-chat");
                    document.querySelector(".chat-container").classList.remove("show-users");

                    // Set user details in chat header
                    const name = item.querySelector(".user-name").textContent;
                    const pic = item.querySelector(".profile-pic").cloneNode(true);

                    document.querySelector(".chat-header .chat-username").textContent = name;
                    document.querySelector(".chat-header .chat-profile-pic").replaceWith(pic);
                });
            });

            backButton.addEventListener("click", () => {
                document.querySelector(".chat-container").classList.remove("show-chat");
                document.querySelector(".chat-container").classList.add("show-users");
            });
        });
    </script>

    <script>
        // Script for search functionality
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("searchUsers");
            const userItems = document.querySelectorAll(".user-item");

            searchInput.addEventListener("keyup", function() {
                const query = this.value.toLowerCase();

                userItems.forEach(item => {
                    const name = item.querySelector(".user-name").textContent.toLowerCase();
                    item.style.display = name.includes(query) ? "flex" : "none";
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const chatContent = document.querySelector(".chat-content");
            const messageInput = document.getElementById("messageInput");
            const sendMessageButton = document.getElementById("sendMessage");
            const chatHeader = document.querySelector(".chat-header");
            let currentReceiverId = null;

            function formatDate(dateString) {
                if (!dateString) return ""; // Handle empty or invalid date strings
                const date = new Date(dateString); // Parse the date string
                if (isNaN(date.getTime())) return ""; // Handle invalid date objects

                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(today.getDate() - 1);

                if (date.toDateString() === today.toDateString()) {
                    return "Today";
                } else if (date.toDateString() === yesterday.toDateString()) {
                    return "Yesterday";
                } else {
                    const options = {
                        day: "2-digit",
                        month: "short",
                        year: "2-digit"
                    };
                    return date.toLocaleDateString("en-GB", options).replace(/ /g, "-");
                }
            }

            function fetchMessages(receiverId) {
                const previousMessageCount = chatContent.childElementCount; // Count current messages

                fetch(`logic/fetch_messages.php?receiver_id=${receiverId}`)
                    .then(response => response.json())
                    .then(data => {
                        const messages = data.messages;

                        // Sort messages by sent_on (date) and sent_at (time)
                        messages.sort((a, b) => {
                            return new Date(a.sent_on + " " + a.sent_at) - new Date(b.sent_on + " " + b.sent_at);
                        });

                        let lastDate = null;
                        const newContent = document.createDocumentFragment();

                        messages.forEach(msg => {
                            const messageDate = msg.sent_on;
                            if (messageDate !== lastDate) {
                                // Format the date
                                const formattedDate = formatDate(messageDate);
                                if (formattedDate) {
                                    const dateHeader = document.createElement("div");
                                    dateHeader.classList.add("date-header");
                                    dateHeader.textContent = formattedDate;
                                    dateHeader.style.textAlign = "center"; // Align in center
                                    newContent.appendChild(dateHeader);
                                }
                                lastDate = messageDate;
                            }

                            // Add message
                            const messageElement = document.createElement("div");
                            messageElement.classList.add("message", msg.is_sender ? "sent" : "received");
                            messageElement.innerHTML = `
                    <p>${msg.message}</p>
                    <span class="timestamp">${msg.sent_at}</span>
                `;
                            newContent.appendChild(messageElement);
                        });

                        // Replace chat content with new messages
                        chatContent.innerHTML = "";
                        chatContent.appendChild(newContent);

                        // Scroll to the bottom if the number of messages increases
                        const currentMessageCount = chatContent.childElementCount;
                        if (currentMessageCount > previousMessageCount) {
                            chatContent.scrollTop = chatContent.scrollHeight;
                        }
                    })
                    .catch(error => console.error("Error fetching messages:", error));
            }

            // Function to format the date (Today, Yesterday, or exact date)
            function formatDate(dateString) {
                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);

                const messageDate = new Date(dateString);

                if (messageDate.toDateString() === today.toDateString()) {
                    return "Today";
                } else if (messageDate.toDateString() === yesterday.toDateString()) {
                    return "Yesterday";
                } else {
                    return messageDate.toLocaleDateString(); // Show full date if not today/yesterday
                }
            }


            function sendMessage() {
                const message = messageInput.value.trim();
                if (message !== "" && currentReceiverId) {
                    fetch("logic/send_message.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                receiver_id: currentReceiverId,
                                message
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messageInput.value = "";
                                fetchMessages(currentReceiverId);
                            } else {
                                console.error(data.error || "Failed to send message.");
                            }
                        });
                }
            }

            function openChat(userItem) {
                currentReceiverId = userItem.dataset.user;
                localStorage.setItem("lastOpenedChat", currentReceiverId); // Save to localStorage
                const name = userItem.querySelector(".user-name").textContent;
                const pic = userItem.querySelector(".profile-pic").cloneNode(true);

                chatHeader.innerHTML = "";
                chatHeader.appendChild(pic);
                const nameSpan = document.createElement("span");
                nameSpan.classList.add("chat-username");
                nameSpan.textContent = name;
                chatHeader.appendChild(nameSpan);

                fetchMessages(currentReceiverId);
            }

            sendMessageButton.addEventListener("click", sendMessage);
            messageInput.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    sendMessage();
                }
            });

            document.querySelectorAll(".user-item").forEach(item => {
                item.addEventListener("click", () => {
                    openChat(item);
                });
            });

            // Open the last opened chat on page load
            const lastOpenedChat = localStorage.getItem("lastOpenedChat");
            if (lastOpenedChat) {
                const lastChatItem = document.querySelector(`.user-item[data-user='${lastOpenedChat}']`);
                if (lastChatItem) {
                    openChat(lastChatItem);
                }
            } else {
                const firstUser = document.querySelector(".user-item");
                if (firstUser) {
                    openChat(firstUser);
                }
            }

            // Real-time message fetching
            setInterval(() => {
                if (currentReceiverId) {
                    fetchMessages(currentReceiverId);
                }
            }, 3000); // Fetch messages every 3 seconds
        });
    </script>

    <script>
        // Theme toggle functionality
        document.addEventListener("DOMContentLoaded", function() {
            const themeToggleCheckbox = document.getElementById("themeToggleCheckbox");
            const body = document.body;

            // Load saved theme from localStorage
            const savedTheme = localStorage.getItem("theme") || "light-theme";
            body.classList.add(savedTheme);
            themeToggleCheckbox.checked = savedTheme === "dark-theme";

            themeToggleCheckbox.addEventListener("change", function() {
                const newTheme = themeToggleCheckbox.checked ? "dark-theme" : "light-theme";
                body.classList.remove("dark-theme", "light-theme");
                body.classList.add(newTheme);
                localStorage.setItem("theme", newTheme);
            });

            document.getElementById("visitProfile").addEventListener("click", function() {
                window.location.href = "profile.php"; // Adjust the URL as needed
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const extraOptionsBtn = document.getElementById("extraOptions");
            const extraOptionsPopup = document.getElementById("extraOptionsPopup");
            const sendLocationBtn = document.getElementById("sendLocation");

            // Toggle popup visibility
            extraOptionsBtn.addEventListener("click", function() {
                extraOptionsPopup.style.display =
                    extraOptionsPopup.style.display === "block" ? "none" : "block";
            });

            // Close popup when clicking outside
            document.addEventListener("click", function(event) {
                if (!extraOptionsBtn.contains(event.target) && !extraOptionsPopup.contains(event.target)) {
                    extraOptionsPopup.style.display = "none";
                }
            });

            // Send geolocation with address
            sendLocationBtn.addEventListener("click", function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;

                            // Use a reverse geocoding API to fetch the address
                            const geocodingApiUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`;

                            fetch(geocodingApiUrl)
                                .then(response => response.json())
                                .then(data => {
                                    const address = data.display_name || "Address not available";
                                    const locationMessage = `My location: ${address} (https://www.google.com/maps?q=${latitude},${longitude})`;
                                    document.getElementById("messageInput").value = locationMessage;
                                })
                                .catch(error => {
                                    console.error("Error fetching address:", error);
                                    alert("Unable to fetch address. Please try again.");
                                });
                        },
                        function(error) {
                            alert("Unable to fetch location. Please try again.");
                        }
                    );
                } else {
                    alert("Geolocation is not supported by your browser.");
                }
            });
        });
    </script>
</body>

</html>