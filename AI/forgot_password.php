<?php
// Simple placeholder – no email sending yet
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f5f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 400px;
            text-align: center;
        }
        h2 { color: #1e3a8a; }
        p { margin: 20px 0; color: #4b5563; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-key"></i> Password Reset</h2>
        <p><i class="fas fa-envelope"></i> If you have an approved lecturer account, contact the admin to reset your password.</p>
        <p><a href="lecturer_login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
    </div>
</body>
</html>