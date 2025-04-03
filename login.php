<!-- Login Page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login/LPU Connect +</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="icon" href="https://ums.lpu.in/lpuums/assets/login/img/favicon.ico" />
    <link rel="stylesheet" href="css/login.css" />
</head>

<body>
    <div class="header-container">
        <div class="header">
            <img src="https://ums.lpu.in/lpuums/assets/login/img/logos/seal.svg" alt="LPU Logo" />
            <h1>LPU<br />Connect+</h1>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
    <br>
    <form class="form" action="logic/login.php" method="post">
        <label for="username">Username</label>
        <i class="fa fa-user"></i>
        <input type="text" name="username" placeholder="Username" required />

        <label for="password">Password</label>
        <i class="fa fa-eye"></i>
        <input type="password" name="password" placeholder="Password" required />

        <p style="color:red;font-size:15px;" class="error">
            <?php
            if (isset($_GET['error'])) {
                if ($_GET['error'] === 'emptyfields') {
                    echo 'Please fill in all fields!';
                } else if ($_GET['error'] === 'invalidpassword') {
                    echo 'Invalid password!';
                } else if ($_GET['error'] === 'usernotfound') {
                    echo 'User not found!';
                }
            }
            ?>
        </p>

        <button type="submit">Login</button>
        <br>
        <p>Don't have an account? <a href='signup.php'>Sign Up</a></p>
    </form>
    <br>
    <footer>
        <p>&copy;2025 LPU Connect+</p>
    </footer>

    <script>
        // Password Toggle
        const password = document.querySelector('input[type="password"]');
        const eye = document.querySelector('.fa-eye');
        eye.addEventListener('click', () => {
            if (password.type === 'password') {
                password.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        });

        // Theme Toggle
        const themeToggle = document.getElementById("theme-toggle");
        const body = document.body;

        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            // Change icon
            const icon = themeToggle.querySelector("i");
            if (body.classList.contains("dark-mode")) {
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
            } else {
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
            }
            // Save preference
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });
        // Load saved theme
        if (localStorage.getItem("theme") === "dark") {
            body.classList.add("dark-mode");
            themeToggle.querySelector("i").classList.remove("fa-moon");
            themeToggle.querySelector("i").classList.add("fa-sun");
        }
    </script>
</body>

</html>