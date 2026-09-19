<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../signup.html');
    exit;
}
if ($_SESSION['role'] === 'lecturer') {
    echo "
    <div style='font-family:Segoe UI,sans-serif; text-align:center; margin-top:80px;'>
        <h2 style='color:#1e3a8a;'>⚠️ Access Denied</h2>
        <p style='color:#4b5563;'>This page is for students only.</p>
        <a href='../lecturer/lecturer_dashboard.php' 
           style='display:inline-block; margin-top:15px; padding:10px 20px; 
                  background:#2563eb; color:white; border-radius:8px; text-decoration:none;'>
           Go to My Dashboard
        </a>
    </div>";
    exit;
}
if ($_SESSION['role'] !== 'student') {
    header('Location: ../signup.html');
    exit;
}

include '../config.php';

$student_id = $_SESSION['user_id'];

// Mark all as read the moment they open the full page
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE recipient_id = $student_id");

// Fetch all notifications for this student
$query = "SELECT n.notification_id, n.sender_id, n.title, n.message,
                 n.resource_id, n.is_read, n.created_at,
                 u.username AS sender_name
          FROM notifications n
          LEFT JOIN users u ON u.id = n.sender_id
          WHERE n.recipient_id = ?
          ORDER BY n.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Galorem AI - Notifications</title>
  <link rel="stylesheet" href="AI.css" />
  <link rel="stylesheet" href="sidebar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    .notif-page-wrapper {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .notif-page-header {
      margin-bottom: 1.5rem;
      margin-top: 6rem;
    }
    .notif-page-header h1 {
      font-size: 1.8rem;
      color: inherit;
      
    }
    .notif-page-header p {
      color: #94a3b8;
      margin-top: 4px;
    }
    .notif-card {
      background: rgba(25, 25, 55, 0.6);
      border: 1px solid rgba(94, 137, 251, 0.25);
      border-radius: 14px;
      padding: 1.2rem 1.4rem;
      margin-bottom: 1rem;
      transition: border-color 0.2s ease;
    }
    .notif-card.unread {
      border-color: rgba(94, 137, 251, 0.6);
      background: rgba(94, 137, 251, 0.08);
    }
    .notif-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 8px;
    }
    .notif-card-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #5e89fb;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .notif-unread-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #ef4444;
      display: inline-block;
    }
    .notif-card-time {
      font-size: 0.78rem;
      color: #64748b;
      white-space: nowrap;
    }
    .notif-card-message {
      color: #cbd5e1;
      line-height: 1.6;
      font-size: 0.95rem;
      white-space: pre-wrap;
    }
    .notif-card-sender {
      margin-top: 10px;
      font-size: 0.8rem;
      color: #94a3b8;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .notif-card-resource {
      margin-top: 10px;
      display: inline-block;
      font-size: 0.8rem;
      color: #5e89fb;
      text-decoration: none;
      border: 1px solid rgba(94, 137, 251, 0.4);
      padding: 5px 12px;
      border-radius: 8px;
      transition: background 0.2s;
    }
    .notif-card-resource:hover {
      background: rgba(94, 137, 251, 0.15);
    }
    .notif-empty-page {
      text-align: center;
      padding: 4rem 1rem;
      color: #64748b;
    }
    .notif-empty-page i {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: #3b4661;
    }

    /* Light mode */
    body.light-mode .notif-card {
      background: #f8fafc;
      border-color: #e2e8f0;
    }
    body.light-mode .notif-card.unread {
      border-color: #3b82f6;
      background: #eff6ff;
    }
    body.light-mode .notif-card-message {
      color: #334155;
    }
    body.light-mode .notif-card-sender,
    body.light-mode .notif-card-time {
      color: #64748b;
    }
  </style>
</head>
<body>
<header>
  <nav>
    <div class="menu"><img src="icons8-menu-100.png" /></div>
    <div class="logo"><img src="chatbot.png" /><div id="inner-text"><h1>Galorem Ai</h1><p>Smart Study Platform</p></div></div>
    <div class="toogle"><img src="cresent-moon.png" /></div>
  </nav>
</header>
<div class="overlay" id="overlay"></div>
<?php include 'sidebar.php'; ?>

<div class="notif-page-wrapper">
  <div class="notif-page-header">
    <h1><i class="fas fa-bell"></i> Notifications</h1>
    <p>Messages and feedback from your lecturers</p>
  </div>

  <?php if (mysqli_num_rows($result) === 0): ?>
    <div class="notif-empty-page">
      <i class="fas fa-bell-slash"></i>
      <p>No notifications yet. When a lecturer sends you feedback, it will appear here.</p>
    </div>
  <?php else: ?>
    <?php while ($n = mysqli_fetch_assoc($result)): ?>
      <div class="notif-card <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>">
        <div class="notif-card-top">
          <div class="notif-card-title">
            <?php if ($n['is_read'] == 0): ?><span class="notif-unread-dot"></span><?php endif; ?>
            <?php echo htmlspecialchars($n['title']); ?>
          </div>
          <div class="notif-card-time">
            <?php echo date('M j, Y · g:i A', strtotime($n['created_at'])); ?>
          </div>
        </div>
        <div class="notif-card-message"><?php echo htmlspecialchars($n['message']); ?></div>
        <div class="notif-card-sender">
          <i class="fas fa-chalkboard-user"></i>
          From <?php echo htmlspecialchars($n['sender_name'] ?? 'A lecturer'); ?>
        </div>
        <?php if (!empty($n['resource_id'])): ?>
          <a href="quiz.php?resource_id=<?php echo (int)$n['resource_id']; ?>" class="notif-card-resource">
            <i class="fas fa-link"></i> View related resource
          </a>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<script>
  // Sidebar toggle
  const menuIcon = document.querySelector('.menu img');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const closeBtn = document.getElementById('closeBtn');
  if (menuIcon && sidebar && overlay && closeBtn) {
    function openSidebar() { sidebar.style.left = '0'; overlay.style.display = 'block'; }
    function closeSidebar() { sidebar.style.left = '-260px'; overlay.style.display = 'none'; }
    menuIcon.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
  }

  // Dark/Light mode
  const toggleBtn = document.querySelector('.toogle img');
  const body = document.body;
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      body.classList.toggle('light-mode');
      toggleBtn.src = body.classList.contains('light-mode') ? 'brightness.png' : 'cresent-moon.png';
    });
  }
</script>
</body>
</html>