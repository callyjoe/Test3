<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.html');
    exit;
}

// Temporary debug – remove after testing

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="AI.css" rel="stylesheet">
    <link href="document.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>



<div id="loading-overlay">
  <div class="loader"></div>
  <p>Processing Document🎫...</p>
</div>






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

    <main>
  <div class="main-text">
    <h2>Document Upload & Analysis</h2>
    <p>
      Upload your study materials (PDF, Word, PowerPoint) and let our AI extract content for quiz<br />
      generation, flashcards, and discussions.
    </p>
  </div>

  <div class="upload-section">
    <div class="upload-box" id="upload-box">
      <img src="icons8-upload-94.png" alt="Upload Icon" />
      <p>Drop your files here or click to browse</p>
      <small>File size limit: 10MB</small>
    </div>
    <input type="file" id="fileInput" hidden />
    <button id="chooseBtn">Choose Files</button>
  </div>

  <div id="output" style="margin-top: 0;"></div>



  <div class="supported-formats">
    <div class="format-card">
      <img src="icons8-pdf-94.png" alt="PDF Icon" />
      <h3>PDF Files</h3>
      <p>Text extraction & analysis</p>
    </div>
    <div class="format-card">
      <img src="icons8-microsoft-word-94.png" alt="Word Icon" />
      <h3>Word Documents</h3>
      <p>.docx format supported</p>
    </div>
    <div class="format-card">
      <img src="icons8-powerpoint-48.png" alt="PPT Icon" />
      <h3>PowerPoin</h3>
      <p>.pptx slide content</p>
    </div>
  </div>
</main>



<script type="module" src="script.js"></script>
<script src="document.js"></script>
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




</body>
</html>