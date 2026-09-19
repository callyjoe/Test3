import { fetchAIResponse } from "./api.js";

const form = document.getElementById("chat-form");
const textarea = document.getElementById("message");
const chatArea = document.querySelector(".chat-scroll-area");

const voiceBtn = document.getElementById("voice-input-btn");

if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const recognition = new SpeechRecognition();

  recognition.lang = 'en-US';
  recognition.continuous = false;
  recognition.interimResults = false;

  recognition.onstart = () => {
    voiceBtn.classList.add("listening");
  };

  recognition.onend = () => {
    voiceBtn.classList.remove("listening");
  };

  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    textarea.value += transcript;
    textarea.focus();
  };

  recognition.onerror = (event) => {
    alert("Voice recognition error: " + event.error);
    voiceBtn.classList.remove("listening");
  };

  voiceBtn.addEventListener("click", () => {
    recognition.start();
  });
} else {
  voiceBtn.disabled = true;
  voiceBtn.title = "Speech recognition not supported in this browser.";
}

function animateWave(waveContainer) {
  const bars = waveContainer.querySelectorAll('.bar');
  let isSpeaking = true;

  const interval = setInterval(() => {
    if (!isSpeaking) {
      clearInterval(interval);
      bars.forEach(bar => bar.style.height = '10px');
      return;
    }

    bars.forEach(bar => {
      const height = Math.floor(Math.random() * 15) + 10; // Between 10 and 25px
      bar.style.height = `${height}px`;
    });
  }, 120);

  return () => {
    isSpeaking = false;
  };
}


function formatTime() {
  const now = new Date();
  return now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
}

function escapeHTML(str) {
  return str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function formatAIText(text) {
  let escaped = escapeHTML(text);

  // Remove markdown-style asterisks
  escaped = escaped.replace(/\*/g, '');

  // Inline code using backticks
  escaped = escaped.replace(/`([^`]+)`/g, "<code>$1</code>");

  // Bold numbered steps
  escaped = escaped.replace(/^\d+\.\s/gm, match => `<strong>${match}</strong>`);

  // Remove triple line breaks
  escaped = escaped.replace(/\n{3,}/g, '\n\n');

  const bullets = ['•', '➤', '➡️', '👉', '💡', '⭐', '✔️', '🔥'];
  const headings = ['🧠', '📘', '🚀', '📌', '📝', '🔍'];

  const lines = escaped.split(/\n/);
  const formattedLines = [];

  for (let line of lines) {
    const trimmed = line.trim();

    if (!trimmed) continue;

    if (/^##\s*/.test(trimmed)) {
      const headingText = trimmed.replace(/^##\s*/, '');
      const emoji = headings[Math.floor(Math.random() * headings.length)];
      formattedLines.push(`<br><strong style="font-size: 1.1rem">${emoji} ${headingText}</strong><br>`);
    } else if (/^[A-Za-z0-9]/.test(trimmed)) {
      const emoji = bullets[Math.floor(Math.random() * bullets.length)];
      formattedLines.push(`${emoji} ${trimmed}<br><br>`);
    } else {
      formattedLines.push(`${trimmed}<br><br>`);
    }
  }

  return formattedLines.join('');
}

function createTypingBubble() {
  const typing = document.createElement("div");
  typing.classList.add("typing-indicator");
  typing.setAttribute("id", "typing");
  typing.innerHTML = `
    <div class="typing-dot"></div>
    <div class="typing-dot"></div>
    <div class="typing-dot"></div>
  `;
  chatArea.appendChild(typing);
  chatArea.scrollTop = chatArea.scrollHeight;
}

function removeTypingBubble() {
  const typing = document.getElementById("typing");
  if (typing) typing.remove();
}

function createAIReply(message) {
  const aiReply = document.createElement("div");
  aiReply.className = "chat";
  aiReply.innerHTML = `
    <div id="ai-img">
      <img src="icons8-chatbot-100.png" />
    </div>
    <div class="chat-box ai">
      <p>${formatAIText(message)}</p>
      <span class="timestamp">${formatTime()}</span>
      <button class="speak-btn" title="Listen to reply">🔊</button>
      <div class="wave-container" style="display: none;">
        <div class="bar"></div><div class="bar"></div><div class="bar"></div>
        <div class="bar"></div><div class="bar"></div>
      </div>
    </div>
  `;
  chatArea.appendChild(aiReply);
  chatArea.scrollTop = chatArea.scrollHeight;

  const speakBtn = aiReply.querySelector(".speak-btn");
  const wave = aiReply.querySelector(".wave-container");
  const textToRead = aiReply.querySelector("p").innerText;

  speakBtn.addEventListener("click", () => {
    const synth = window.speechSynthesis;
    const utterance = new SpeechSynthesisUtterance(textToRead);
    utterance.lang = "en-US";

    utterance.onstart = () => {
      wave.style.display = "flex";
      animateWave(wave);
    };

    utterance.onend = () => {
      wave.style.display = "none";
    };

    synth.speak(utterance);
  });
}


form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const userText = textarea.value.trim();
  if (!userText) return;
  
  const docContext = sessionStorage.getItem("documentContent");
  console.log("Loaded document content:", docContext); // ✅ verify latest upload

const finalPrompt = docContext
  ? `Based on the following document content:\n${docContext}\n\nAnswer this query:\n${userText}`
  : userText;

  // Add user message to chat
  const userMsg = document.createElement("div");
  userMsg.className = "chat";
  userMsg.innerHTML = `
    <div class="chat-box user">
      <p>${escapeHTML(userText)}</p>
      <span class="timestamp">${formatTime()}</span>
    </div>
  `;
  chatArea.appendChild(userMsg);
  textarea.value = "";
  chatArea.scrollTop = chatArea.scrollHeight;

  // Typing animation
  createTypingBubble();

    try {
    const docContext = sessionStorage.getItem("documentContent");
    const finalPrompt = docContext
      ? `Based on the following document content:\n${docContext}\n\nAnswer this query:\n${userText}`
      : userText;

    const aiReply = await fetchAIResponse(finalPrompt);
    removeTypingBubble();
    createAIReply(aiReply);

   
} catch (error) {
    removeTypingBubble();
    createAIReply("❌ Error: Couldn't connect. Please try again.");
  }

});
document.addEventListener("DOMContentLoaded", async () => {
  const docContext = sessionStorage.getItem("documentContent");

  if (docContext) {
    createTypingBubble();

    try {
      const introPrompt = `You are an AI study assistant. The user just uploaded a document. Read this content and reply with a short friendly message like: "I've reviewed your document. You can now ask questions related to it."\n\nDocument content:\n${docContext}`;

      const introReply = await fetchAIResponse(introPrompt);
      removeTypingBubble();
      createAIReply(introReply);
    } catch (err) {
      removeTypingBubble();
      createAIReply("❌ Error loading the document context.");
    }
  }
});

// DARK & LIGHT MODE TOGGLE
const toggleBtn = document.querySelector('.toogle img');
const body = document.body;

toggleBtn.addEventListener('click', () => {
  body.classList.toggle('light-mode');

  // Swap the toggle icon
  if (body.classList.contains('light-mode')) {
    toggleBtn.src = 'brightness.png';
  } else {
    toggleBtn.src = 'cresent-moon.png';
  }
});
