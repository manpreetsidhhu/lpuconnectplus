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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <header class="header-container">
        <div class="header-left">
            <img src="https://ums.lpu.in/lpuums/assets/login/img/logos/seal.svg" alt="LPU Logo">
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
            <div class="profile-placeholder"><?php echo $initials; ?></div>
            <a href="logic/logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <main class="chat-container">
        <!-- Left: User List -->
        <div class="user-list">
            <input type="text" id="searchUsers" placeholder="Search users or messages...">
            <ul id="userList">
                <?php
                include 'logic/db_connect.php';
                $query = "SELECT id, username, fullname FROM users";
                $result = $conn->query($query);

                while ($row = $result->fetch_assoc()) {
                    $name = htmlspecialchars($row['fullname']);
                    if (empty($name)) {
                        $name = htmlspecialchars($row['username']);
                    }
                    // Generate initials for the user
                    $isYou = ($row['id'] == $_SESSION['user_id']);
                    $displayName = $isYou ? "$name (You)" : $name;
                    $profileColor = $isYou ? '#333' : '#f47d1c';
                    $nameletter = strtoupper($name);

                    echo "<li class='user-item' data-user='{$row['id']}'>
                        <span class='profile-pic' style='background-color: {$profileColor};'>{$nameletter[0]}</span>
                        <span class='user-name'>{$displayName}</span>
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
                <label for="media-upload" class="media-btn">
                    <i class="fas fa-paperclip"></i>
                </label>
                <input type="file" id="media-upload" class="media-input" style="display: none;">
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
                        let messages = data.messages;

                        // Ensure correct sorting: Date DESC, Time ASC
                        messages.sort((a, b) => {
                            const dateComparison = new Date(b.sent_on) - new Date(a.sent_on); // Reverse order for date
                            if (dateComparison !== 0) {
                                return dateComparison; // Latest date at the bottom
                            }
                            return new Date(`1970-01-01T${a.sent_at}`) - new Date(`1970-01-01T${b.sent_at}`); // Time ASC
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

    <style>
        /* Add styles for the date header */
        .date-header {
            text-align: center;
            font-weight: bold;
            margin: 10px 0;
            color: #555;
        }

        /* Add styles for responsiveness */
        .header-title .short-title {
            display: none;
        }

        @media (max-width: 468px) {
            .header-title .full-title {
                display: none;
            }

            .header-title .short-title {
                display: inline;
            }
        }
    </style>
</body>

</html>