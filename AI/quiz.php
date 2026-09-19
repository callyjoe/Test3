<?php
session_start();
echo "<!-- DEBUG ROLE: " . ($_SESSION['role'] ?? 'NOT SET') . " | USER_ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->";
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
include '../config.php';

$student_school = $_SESSION['school'];
$dept_query = "SELECT DISTINCT department FROM learning_resources WHERE school='$student_school' ORDER BY department";
$departments = mysqli_query($conn, $dept_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Galorem AI - Quiz Generator</title>
  <link rel="stylesheet" href="AI.css" />
  <link rel="stylesheet" href="quiz.css" />
  <link rel="stylesheet" href="sidebar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    .flip-container { perspective: 1500px; margin: 2rem auto; max-width: 800px; }
    .flipper { transition: transform 0.6s; transform-style: preserve-3d; position: relative; }
    .flip-container.flip .flipper { transform: rotateY(180deg); }
    .front, .back { backface-visibility: hidden; position: relative; width: 100%; }
    .front { z-index: 2; transform: rotateY(0deg); }
    .back { transform: rotateY(180deg); position: absolute; top: 0; left: 0; }
    .lecturer-panel { background: rgba(25, 25, 55, 0.9); border-radius: 15px; padding: 1.5rem; box-shadow: 0 0 20px rgba(94, 137, 251, 0.3); }
    .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem; max-height: 400px; overflow-y: auto; }
    .resource-card { position: relative; background: url('/Test3/logos/UENR-LOGO.jpg') center center no-repeat; background-size: cover; border-radius: 12px; overflow: hidden; cursor: pointer; border: 1px solid #2d4abf; transition: box-shadow 0.2s ease; }
    .resource-card:hover { box-shadow: 0 0 12px rgba(0, 207, 255, 0.4); }
    .resource-card::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 24, 48, 0.7); transition: background 0.25s ease; pointer-events: none; z-index: 1; }
    .resource-card:hover::after { background: rgba(15, 24, 48, 0.4); }
    .resource-card .card-content { position: relative; z-index: 2; padding: 0.8rem; text-align: center; color: white; }
    .preview-btn { background: #3b82f6; color: white; border: none; padding: 0.2rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.7rem; margin-top: 6px; }
    .preview-btn:hover { background: #2563eb; }
    .resource-card .select-overlay { position: absolute; bottom: 8px; right: 8px; background: #10b981; padding: 0.25rem 0.6rem; border-radius: 20px; font-weight: bold; font-size: 0.7rem; color: white; opacity: 0; transition: opacity 0.2s ease; pointer-events: none; z-index: 3; white-space: nowrap; }
    .resource-card:hover .select-overlay { opacity: 1; }

    .preview-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; justify-content: center; align-items: center; }
    .preview-content { background: #0a0f1a; width: 95%; height: 90%; max-width: 1400px; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; }
    .preview-header { padding: 1rem; border-bottom: 1px solid #2d4abf; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .preview-body { flex: 1; overflow: hidden; padding: 0; background: #1a1f2e; }
    .preview-body iframe { width: 100%; height: 100%; border: none; display: block; }

    .floating-chat-btn { position: fixed; bottom: 30px; right: 30px; background: #10b981; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.3); z-index: 10001; transition: 0.2s; }
    .floating-chat-btn:hover { background: #059669; }
    .chat-panel { position: fixed; bottom: 100px; right: 30px; width: 350px; background: #0a0f1a; border: 1px solid #2d4abf; border-radius: 12px; padding: 1rem; display: none; flex-direction: column; gap: 0.8rem; z-index: 10002; box-shadow: 0 4px 15px rgba(0,0,0,0.4); }
    .chat-panel textarea { width: 100%; padding: 0.5rem; border-radius: 8px; background: #1a1f2e; color: white; border: 1px solid #3b82f6; resize: vertical; font-size: 0.9rem; }
    .chat-panel button { background: #3b82f6; color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .chat-panel button:hover { background: #2563eb; }
    .chat-response { background: #1e293b; padding: 0.5rem; border-radius: 8px; font-size: 0.85rem; max-height: 200px; overflow-y: auto; }

    .btn-flip { background: #2563eb; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; margin-bottom: 1rem; }
    #useResourceBtn { background: #10b981; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; margin-left: 10px; }
    #useResourceBtn:hover { background: #059669; }

    /* Timer bar */
    #timer-bar-wrapper { display: none; width: 100%; background: #1a1f2e; border-radius: 8px; overflow: hidden; height: 10px; margin-bottom: 1rem; }
    #timer-fill { height: 100%; width: 100%; background: #3b82f6; transition: width 1s linear; }
    

    #loading-popup {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.85);
      color: white;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      z-index: 9999;
      flex-direction: column;
      font-family: 'Segoe UI', sans-serif;
    }
    .loading-sequence { display: flex; flex-direction: column; align-items: center; }
    .dot-row { display: flex; gap: 12px; margin-bottom: 15px; }
    .dot { width: 20px; height: 20px; border-radius: 50%; background: #5e89fb; animation: pulseDot 1.2s infinite ease-in-out; }
    .dot:nth-child(2) { animation-delay: 0.2s; }
    .dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes pulseDot {
      0%, 100% { transform: scale(1); opacity: 0.6; }
      50% { transform: scale(1.4); opacity: 1; }
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
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

<!-- ===================== FORM PAGE ===================== -->
<div id="form-page">
  <div class="headingpage">
    <h1 class="quiz-heading">Quiz Generator</h1>
    <p class="quiz-subheading">Generate UENR-style exam questions tailored to your study needs</p>
  </div>

  <main>
    <div class="flip-container" id="flipContainer">
      <div class="flipper">

        <!-- FRONT: Quiz Form -->
        <div class="front">
          <div class="quiz-container">
            <button class="btn-flip" id="flipToMaterials"><i class="fas fa-chalkboard-user"></i> Browse Lecturer Materials</button>
            <h2 class="form-title"><i class="fas fa-edit"></i> Create Custom Quiz</h2>
            <p class="form-desc">Configure your quiz settings for personalized practice</p>
            <form id="quiz-form">
              <div class="form-group">
                <div class="left">
                  <label for="topic"><i class="fas fa-tag"></i> Topic/Subject</label>
                  <input type="text" id="topic" placeholder="e.g., Calculus, Ghana History, Biology" />
                </div>
                <div class="right">
                  <label for="question-type"><i class="fas fa-question-circle"></i> Question Type</label>
                  <select id="question-type">
                    <option>Quiz</option><option>Exams</option><option>Mid-Sem</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <div class="left">
                  <label for="num-questions"><i class="fas fa-sort-numeric-up"></i> Number of Questions</label>
                  <select id="num-questions">
                    <option>5</option><option>10</option><option>15</option><option>20</option>
                  </select>
                </div>
                <div class="right">
                  <label for="time-limit"><i class="fas fa-clock"></i> Time Limit (minutes)</label>
                  <select id="time-limit">
                    <option>10</option><option>20</option><option>30</option><option>60</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <div class="left-full">
                  <label for="difficulty"><i class="fas fa-chart-line"></i> Difficulty Level</label>
                  <select id="difficulty">
                    <option>Easy</option><option>Medium</option><option>Hard</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <div class="left-full">
                  <label for="instructions"><i class="fas fa-info-circle"></i> Specific Instructions (optional)</label>
                  <textarea id="instructions" placeholder="Any specific topics or format requirements..."></textarea>
                </div>
              </div>
              <input type="hidden" id="selectedResourceId" value="">
              <button type="submit" class="generate-btn"><i class="fas fa-plus-circle"></i> Generate Quiz</button>
            </form>
          </div>
        </div>

        <!-- BACK: Lecturer Materials -->
        <div class="back">
          <div class="quiz-container lecturer-panel">
            <button class="btn-flip" id="flipToForm"><i class="fas fa-arrow-left"></i> Back to Quiz Form</button>
            <h2 class="form-title"><i class="fas fa-book-open"></i> Lecturer Resources</h2>
            <div class="form-group">
              <label><i class="fas fa-building"></i> Select Department</label>
              <select id="deptSelect">
                <option value="">-- Choose Department --</option>
                <?php while($dept = mysqli_fetch_assoc($departments)): ?>
                  <option value="<?php echo htmlspecialchars($dept['department']); ?>"><?php echo htmlspecialchars($dept['department']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group" id="lecturerGroup" style="display:none;">
              <label><i class="fas fa-chalkboard-teacher"></i> Select Lecturer</label>
              <select id="lecturerSelect"></select>
            </div>
            <div id="resourceGrid" class="resource-grid" style="display:none;"></div>
            <div id="selectedResourceInfo"></div>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>
<!-- ===================== END FORM PAGE ===================== -->


<!-- ===================== QUESTION PAGE ===================== -->
<div id="question-page" style="display:none;">
  <div class="headingpage">
    <h1 class="quiz-heading">Your Quiz</h1>
    <p class="quiz-subheading">Answer all questions before the timer runs out</p>
  </div>
  <main>
    <div class="quiz-container" style="max-width:800px; margin: 2rem auto;">
      <div id="countdown-timer">Time: 00:00</div>
      <div id="timer-bar-wrapper">
        <div id="timer-fill"></div>
      </div>
      <div id="question-area"></div>
    </div>
  </main>
</div>
<!-- ===================== END QUESTION PAGE ===================== -->


<!-- Preview Modal -->
<div id="previewModal" class="preview-modal">
  <div class="preview-content">
    <div class="preview-header">
      <h3><i class="fas fa-eye"></i> <span id="previewTitle">Resource Preview</span></h3>
      <button id="closePreview" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div id="previewBody" class="preview-body">Loading...</div>
  </div>
</div>

<!-- Loading Popup -->
<div id="loading-popup">
  <div class="loading-sequence">
    <div class="dot-row">
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
    <div id="loading-text">Generating your quiz... Please wait</div>
  </div>
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

  // Flip container
  const flipContainer = document.getElementById('flipContainer');
  document.getElementById('flipToMaterials').addEventListener('click', () => flipContainer.classList.add('flip'));
  document.getElementById('flipToForm').addEventListener('click', () => flipContainer.classList.remove('flip'));

  const deptSelect = document.getElementById('deptSelect');
  const lecturerGroup = document.getElementById('lecturerGroup');
  const lecturerSelect = document.getElementById('lecturerSelect');
  const resourceGrid = document.getElementById('resourceGrid');

  deptSelect.addEventListener('change', async () => {
    const dept = deptSelect.value;
    if (!dept) { lecturerGroup.style.display = 'none'; resourceGrid.style.display = 'none'; return; }
    const response = await fetch(`get_lecturers.php?department=${encodeURIComponent(dept)}`);
    const lecturers = await response.json();
    lecturerSelect.innerHTML = '<option value="">-- Select Lecturer --</option>';
    lecturers.forEach(lec => {
      lecturerSelect.innerHTML += `<option value="${lec.user_id}">${escapeHtml(lec.username)}</option>`;
    });
    lecturerGroup.style.display = 'block';
    resourceGrid.style.display = 'none';
  });

  lecturerSelect.addEventListener('change', async () => {
    const lecturerId = lecturerSelect.value;
    if (!lecturerId) { resourceGrid.style.display = 'none'; return; }
    const response = await fetch(`get_resources.php?lecturer_id=${lecturerId}`);
    const resources = await response.json();
    resourceGrid.innerHTML = '';
    resources.forEach(res => {
      const card = document.createElement('div');
      card.className = 'resource-card';
      card.dataset.id = res.resource_id;
      card.dataset.title = res.title;
      card.dataset.type = res.type;
      card.innerHTML = `
        <div class="card-content">
          <strong>${escapeHtml(res.title)}</strong><br>
          <small>${res.type}</small><br>
          <button class="preview-btn" data-id="${res.resource_id}"><i class="fas fa-search"></i> Preview</button>
        </div>
        <div class="select-overlay"><span><i class="fas fa-check-circle"></i> Select</span></div>
      `;
      resourceGrid.appendChild(card);
    });
    resourceGrid.style.display = 'grid';
  });

  resourceGrid.addEventListener('click', async (e) => {
    let card = e.target.closest('.resource-card');
    if (!card) return;
    if (e.target.classList.contains('preview-btn') || e.target.closest('.preview-btn')) {
      const btn = e.target.closest('.preview-btn');
      const resId = btn.dataset.id;
      showPreview(resId);
      return;
    }
    document.querySelectorAll('.resource-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    const resId = card.dataset.id;
    const resTitle = card.dataset.title;
    document.getElementById('selectedResourceInfo').innerHTML = `<p>Selected: <strong>${escapeHtml(resTitle)}</strong> <button id="useResourceBtn"><i class="fas fa-check"></i> Use for Quiz</button></p>`;

    document.getElementById('useResourceBtn').addEventListener('click', async () => {
      const textRes = await fetch(`fetch_resource_text.php?resource_id=${resId}`);
      const data = await textRes.json();
      sessionStorage.setItem('lecturerResourceContent', data.content || '');
      sessionStorage.setItem('selectedResourceId', resId);
      document.getElementById('selectedResourceId').value = resId;
      document.getElementById('topic').value = escapeHtml(resTitle);
      document.getElementById('topic').disabled = true;
      document.getElementById('topic').placeholder = `Using: ${escapeHtml(resTitle)}`;
      flipContainer.classList.remove('flip');
    });
  });

  let currentPreviewContent = '';

  async function showPreview(resId) {
    try {
      const response = await fetch(`fetch_resource_text.php?resource_id=${resId}`);
      const resData = await response.json();
      const modal = document.getElementById('previewModal');
      const previewBody = document.getElementById('previewBody');
      const previewTitle = document.getElementById('previewTitle');

      if (resData.error) {
        previewBody.innerHTML = `<p>${resData.error}</p>`;
        previewTitle.innerText = 'Error';
        modal.style.display = 'flex';
        return;
      }

      previewTitle.innerText = resData.title;
      sessionStorage.setItem('selectedResourceId', resId);

      if (resData.type === 'pdf') {
        previewBody.innerHTML = `<iframe src="${resData.url}" frameborder="0"></iframe>`;

        // ✅ Extract text client-side using PDF.js
        try {
          const pdfjsLib = window['pdfjs-dist/build/pdf'] || await import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
          pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

          const loadingTask = pdfjsLib.getDocument(resData.url);
          const pdf = await loadingTask.promise;
          let fullText = '';

          for (let pageNum = 1; pageNum <= Math.min(pdf.numPages, 10); pageNum++) {
            const page = await pdf.getPage(pageNum);
            const textContent = await page.getTextContent();
            const pageText = textContent.items.map(item => item.str).join(' ');
            fullText += pageText + '\n';
          }

          fullText = fullText.trim();
          console.log('Client-side extracted length:', fullText.length, fullText.substring(0, 200));

          if (fullText) {
            currentPreviewContent = fullText;
            sessionStorage.setItem('lecturerResourceContent', fullText);
          } else {
            // ✅ Last resort: tell AI the PDF URL and let it reason about it
            currentPreviewContent = `PDF resource titled "${resData.title}" available at ${resData.url}. Answer questions about this document as best you can based on the title and context.`;
            sessionStorage.setItem('lecturerResourceContent', currentPreviewContent);
          }
        } catch (pdfErr) {
          console.warn('PDF.js extraction failed:', pdfErr);
          currentPreviewContent = `PDF resource titled "${resData.title}".`;
          sessionStorage.setItem('lecturerResourceContent', currentPreviewContent);
        }

      } else if (resData.type === 'youtube') {
        let videoId = resData.url.split('v=')[1];
        if (!videoId) {
          const parts = resData.url.split('/');
          videoId = parts[parts.length - 1];
        }
        previewBody.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen style="width:100%; height:100%;"></iframe>`;
        currentPreviewContent = `YouTube video titled "${resData.title}" at ${resData.url}.`;
        sessionStorage.setItem('lecturerResourceContent', currentPreviewContent);
      }

      modal.style.display = 'flex';
      document.getElementById('closePreview').onclick = () => modal.style.display = 'none';
      createChatButton();
    } catch (err) {
      console.error('Preview error:', err);
      alert('Could not load preview. Check console for details.');
    }
  }

  let chatButtonCreated = false;
  function createChatButton() {
    if (chatButtonCreated) return;
    const btn = document.createElement('div');
    btn.className = 'floating-chat-btn';
    btn.innerHTML = '<i class="fas fa-comment"></i>';
    btn.onclick = () => toggleChatPanel();
    document.body.appendChild(btn);
    chatButtonCreated = true;
  }

  function toggleChatPanel() {
    let panel = document.getElementById('floatingChatPanel');
    if (!panel) {
      panel = document.createElement('div');
      panel.id = 'floatingChatPanel';
      panel.className = 'chat-panel';
      panel.innerHTML = `
        <h4><i class="fas fa-robot"></i> Ask AI about this resource</h4>
        <textarea id="chatQuestion" rows="3" placeholder="Type your question..."></textarea>
        <button id="sendChatQuestion">Send</button>
        <div id="chatResponse" class="chat-response">Type a question and click Send.</div>
      `;
      document.body.appendChild(panel);
      document.getElementById('sendChatQuestion').onclick = async () => {
        const question = document.getElementById('chatQuestion').value.trim();
        if (!question) return;
        const responseDiv = document.getElementById('chatResponse');
        responseDiv.innerHTML = 'Thinking...';
        const answer = await askAIAboutResource(question);
        responseDiv.innerHTML = answer;
      };
    }
    panel.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
  }

  // Replace the existing askAIAboutResource function with this:
async function askAIAboutResource(question) {
  let content = currentPreviewContent || sessionStorage.getItem('lecturerResourceContent');

  if (!content) {
    const resourceId = document.getElementById('selectedResourceId')?.value || sessionStorage.getItem('selectedResourceId');
    if (resourceId) {
      try {
        const response = await fetch(`fetch_resource_text.php?resource_id=${resourceId}`);
        const resData = await response.json();
        content = resData.content || '';
        if (content) sessionStorage.setItem('lecturerResourceContent', content);
      } catch (e) {
        content = '';
      }
    }
  }

  if (!content) {
    return 'No resource content available. Please open a preview or select a resource first.';
  }

  const prompt = `You are an AI study assistant. The user is viewing the following resource content (excerpt):\n\n${content.substring(0, 4000)}\n\nUser question: "${question}"\n\nAnswer the question based on the resource content. Be helpful and concise.`;

  // ✅ Call Mistral directly instead of using queryAI
  try {
    const response = await fetch("https://api.mistral.ai/v1/chat/completions", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer COE44lUR8WR95rvozEaRl8JkSR9ocT6U"
      },
      body: JSON.stringify({
        model: "open-mistral-7b",
        messages: [
          { role: "system", content: "You are a helpful academic study assistant." },
          { role: "user", content: prompt }
        ],
        temperature: 0.7,
        max_tokens: 800
      })
    });

    const result = await response.json();
    return result.choices?.[0]?.message?.content?.trim() || "No response received.";
  } catch (err) {
    console.error('AI error:', err);
    return "Error connecting to AI. Please try again.";
  }
}
</script>

<script type="module" src="quiz.js"></script>
<script type="module" src="script.js"></script>
</body>
</html>