<?php
session_start();
if ($_SESSION['role'] === 'lecturer') {
    echo "
    <div style='font-family:Segoe UI,sans-serif; text-align:center; margin-top:80px;'>
        <h2 style='color:#1e3a8a;'>⚠️ Access Denied</h2>
        <p style='color:#4b5563;'>This page is for students only.</p>
        <a href='../lecturer/lecturer_dashboard.php' 
           style='display:inline-block; margin-top:15px; padding:10px 20px; 
                  background:#2563eb; color:white; border-radius:8px; text-decoration:none;'>
           Go to Lecturer Dashboard
        </a>
    </div>";
    exit;
}
if ($_SESSION['role'] !== 'student') {
    header('Location: ../signup.html');
    exit;
}

// Temporary debug – remove after testing

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Galorem AI - Flashcards</title>
  <link rel="stylesheet" href="flashcards.css" />
  <link rel="stylesheet" href="AI.css"/>
  <link rel="stylesheet" href="sidebar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="menu">
              <img src="icons8-menu-100.png" />
            </div>
            <div class="logo">
                <img src="chatbot.png" />
                <div id="inner-text">
                <h1>Galorem Ai</h1>
                <p>Smart Study Platform</p>
                </div>
            </div>

            
            <div class="toogle">
                <img src="cresent-moon.png" />
            </div>
        </nav>
    </header>

    <div class="overlay" id="overlay"></div>
    <!-- Sidebar Menu -->
<?php include 'sidebar.php'; ?>


  <main class="flashcard-container">
    <!-- Top Buttons -->
    <div class="flashcard-nav">
      <button id="review-mode" class="active">Review Mode</button>
      <button id="create-mode">+ Create Card</button>
    </div>

    <!-- Create Page -->
    <section id="create-page">
      <h2>Create AI Flashcards</h2>
      <form id="flashcard-form">
        <textarea id="flashcard-topic" placeholder="Enter topic or paste document text..." required></textarea>
        <select id="flashcard-difficulty">
          <option value="easy">Easy</option>
          <option value="medium" selected>Medium</option>
          <option value="hard">Hard</option>
        </select>
        <input type="text" id="flashcard-category" placeholder="Category (e.g., Math, CS)" required />
        <button type="submit" class="generate-btn">✨ Generate Flashcards</button>
      </form>
    </section>

    <!-- Review Page -->
    <section id="review-page" style="display:none;">
      <div class="flashcard-stats">
        <div>Total Cards<br><span id="stat-total">0</span></div>
        <div>Correct<br><span id="stat-correct">0</span></div>
        <div>Incorrect<br><span id="stat-incorrect">0</span></div>
        <div>Categories<br><span id="stat-category">0</span></div>
      </div>

      <div class="flashcard-view">
        <span class="difficulty-label" id="difficulty-label"></span>
        <div class="card-count" id="card-count">1 of 1</div>

        <div class="card" id="flashcard">
          <div class="front" id="card-front">Question here</div>
          <div class="back" id="card-back">Answer here</div>
        </div>

        <div class="card-meta">
          <span id="card-category">Category</span>
        </div>

        <div class="card-controls">
          <button id="prev-card">← Previous</button>
          <button id="flip-card">🔁 Flip</button>
          <button id="next-card">Next →</button>
        </div>

        <div class="correctness">
          <button id="mark-wrong" class="wrong">❌ Incorrect</button>
          <button id="mark-correct" class="correct">✅ Correct</button>
        </div>
      </div>
    </section>
  </main>

  <script type="module" src="flashcards.js"></script>

  <div id="loading-overlay">
  <div class="loading-box">
    <div class="loader"></div>
    <p>🧠 Generating flashcards, please wait...</p>
  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.querySelector('.toogle img');
    const body = document.body;

    if (!toggleBtn) {
      console.warn("Toggle button not found.");
      return;
    }

    toggleBtn.addEventListener('click', () => {
      body.classList.toggle('light-mode');

      // Swap icon
      toggleBtn.src = body.classList.contains('light-mode')
        ? 'brightness.png'
        : 'cresent-moon.png';
    });
  });
</script>

<script>
  const menuIcon = document.querySelector('.menu img');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const closeBtn = document.getElementById('closeBtn');

  function openSidebar() {
    sidebar.style.left = '0';
    overlay.style.display = 'block';
  }

  function closeSidebar() {
    sidebar.style.left = '-260px';
    overlay.style.display = 'none';
  }

  menuIcon.addEventListener('click', openSidebar);
  closeBtn.addEventListener('click', closeSidebar);
  overlay.addEventListener('click', closeSidebar);
</script>

<div id="error-popup" class="popup-overlay">
  <div class="popup-content">
    <h3>⚠️ Inappropriate Topic</h3>
    <p>We cannot generate flashcards on this topic.<br>Please try a more academic or appropriate subject.</p>
    <button id="close-error">Okay</button>
  </div>
</div>

</body>
</html>
