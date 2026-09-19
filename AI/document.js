document.addEventListener("DOMContentLoaded", () => {
  const uploadBox = document.getElementById("upload-box");
  const fileInput = document.getElementById("fileInput");
  const chooseBtn = document.getElementById("chooseBtn");
  const overlay = document.getElementById("loading-overlay");
  const outputDiv = document.getElementById("output");

  // Click & drag events
  uploadBox.addEventListener("click", () => fileInput.click());
  chooseBtn.addEventListener("click", () => fileInput.click());

  uploadBox.addEventListener("dragover", e => {
    e.preventDefault();
    uploadBox.classList.add("dragover");
  });

  uploadBox.addEventListener("dragleave", () => {
    setTimeout(() => uploadBox.classList.remove("dragover"), 300);
  });

  uploadBox.addEventListener("drop", e => {
    e.preventDefault();
    setTimeout(() => uploadBox.classList.remove("dragover"), 300);
    const files = e.dataTransfer.files;
    if (files.length > 0) handleFileUpload(files[0]);
  });

  // Manual file selection
  fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) handleFileUpload(fileInput.files[0]);
  });

  // File upload handler
  function handleFileUpload(file) {
    // Clear old data only when a new file is uploaded
    sessionStorage.removeItem("documentContent");

    overlay.classList.add("active");
    const MIN_DISPLAY_TIME = 2000; 
    const startTime = Date.now();

    const formData = new FormData();
    formData.append("document", file);

    fetch("upload.php?nocache=" + Date.now(), {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(data => {
      const elapsed = Date.now() - startTime;
      const waitTime = Math.max(0, MIN_DISPLAY_TIME - elapsed);

      setTimeout(() => {
        overlay.classList.add("fade-out");
        setTimeout(() => overlay.classList.remove("active", "fade-out"), 500);

        // Store the uploaded document content
        sessionStorage.setItem("documentContent", data);

        // Show next action buttons
        outputDiv.innerHTML = `
          <div class="post-upload-actions">
            <h3>What do you want to do next?</h3>
            <button id="goToChat">Continue to Chatbot</button>
            <button id="goToQuiz">Generate Quiz</button>
          </div>
        `;

        document.getElementById("goToChat").addEventListener("click", () => {
          window.location.href = "AI.php"; // Only navigate after sessionStorage is set
        });

        document.getElementById("goToQuiz").addEventListener("click", () => {
          window.location.href = "quiz.php";
        });

        console.log("✅ Document content stored successfully");
      }, waitTime);
    })
    .catch(err => {
      overlay.classList.remove("active");
      alert("Upload failed. Please try again.");
      console.error(err);
    });
  }
});
