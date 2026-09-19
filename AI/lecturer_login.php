<?php
session_start();

// If already logged in as lecturer, go to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'lecturer') {
    header('Location: lecturer_dashboard.php');
    exit;
}
// If logged in as student or admin, destroy session to avoid confusion
if (isset($_SESSION['user_id'])) {
    session_destroy();
    session_start(); // fresh start for login page
}

include '../config.php'; // adjust if config.php is one level up

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email' AND role='lecturer'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['school'] = $user['school'];
            $_SESSION['department'] = $user['department'];
            header('Location: lecturer_dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            background: #f5f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 { color: #1e3a8a; margin-bottom: 8px; }
        .subtitle {
            color: #4b5563;
            margin-bottom: 24px;
            font-size: 0.9rem;
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
        }
        .form-group {
            margin-bottom: 18px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #1e40af;
            font-size: 0.85rem;
        }
        label i { margin-right: 6px; }
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
        }
        button i { margin-right: 8px; }
        button:hover { background: #1d4ed8; }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .forgot { text-align: right; margin-top: 8px; }
        .forgot a { color: #3b82f6; font-size: 0.8rem; text-decoration: none; }
        hr { margin: 20px 0; }
        .request-link { text-align: center; }
        .request-link a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-chalkboard-user"></i> Lecturer Login</h2>
        <div class="subtitle">Access your dashboard</div>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            <div class="forgot">
                <a href="forgot_password.php"><i class="fas fa-question-circle"></i> Forgot password?</a>
            </div>
            <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
        <hr>
        <div class="request-link">
            <i class="fas fa-user-plus"></i> Don't have an account? <a href="lecturer_request.php">Request Access</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>