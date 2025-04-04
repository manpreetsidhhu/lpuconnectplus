<!-- Signup Page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Signup/LPU Connect +</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="icon" href="media/lpuicon.svg"/>
    <link rel="stylesheet" href="css/login.css" />
</head>

<body>
    <div class="header-container">
        <div class="header">
            <img src="media/lpuicon.svg" alt="LPU Logo" />
            <h1>LPU<br />Connect+</h1>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>

    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] === 'emailtaken') {
            echo '<p style="color:red;font-size:20px;" class="error">Email already in use!</p>';
        } else if ($_GET['error'] === 'passwordmismatch') {
            echo '<p style="color:red;font-size:20px;" class="error">Passwords do not match!</p>';
        }
    }
    ?>
    <br>
    <form class="form" action="logic/signup.php" method="post">
        <p>Already have an account? <a href='login.php'>Login</a></p>
        <br><br>
        <label for="name">Full Name</label>
        <i class="fa fa-user"></i>
        <input type="text" name="name" placeholder="Full Name" required />

        <label for="email">Email</label>
        <i class="fa fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />

        <label for="username">Username</label>
        <i class="fa fa-user"></i>
        <input type="text" name="username" placeholder="Username" required />

        <label for="password">Password</label>
        <i class="fa fa-eye"></i>
        <input type="password" name="password" placeholder="Password" required />

        <label for="confirm-password">Confirm Password</label>
        <i class="fa fa-eye"></i>
        <input type="password" name="confirm-password" placeholder="Confirm Password" required />

        <button type="submit">Sign Up</button>
    </form>
    <br>
    <footer>
        <p>&copy;2025 LPU Connect+</p>
    </footer>

    <script>
        // Password Toggle
        const passwordFields = document.querySelectorAll('input[type="password"]');
        const eyeIcons = document.querySelectorAll('.fa-eye');

        eyeIcons.forEach((eye, index) => {
            eye.addEventListener('click', () => {
                if (passwordFields[index].type === 'password') {
                    passwordFields[index].type = 'text';
                    eye.classList.remove('fa-eye');
                    eye.classList.add('fa-eye-slash');
                } else {
                    passwordFields[index].type = 'password';
                    eye.classList.remove('fa-eye-slash');
                    eye.classList.add('fa-eye');
                }
            });
        });

        // Theme Toggle
        const themeToggle = document.getElementById("theme-toggle");
        const body = document.body;

        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            const icon = themeToggle.querySelector("i");
            if (body.classList.contains("dark-mode")) {
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
            } else {
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
            }
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });

        if (localStorage.getItem("theme") === "dark") {
            body.classList.add("dark-mode");
            themeToggle.querySelector("i").classList.remove("fa-moon");
            themeToggle.querySelector("i").classList.add("fa-sun");
        }
    </script>
</body>

</html>