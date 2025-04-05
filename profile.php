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
$query = "SELECT username, fullname, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - LPU Connect+</title>
    <link rel="icon" href="media/lpuicon.svg">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/profile.css">
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
            <div class="profile-placeholder">
                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
            </div>
            <div class="options-menu">
                <button class="options-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="options-dropdown">
                    <button class="popup-option" onclick="window.location.href='index.php'">Visit Dashboard</button>
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

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="javascript:history.back()" class="back-button">&larr; Back</a>
    </div>

    <main class="profile-container">
        <h2>Profile Settings</h2>
        <form id="profileForm" method="POST" action="logic/update_profile.php">
            <label for="fullname">Full Name:</label>
            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label for="password">Confirm Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <button type="submit" class="save-btn">Save Changes</button>
        </form>

        <h3>Delete Account</h3>
        <form id="deleteAccountForm" method="POST" action="logic/delete_account.php">
            <label for="deletePassword">Confirm Password:</label>
            <input type="password" id="deletePassword" name="password" placeholder="Enter your password" required>

            <button type="submit" class="delete-btn">Delete Account</button>
        </form>
    </main>

    <script>
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
        });
    </script>
</body>

</html>