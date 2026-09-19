<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../signup.html');
    exit;
}
include '../config.php';

$lecturer_id = $_SESSION['user_id'];
$resource_id = (int)$_GET['resource_id'];

// Verify this resource belongs to the logged-in lecturer
$check = mysqli_query($conn, "SELECT * FROM learning_resources WHERE resource_id=$resource_id AND uploaded_by=$lecturer_id");
if (mysqli_num_rows($check) == 0) {
    die("Unauthorized access.");
}
$resourceInfo = mysqli_fetch_assoc($check);

// Fetch all attempts for this resource
$attempts = mysqli_query($conn, "SELECT pr.record_id, pr.student_id, pr.quiz_title, pr.score, pr.taken_at, pr.answers_json 
                                  FROM performance_records pr 
                                  WHERE pr.resource_id = $resource_id 
                                  ORDER BY pr.taken_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance – Resource #<?php echo $resource_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body { background: #f5f7fc; font-family: 'Segoe UI', sans-serif; padding: 30px; }
        .container { max-width: 1100px; margin: 0 auto; background: white; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        h1 { color: #1e3a8a; }
        .resource-meta { color: #4b5563; margin: 6px 0 20px; }
        .top-actions { margin-bottom: 20px; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #565e64; }

        .attempt-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .attempt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: #f8fafc;
            cursor: pointer;
        }
        .attempt-header:hover { background: #f1f5f9; }
        .attempt-student { font-weight: 600; color: #1e293b; }
        .attempt-meta { font-size: 0.85rem; color: #64748b; margin-top: 2px; }
        .score-badge {
            font-weight: bold;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .score-high { background: #dcfce7; color: #166534; }
        .score-mid  { background: #fef9c3; color: #854d0e; }
        .score-low  { background: #fee2e2; color: #b91c1c; }

        .attempt-actions { display: flex; align-items: center; gap: 10px; }

        .attempt-body {
            display: none;
            padding: 18px;
            border-top: 1px solid #e2e8f0;
        }
        .attempt-body.show { display: block; }

        .answer-block {
            margin-bottom: 14px;
            padding-bottom: 14px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .answer-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .answer-question { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .answer-text { color: #334155; background: #f8fafc; padding: 8px 12px; border-radius: 8px; font-size: 0.92rem; }
        .no-answer { color: #94a3b8; font-style: italic; }

        .empty-state { padding: 30px; text-align: center; color: #64748b; }

        /* Notification modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white;
            border-radius: 14px;
            padding: 24px;
            width: 90%;
            max-width: 450px;
        }
        .modal-box h3 { color: #1e3a8a; margin-bottom: 12px; }
        .modal-box label { display: block; font-weight: 600; color: #1e40af; margin-bottom: 5px; font-size: 0.88rem; }
        .modal-box input, .modal-box textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 0.92rem;
        }
        .modal-box textarea { resize: vertical; min-height: 90px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .modal-status { font-size: 0.85rem; margin-top: 8px; }
        .modal-status.success { color: #166534; }
        .modal-status.error { color: #b91c1c; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-chart-line"></i> Student Performance</h1>
    <p class="resource-meta">
        Resource: <strong><?php echo htmlspecialchars($resourceInfo['title']); ?></strong> (ID: <?php echo $resource_id; ?>)
    </p>

    <div class="top-actions">
        <a href="lecturer_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if (mysqli_num_rows($attempts) == 0): ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:10px; display:block;"></i>
            No attempts yet for this resource.
        </div>
    <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($attempts)): ?>
            <?php
                $scoreClass = ($row['score'] >= 70) ? 'score-high' : (($row['score'] >= 40) ? 'score-mid' : 'score-low');
                $answers = json_decode($row['answers_json'], true);
            ?>
            <div class="attempt-card">
                <div class="attempt-header" onclick="toggleAttempt(<?php echo $row['record_id']; ?>)">
                    <div>
                        <div class="attempt-student">Student #<?php echo (int)$row['student_id']; ?></div>
                        <div class="attempt-meta"><?php echo htmlspecialchars($row['quiz_title']); ?> &middot; <?php echo htmlspecialchars($row['taken_at']); ?></div>
                    </div>
                    <div class="attempt-actions">
                        <span class="score-badge <?php echo $scoreClass; ?>"><?php echo (int)$row['score']; ?>%</span>
                        <button class="btn" onclick="event.stopPropagation(); openNotifModal(<?php echo (int)$row['student_id']; ?>, <?php echo $resource_id; ?>)">
                            <i class="fas fa-paper-plane"></i> Notify
                        </button>
                        <i class="fas fa-chevron-down" id="chevron-<?php echo $row['record_id']; ?>"></i>
                    </div>
                </div>
                <div class="attempt-body" id="body-<?php echo $row['record_id']; ?>">
                    <?php if (!$answers || (empty($answers['objectives']) && empty($answers['theory']))): ?>
                        <p class="no-answer">No detailed answers recorded for this attempt.</p>
                    <?php else: ?>
                        <?php if (!empty($answers['objectives'])): ?>
                            <h4 style="margin-bottom:10px; color:#1e3a8a;">Objective Questions</h4>
                            <?php foreach ($answers['objectives'] as $obj): ?>
                                <div class="answer-block">
                                    <div class="answer-question"><?php echo htmlspecialchars($obj['question'] ?? 'Question'); ?></div>
                                    <div class="answer-text">
                                        <?php echo htmlspecialchars($obj['answer'] ?? 'Not answered'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($answers['theory'])): ?>
                            <h4 style="margin:14px 0 10px; color:#1e3a8a;">Theory Questions</h4>
                            <?php foreach ($answers['theory'] as $th): ?>
                                <div class="answer-block">
                                    <div class="answer-question"><?php echo htmlspecialchars($th['question'] ?? 'Question'); ?></div>
                                    <div class="answer-text">
                                        <?php echo nl2br(htmlspecialchars($th['answer'] ?? 'Not answered')); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- ===================== NOTIFICATION MODAL ===================== -->
<div class="modal-overlay" id="notifModal">
    <div class="modal-box">
        <h3><i class="fas fa-paper-plane"></i> Send Notification</h3>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:14px;" id="modalRecipientLabel"></p>

        <label>Title</label>
        <input type="text" id="notifTitle" placeholder="e.g., Feedback on your quiz answers" maxlength="150">

        <label>Message</label>
        <textarea id="notifMessage" placeholder="Write your feedback or message to the student..."></textarea>

        <div class="modal-status" id="modalStatus"></div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeNotifModal()">Cancel</button>
            <button class="btn" id="sendNotifBtn" onclick="sendNotification()"><i class="fas fa-paper-plane"></i> Send</button>
        </div>
    </div>
</div>

<script>
    function toggleAttempt(recordId) {
        const body = document.getElementById('body-' + recordId);
        const chevron = document.getElementById('chevron-' + recordId);
        body.classList.toggle('show');
        chevron.style.transform = body.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    let currentStudentId = null;
    let currentResourceId = null;

    function openNotifModal(studentId, resourceId) {
        currentStudentId = studentId;
        currentResourceId = resourceId;
        document.getElementById('modalRecipientLabel').textContent = `Sending to Student #${studentId}`;
        document.getElementById('notifTitle').value = '';
        document.getElementById('notifMessage').value = '';
        document.getElementById('modalStatus').textContent = '';
        document.getElementById('modalStatus').className = 'modal-status';
        document.getElementById('notifModal').classList.add('show');
    }

    function closeNotifModal() {
        document.getElementById('notifModal').classList.remove('show');
    }

    async function sendNotification() {
        const title = document.getElementById('notifTitle').value.trim();
        const message = document.getElementById('notifMessage').value.trim();
        const statusEl = document.getElementById('modalStatus');
        const sendBtn = document.getElementById('sendNotifBtn');

        if (!title || !message) {
            statusEl.textContent = 'Please fill in both title and message.';
            statusEl.className = 'modal-status error';
            return;
        }

        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';

        try {
            const res = await fetch('send_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `student_id=${currentStudentId}&resource_id=${currentResourceId}&title=${encodeURIComponent(title)}&message=${encodeURIComponent(message)}`
            });
            const data = await res.json();

            if (data.status === 'success') {
                statusEl.textContent = 'Notification sent successfully!';
                statusEl.className = 'modal-status success';
                setTimeout(closeNotifModal, 1200);
            } else {
                statusEl.textContent = data.message || 'Failed to send notification.';
                statusEl.className = 'modal-status error';
            }
        } catch (err) {
            statusEl.textContent = 'Error: ' + err.message;
            statusEl.className = 'modal-status error';
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
        }
    }

    // Close modal when clicking outside the box
    document.getElementById('notifModal').addEventListener('click', function(e) {
        if (e.target === this) closeNotifModal();
    });
</script>
</body>
</html>