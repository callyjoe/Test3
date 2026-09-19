<?php
session_start();
if ($_SESSION['role'] === 'lecturer') {
    echo "
    <div style='font-family:Segoe UI,sans-serif; text-align:center; margin-top:80px;'>
        <h2 style='color:#1e3a8a;'>⚠️ Access Denied</h2>
        <p style='color:#4b5563;'>This page is for students only.</p>
        <a href='lecturer_login.php' 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galorem AI - Chat</title>
    <link rel="stylesheet" href="AI.css" />
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===================== PLUS BUTTON & UPLOAD MENU ===================== */
        .textarea-wrapper {
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }

        .plus-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(94, 137, 251, 0.12);
            color: #5e89fb;
            border: none;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.2s ease, transform 0.15s ease;
        }
        .plus-btn:hover { background: rgba(94, 137, 251, 0.25); }
        .plus-btn:active { transform: scale(0.92); }
        .plus-btn.open { transform: rotate(45deg); }

        .upload-menu {
            position: absolute;
            bottom: 48px;
            left: 0;
            background: rgba(10, 15, 26, 0.97);
            border: 1px solid rgba(94, 137, 251, 0.35);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            min-width: 200px;
            display: none;
            z-index: 500;
            overflow: hidden;
        }
        .upload-menu.show { display: block; }

        .upload-menu button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            color: #e0f2ff;
            padding: 12px 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .upload-menu button:hover { background: rgba(94, 137, 251, 0.18); }
        .upload-menu button i { color: #5e89fb; width: 16px; }

        body.light-mode .upload-menu {
            background: rgba(255, 255, 255, 0.97);
            border-color: rgba(0, 51, 102, 0.25);
        }
        body.light-mode .upload-menu button { color: #1e293b; }

        /* Inline upload progress shown above textarea */
        .upload-progress {
            display: none;
            align-items: center;
            gap: 10px;
            background: rgba(94, 137, 251, 0.1);
            border: 1px solid rgba(94, 137, 251, 0.3);
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #cbd5e1;
        }
        .upload-progress.show { display: flex; }
        .upload-progress .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(94, 137, 251, 0.3);
            border-top-color: #5e89fb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .upload-progress .cancel-upload {
            margin-left: auto;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .attached-file-chip {
            display: none;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 10px;
            padding: 6px 12px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #6ee7b7;
            width: fit-content;
        }
        .attached-file-chip.show { display: flex; }
        .attached-file-chip .remove-chip {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
        }
    </style>
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
    <?php include 'sidebar.php'; ?>

    <main>
        <div class="heading">
            <h1>Welcome to Galorem AI</h1>
            <p>Your intelligent companion for academic success. Ask questions,<br /> generate quizzes, create flashcards, and more.</p>
        </div>

        <div class="main-area">
           <div class="chat-area">
              <div class="chat-scroll-area" style="overflow-y: auto; flex: 1; padding-bottom: 1rem;">
                <div class="chat-area-head">
                  <img src="icons8-chatbot-100.png" />
                  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                  <img src="icons8-expand-48.png" id="expand-icon" />
                  <img src="icons8-shrink-down-48.png" id="shrink-icon" style="display: none;" />
                </div>

                <div class="chat">
                  <div id="ai-img">
                    <img src="icons8-chatbot-100.png" />
                  </div>
                  <div class="chat-box ai">
                    <p>Hello! I'm Galorem AI. How can I help you today?</p>
                     <p id="tip-text"></p>
                    <span class="timestamp">⏰AM/PM</span>
                  </div>
                </div>

                <div class="chat user-template" style="display: none">
                  <div class="chat-box user">
                    <p></p>
                    <span class="timestamp"></span>
                  </div>
                </div>
              </div>

              <div class="input" id="input">

                <!-- Upload progress indicator -->
                <div class="upload-progress" id="uploadProgress">
                    <div class="spinner"></div>
                    <span id="uploadProgressText">Processing file...</span>
                    <button type="button" class="cancel-upload" id="cancelUploadBtn">&times;</button>
                </div>

                <!-- Attached file confirmation chip -->
                <div class="attached-file-chip" id="attachedChip">
                    <i class="fas fa-check-circle"></i>
                    <span id="attachedFileName"></span>
                    <button type="button" class="remove-chip" id="removeChipBtn">&times;</button>
                </div>

                <form id="chat-form">
                    <div class="textarea-wrapper">

                        <button type="button" class="plus-btn" id="plusBtn">
                            <i class="fas fa-plus"></i>
                        </button>

                        <div class="upload-menu" id="uploadMenu">
                            <button type="button" id="uploadImageOption">
                                <i class="fas fa-image"></i> Upload Image
                            </button>
                            <button type="button" id="uploadDocOption">
                                <i class="fas fa-file-alt"></i> Upload Document
                            </button>
                        </div>

                        <textarea id="message" name="message" rows="3" placeholder="Ask me anything..."></textarea>
                        <button type="button" id="voice-input-btn" title="Click to speak">
                            🎤
                        </button>
                    </div>
                    <button type="submit">
                        <img src="icons8-send-96.png" />
                    </button>
                </form>

                <!-- Hidden file inputs -->
                <input type="file" id="imageFileInput" accept="image/*" hidden>
                <input type="file" id="docFileInput" accept=".pdf,.doc,.docx,.ppt,.pptx" hidden>
              </div>

            </div>
        </div>
    </main>

    <script type="module" src="script.js"></script>
    <script type="module" src="chat_upload.js"></script>

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

    <script>
      const tipElement = document.getElementById("tip-text");
      const tipMessage = `💡 Tip: For code review, press Ctrl + Alt + G or open the code editor here: Open router test`;
      const folderLink = "Open router test/editor.html";

      let i = 0;
      const speed = 35;

      function typeTip() {
        if (i < tipMessage.length) {
          tipElement.innerHTML += tipMessage.charAt(i);
          i++;
          setTimeout(typeTip, speed);
        } else {
          tipElement.innerHTML = tipElement.innerHTML.replace(
            "Open router test",
            `<a href="${folderLink}" target="_blank" style="color:#00eaff;text-decoration:underline;">Open router test</a>`
          );
        }
      }

      window.addEventListener("DOMContentLoaded", typeTip);

      document.addEventListener("keydown", function (e) {
        if (e.ctrlKey && e.altKey && e.key.toLowerCase() === "g") {
          e.preventDefault();
          window.location.href = folderLink;
        }
      });
    </script>

    <script>
        const toggleBtn = document.querySelector('.toogle img');
        const body = document.body;

        toggleBtn.addEventListener('click', () => {
          body.classList.toggle('light-mode');
          if (body.classList.contains('light-mode')) {
            toggleBtn.src = 'brightness.png';
          } else {
            toggleBtn.src = 'cresent-moon.png';
          }
        });
    </script>

</body>
</html>