<!-- sidebar.php -->
<div class="sidebar" id="sidebar">

    <div class="close-btn" id="closeBtn">&times;</div>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
    <!-- ===================== STANDALONE NOTIFICATION BELL ICON ===================== -->
    <div class="notif-icon-wrapper" id="notifContainer">
        <div class="notif-icon-btn">
            <i class="fas fa-bell"></i>
            <span class="notif-badge" id="notifBadge">0</span>
        </div>

        <div class="notif-panel" id="notifPanel">
            <div class="notif-panel-header">
                <span>Notifications</span>
                <span class="mark-all" id="markAllRead">Mark all read</span>
            </div>
            <div id="notifList">
                <div class="notif-empty">Loading...</div>
            </div>
            <a href="notifications.php" class="notif-view-all">View all notifications</a>
        </div>
    </div>
    <?php endif; ?>

    <ul class="sidebar-nav">
        <li><a href="AI.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="AI.php"><i class="fas fa-comment-dots"></i> Chat</a></li>
        <li><a href="quiz.php"><i class="fas fa-question-circle"></i> Quiz</a></li>
        <!-- <li><a href="flashcards.php"><i class="fas fa-layer-group"></i> Flashcards</a></li> -->
        <li><a href="document.php"><i class="fas fa-file-upload"></i> Document Upload</a></li>
        <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
    </ul>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="profile-container">
        <div class="profile-trigger" id="profileTrigger">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
            <div class="profile-arrow"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="help_feedback.php"><i class="fas fa-question-circle"></i> Help & Feedback</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if (isset($_SESSION['school'])):
        $school_name = $_SESSION['school'];
        $logo_file = '';
        if (strpos($school_name, 'UENR') !== false || strpos($school_name, 'Energy') !== false) {
            $logo_file = 'UENR-LOGO.jpg';
        } elseif (strpos($school_name, 'KNUST') !== false) {
            $logo_file = 'knust.png';
        } elseif (strpos($school_name, 'University of Ghana') !== false) {
            $logo_file = 'ug.png';
        } elseif (strpos($school_name, 'UCC') !== false) {
            $logo_file = 'ucc.png';
        } else {
            $logo_file = 'general.png';
        }
        $logo_url = '/Test3/logos/' . $logo_file;
    ?>
    <div class="school-info">
        <img src="<?php echo $logo_url; ?>" alt="School Logo" class="school-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
        <i class="fas fa-university" style="display:none;"></i>
        <span class="school-name"><?php echo htmlspecialchars($school_name); ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    // ========== PROFILE DROPDOWN ==========
    const profileTrigger = document.getElementById('profileTrigger');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (profileTrigger && dropdownMenu) {
        profileTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            const arrow = profileTrigger.querySelector('.profile-arrow i');
            if (dropdownMenu.classList.contains('show')) {
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            } else {
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });

        document.addEventListener('click', function (e) {
            if (!profileTrigger.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                const arrow = profileTrigger.querySelector('.profile-arrow i');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });
    }

    // ========== NOTIFICATION BELL ==========
    const notifContainer = document.getElementById('notifContainer');
    const notifPanel      = document.getElementById('notifPanel');
    const notifBadge      = document.getElementById('notifBadge');
    const notifList       = document.getElementById('notifList');
    const markAllRead     = document.getElementById('markAllRead');

    if (notifContainer) {

        function timeAgo(dateStr) {
            const date = new Date(dateStr.replace(' ', 'T'));
            const seconds = Math.floor((new Date() - date) / 1000);
            if (seconds < 60) return 'Just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `${minutes}m ago`;
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours}h ago`;
            const days = Math.floor(hours / 24);
            return `${days}d ago`;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
        }

        async function loadNotifications() {
            try {
                const res = await fetch('get_notifications.php');
                const data = await res.json();

                if (data.unread_count > 0) {
                    notifBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    notifBadge.classList.add('show');
                } else {
                    notifBadge.classList.remove('show');
                }

                if (!data.notifications || data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                    return;
                }

                notifList.innerHTML = data.notifications.map(n => {
                    const preview = n.message.length > 80 ? n.message.slice(0, 80) + '…' : n.message;
                    return `
                    <a href="notifications.php" class="notif-item ${n.is_read === '0' || n.is_read === 0 ? 'unread' : ''}">
                        <div class="notif-item-title">
                            <i class="fas fa-user"></i> ${escapeHtml(n.title)}
                        </div>
                        <div class="notif-item-message">${escapeHtml(preview)}</div>
                        <div class="notif-item-meta">
                            ${n.sender_name ? 'From ' + escapeHtml(n.sender_name) + ' &middot; ' : ''}${timeAgo(n.created_at)}
                        </div>
                    </a>
                `;
                }).join('');

            } catch (err) {
                console.error('Failed to load notifications:', err);
                notifList.innerHTML = '<div class="notif-empty">Could not load notifications.</div>';
            }
        }

        // Toggle panel
        notifContainer.addEventListener('click', function (e) {
            e.stopPropagation();
            notifPanel.classList.toggle('show');
            if (notifPanel.classList.contains('show')) {
                loadNotifications();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!notifContainer.contains(e.target)) {
                notifPanel.classList.remove('show');
            }
        });

        // Mark all as read
        if (markAllRead) {
            markAllRead.addEventListener('click', async function (e) {
                e.stopPropagation();
                try {
                    await fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ mark_all: true })
                    });
                    loadNotifications();
                } catch (err) {
                    console.error('Failed to mark all read:', err);
                }
            });
        }

        // Initial badge load (without opening panel)
        loadNotifications();

        // Poll every 60 seconds for new notifications
        setInterval(loadNotifications, 60000);
    }
</script>