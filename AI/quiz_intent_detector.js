// ===================== QUIZ INTENT DETECTOR =====================
// Watches user chat messages for quiz/test/assessment related intent.
// When detected, shows a confirmation card after the AI's reply,
// letting the user opt-in to be redirected to quiz.php with context.

document.addEventListener("DOMContentLoaded", () => {
    const form      = document.getElementById("chat-form");
    const textarea  = document.getElementById("message");
    const chatArea  = document.querySelector(".chat-scroll-area");

    if (!form || !textarea || !chatArea) return;

    // Keywords/phrases that suggest the user wants a quiz/test/assessment
    const QUIZ_KEYWORDS = [
        "quiz", "quizzes", "quiz me",
        "test me", "test on", "give me a test",
        "assess me", "assessment", "assess my knowledge",
        "exam", "mock exam", "practice questions",
        "practice exam", "generate questions", "generate a quiz",
        "create a quiz", "make a quiz", "set questions",
        "challenge me", "evaluate my understanding"
    ];

    function detectQuizIntent(text) {
        const lowered = text.toLowerCase();
        return QUIZ_KEYWORDS.some(keyword => lowered.includes(keyword));
    }

    // Keep a short rolling memory of recent chat exchanges for context
    let recentContext = [];

    function pushContext(role, text) {
        recentContext.push({ role, text });
        if (recentContext.length > 6) recentContext.shift(); // keep last 6 messages
    }

    function extractTopicGuess(text) {
        // Very simple heuristic: strip common quiz trigger phrases to leave the topic behind
        let cleaned = text;
        QUIZ_KEYWORDS.forEach(k => {
            cleaned = cleaned.replace(new RegExp(k, "gi"), "");
        });
        cleaned = cleaned
            .replace(/\b(on|about|me|please|can you|could you|i want|generate|create|make|a|an|the)\b/gi, "")
            .replace(/\s{2,}/g, " ")
            .trim();
        return cleaned;
    }

    function showQuizConfirmCard(topicGuess) {
        const card = document.createElement("div");
        card.className = "chat";
        card.innerHTML = `
            <div id="ai-img">
                <img src="icons8-chatbot-100.png" />
            </div>
            <div class="chat-box ai quiz-confirm-card">
                <p>
                    <i class="fas fa-question-circle"></i>
                    It looks like you'd like a quiz${topicGuess ? ` on <strong>${escapeHtml(topicGuess)}</strong>` : ""}.
                    Want me to take you to the Quiz Generator with this context filled in?
                </p>
                <div class="quiz-confirm-actions">
                    <button class="quiz-confirm-yes"><i class="fas fa-check"></i> Yes, take me there</button>
                    <button class="quiz-confirm-no"><i class="fas fa-times"></i> No thanks</button>
                </div>
            </div>
        `;
        chatArea.appendChild(card);
        chatArea.scrollTop = chatArea.scrollHeight;

        card.querySelector(".quiz-confirm-yes").addEventListener("click", () => {
            const contextText = recentContext
                .map(c => `${c.role === "user" ? "Student" : "AI"}: ${c.text}`)
                .join("\n");

            sessionStorage.setItem("quizPrefillTopic", topicGuess || "");
            sessionStorage.setItem("quizPrefillContext", contextText);

            window.location.href = "quiz.php";
        });

        card.querySelector(".quiz-confirm-no").addEventListener("click", () => {
            card.remove();
        });
    }

    function escapeHtml(str) {
        if (!str) return "";
        return str.replace(/[&<>]/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[m]));
    }

    // Listen to every chat submission to track context and detect intent.
    // This runs in addition to script.js's own submit handler — it does NOT
    // intercept or prevent the normal chat flow, it only observes.
    form.addEventListener("submit", () => {
        const userText = textarea.value.trim();
        if (!userText) return;

        pushContext("user", userText);

        if (detectQuizIntent(userText)) {
            const topicGuess = extractTopicGuess(userText);

            // Wait for the AI's reply to finish rendering before showing the card.
            // We detect this by polling for the typing indicator to disappear.
            const waitForReply = setInterval(() => {
                const typing = document.getElementById("typing");
                if (!typing) {
                    clearInterval(waitForReply);
                    showQuizConfirmCard(topicGuess);
                }
            }, 400);

            // Safety timeout in case typing indicator never appears
            setTimeout(() => clearInterval(waitForReply), 15000);
        }
    });
});