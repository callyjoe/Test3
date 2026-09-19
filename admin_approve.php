<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: signup.html');
    exit;
}
include 'config.php';

// Approve request
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM lecturer_requests WHERE id=$id"));
    if ($req) {
        $insert = "INSERT INTO users (username, email, password, role, school, department, lecturer_id) 
                   VALUES ('{$req['fullname']}', '{$req['email']}', '{$req['password_hash']}', 'lecturer', '{$req['school']}', '{$req['department']}', '{$req['lecturer_id']}')";
        mysqli_query($conn, $insert);
        mysqli_query($conn, "UPDATE lecturer_requests SET status='approved' WHERE id=$id");
    }
    header('Location: admin_approve.php');
    exit;
}
// Reject request
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($conn, "UPDATE lecturer_requests SET status='rejected' WHERE id=$id");
    header('Location: admin_approve.php');
    exit;
}

$pending = mysqli_query($conn, "SELECT * FROM lecturer_requests WHERE status='pending'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Approve Lecturers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            background: #f5f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 30px;
        }
        h1 { color: #1e3a8a; margin-bottom: 5px; }
        .subtitle { color: #4b5563; margin-bottom: 25px; border-left: 3px solid #3b82f6; padding-left: 12px; }
        .request-card {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            margin: 20px 0;
            padding: 18px;
            border-radius: 14px;
        }
        .request-card p { margin: 7px 0; }
        .btn-approve, .btn-reject {
            display: inline-block;
            margin-right: 20px;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 30px;
        }
        .btn-approve { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .btn-reject:hover { background: #fecaca; }
        .no-requests {
            background: #fef9c3;
            padding: 20px;
            border-radius: 14px;
            color: #854d0e;
            text-align: center;
        }
        hr { margin: 30px 0 20px; }
        .logout-link { text-align: right; margin-bottom: 15px; }
        .logout-link a { color: #64748b; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="logout-link">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
    <div class="subtitle">Lecturer access requests – approve or reject</div>

    <?php if (mysqli_num_rows($pending) == 0): ?>
        <div class="no-requests"><i class="fas fa-check-circle"></i> No pending requests.</div>
    <?php else: ?>
        <?php while ($r = mysqli_fetch_assoc($pending)): ?>
            <div class="request-card">
                <p><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($r['fullname']); ?></strong> (<?php echo htmlspecialchars($r['email']); ?>)</p>
                <p><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($r['lecturer_id']); ?></p>
                <p><i class="fas fa-university"></i> School: <?php echo htmlspecialchars($r['school']); ?></p>
                <p><i class="fas fa-building"></i> Department: <?php echo htmlspecialchars($r['department']); ?></p>
                <div style="margin-top: 15px;">
                    <a href="?approve=<?php echo $r['id']; ?>" class="btn-approve"><i class="fas fa-check-circle"></i> Approve</a>
                    <a href="?reject=<?php echo $r['id']; ?>" class="btn-reject"><i class="fas fa-times-circle"></i> Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
    <hr>
    <p><i class="fas fa-info-circle"></i> Approved lecturers will be able to log in and manage resources.</p>
</div>
</body>
</html>