<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.html');
    exit;
}
include '../config.php';

$user_id = $_SESSION['user_id'];

// Fetch summary statistics
$summary_query = "SELECT 
                    COUNT(*) as total_quizzes,
                    AVG(score) as avg_score,
                    MAX(score) as highest_score,
                    MIN(score) as lowest_score
                  FROM performance_records 
                  WHERE student_id = $user_id";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Fetch score trend (all quizzes ordered by date)
$trend_query = "SELECT taken_at, score, quiz_title 
                FROM performance_records 
                WHERE student_id = $user_id 
                ORDER BY taken_at ASC";
$trend_result = mysqli_query($conn, $trend_query);
$trend_data = [];
while ($row = mysqli_fetch_assoc($trend_result)) {
    $trend_data[] = $row;
}

// Fetch average score by difficulty
$difficulty_query = "SELECT difficulty, AVG(score) as avg_score 
                     FROM performance_records 
                     WHERE student_id = $user_id 
                     GROUP BY difficulty";
$difficulty_result = mysqli_query($conn, $difficulty_query);
$difficulty_labels = [];
$difficulty_scores = [];
while ($row = mysqli_fetch_assoc($difficulty_result)) {
    $difficulty_labels[] = ucfirst($row['difficulty']);
    $difficulty_scores[] = round($row['avg_score'], 1);
}

// Fetch recent attempts (last 10)
$recent_query = "SELECT quiz_title, difficulty, score, taken_at 
                 FROM performance_records 
                 WHERE student_id = $user_id 
                 ORDER BY taken_at DESC 
                 LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Galorem AI</title>
    <link rel="stylesheet" href="AI.css">
    <link rel="stylesheet" href="sidebar.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Additional analytics styles */
        .analytics-container {
            padding: 2rem;
            margin-top: 5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        .card {
            background: rgba(1, 10, 26, 0.6);
            border: 1px solid rgba(94, 137, 251, 0.3);
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            backdrop-filter: blur(3px);
        }
        body.light-mode .card {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(0, 51, 102, 0.2);
        }
        .card h4 {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        .card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #00cfff;
        }
        body.light-mode .card .value {
            color: #0077cc;
        }
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .chart-card {
            background: rgba(1, 10, 26, 0.6);
            border: 1px solid rgba(94, 137, 251, 0.3);
            border-radius: 16px;
            padding: 1rem;
        }
        body.light-mode .chart-card {
            background: rgba(255, 255, 255, 0.8);
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-table th, .recent-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(94, 137, 251, 0.3);
        }
        .score-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .score-high { background: #00a86b; color: white; }
        .score-mid { background: #ffae42; color: black; }
        .score-low { background: #ff4d4d; color: white; }
        @media (max-width: 768px) {
            .chart-row { grid-template-columns: 1fr; }
            .analytics-container { padding: 1rem; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="menu">
                <img src="icons8-menu-100.png" alt="menu" />
            </div>
            <div class="logo">
                <img src="chatbot.png" alt="logo" />
                <div id="inner-text">
                    <h1>Galorem Ai</h1>
                    <p>Smart Study Platform</p>
                </div>
            </div>
            <div class="toogle">
                <img src="cresent-moon.png" alt="dark mode toggle" />
            </div>
        </nav>
    </header>

    <div class="overlay" id="overlay"></div>
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="analytics-container">
            <!-- Summary Cards with Font Awesome icons -->
            <div class="summary-cards">
                <div class="card">
                    <h4><i class="fas fa-chalkboard"></i> Total Quizzes</h4>
                    <div class="value"><?php echo $summary['total_quizzes'] ?? 0; ?></div>
                </div>
                <div class="card">
                    <h4><i class="fas fa-chart-line"></i> Average Score</h4>
                    <div class="value"><?php echo round($summary['avg_score'] ?? 0); ?>%</div>
                </div>
                <div class="card">
                    <h4><i class="fas fa-trophy"></i> Highest Score</h4>
                    <div class="value"><?php echo $summary['highest_score'] ?? 0; ?>%</div>
                </div>
                <div class="card">
                    <h4><i class="fas fa-arrow-down"></i> Lowest Score</h4>
                    <div class="value"><?php echo $summary['lowest_score'] ?? 0; ?>%</div>
                </div>
            </div>

            <!-- Score Trend Line Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Score Trend Over Time</h3>
                <canvas id="trendChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Performance by Difficulty Bar Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-simple"></i> Average Score by Difficulty</h3>
                <canvas id="difficultyChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Recent Attempts Table -->
            <div class="chart-card">
                <h3><i class="fas fa-clock"></i> Recent Quiz Attempts</h3>
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                <table class="recent-table">
                    <thead>
                        <tr><th>Quiz Title</th><th>Difficulty</th><th>Score</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_result)): 
                            $score_class = 'score-low';
                            if ($row['score'] >= 70) $score_class = 'score-high';
                            elseif ($row['score'] >= 50) $score_class = 'score-mid';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['quiz_title']); ?></td>
                            <td><?php echo ucfirst($row['difficulty']); ?></td>
                            <td><span class="score-badge <?php echo $score_class; ?>"><?php echo $row['score']; ?>%</span></td>
                            <td><?php echo date('M d, Y', strtotime($row['taken_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No quiz attempts yet. Take a quiz to see your analytics.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Dark/Light mode toggle (consistent with other pages)
        const toggleBtn = document.querySelector('.toogle img');
        const body = document.body;
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                toggleBtn.src = body.classList.contains('light-mode') ? 'brightness.png' : 'cresent-moon.png';
            });
        }

        // Sidebar toggle script (copied from AI.php)
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

        // Trend chart data
        const trendData = <?php 
            $dates = [];
            $scores = [];
            foreach ($trend_data as $row) {
                $dates[] = date('M d', strtotime($row['taken_at']));
                $scores[] = $row['score'];
            }
            echo json_encode(['labels' => $dates, 'scores' => $scores]);
        ?>;

        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: 'Score (%)',
                    data: trendData.scores,
                    borderColor: '#00cfff',
                    backgroundColor: 'rgba(0, 207, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#00cfff',
                    pointBorderColor: '#fff',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { min: 0, max: 100, title: { display: true, text: 'Score (%)', color: '#ccc' } },
                    x: { title: { display: true, text: 'Date', color: '#ccc' } }
                },
                plugins: { legend: { labels: { color: '#ccc' } } }
            }
        });

        // Difficulty chart data
        const difficultyLabels = <?php echo json_encode($difficulty_labels); ?>;
        const difficultyScores = <?php echo json_encode($difficulty_scores); ?>;
        const ctxDiff = document.getElementById('difficultyChart').getContext('2d');
        new Chart(ctxDiff, {
            type: 'bar',
            data: {
                labels: difficultyLabels,
                datasets: [{
                    label: 'Average Score (%)',
                    data: difficultyScores,
                    backgroundColor: '#00cfff',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { min: 0, max: 100, title: { display: true, text: 'Score (%)', color: '#ccc' } }
                },
                plugins: { legend: { labels: { color: '#ccc' } } }
            }
        });
    </script>
</body>
</html>