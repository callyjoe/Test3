import { queryAI } from './api.js';

let lastPrompt = null;
let timerInterval = null;
let currentQuizData = null; // holds the originally generated quiz (with correct answers) so grading has something real to compare against

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("quiz-form");
  const loadingPopup = document.getElementById("loading-popup");
  const topicInput = document.getElementById("topic");
  const docContext = sessionStorage.getItem("documentContent");

  if (!form) {
    console.error("Critical element missing: #quiz-form not found");
    return;
  }

  if (docContext && topicInput) {
    topicInput.parentElement.style.display = "none";
    topicInput.disabled = true;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const questionArea = document.getElementById("question-area");

    const hiddenInputId = document.getElementById("selectedResourceId")?.value?.trim() || '';
    const sessionResourceId = sessionStorage.getItem('selectedResourceId')?.trim() || '';
    const resourceId = hiddenInputId || sessionResourceId || '';

    let resourceContent = sessionStorage.getItem('lecturerResourceContent') || '';
    let finalContext = docContext || '';

    if (resourceId && resourceContent) {
      finalContext = resourceContent;
    } else if (!finalContext) {
      finalContext = document.getElementById("topic")?.value.trim() || '';
    }

    const topic = document.getElementById("topic")?.value.trim() || '';
    sessionStorage.setItem("resourceTopic", topic);
    const type = document.getElementById("question-type")?.value;
    const num = document.getElementById("num-questions")?.value.replace(/\D/g, '');
    const difficulty = document.getElementById("difficulty")?.value;
    const instructions = document.getElementById("instructions")?.value;
    const timeString = document.getElementById("time-limit")?.value;
    const timeInMinutes = parseInt(timeString?.match(/\d+/)?.[0] || "0");

    if ((!finalContext && !topic && !resourceId) || !type || !num) {
      alert("Please fill out all required fields.");
      return;
    }

    if (!questionArea) {
      console.error("Critical element missing: #question-area not found");
      return;
    }

    if (resourceId) {
      sessionStorage.setItem('selectedResourceId', resourceId);
    }

    startLoadingSequence([
      "⏳ Generating your quiz... Please wait",
      "💡 Formatting questions...",
      "📦 Packaging quiz data...",
      "🚀 Launching your quiz!"
    ]);

    const prompt = buildPrompt(topic, type, num, difficulty, instructions, finalContext);
    lastPrompt = prompt;

    try {
      const result = await queryAI(prompt);
      const jsonText = extractJSON(result);
      const data = JSON.parse(jsonText);

      if (Array.isArray(data.theory)) {
        data.theory = data.theory.map(item =>
          typeof item === "string" ? { question: item } : item
        );
      }

      // Keep the original quiz (including correct answers for objectives) around for grading later
      currentQuizData = data;

      renderQuestions(data, type);
      switchToQuestionPage();
      startTimer(timeInMinutes * 60);
    } catch (err) {
      const area = document.getElementById("question-area");
      if (area) {
        area.innerHTML = `<p><i class='fas fa-exclamation-triangle'></i> Error loading quiz: ${err.message}</p>`;
      }
      console.error(err);
    } finally {
      if (loadingPopup) loadingPopup.style.display = "none";
    }
  });

  document.addEventListener("click", async (e) => {
    const id = e.target.id;
    const loadingPopup = document.getElementById("loading-popup");

    if (id === "submit-answers") {
      clearInterval(timerInterval);
      const answers = collectAnswers(currentQuizData);

      const totalQuestions = answers.objectives.length + answers.theory.length;
      if (totalQuestions === 0) {
        alert("Please attempt at least one question.");
        return;
      }

      const gradingPrompt = buildGradingPrompt(answers);

      startLoadingSequence([
        "📊 Analyzing your answers...",
        "🧠 Comparing with correct answers...",
        "✅ Generating feedback report..."
      ]);

      try {
        const feedback = await queryAI(gradingPrompt);
        const jsonText = extractJSON(feedback);
        const graded = JSON.parse(jsonText);

        const gradedObjectives = Array.isArray(graded.objectives) ? graded.objectives : [];
        const gradedTheory = Array.isArray(graded.theory) ? graded.theory : [];

        // Merge the AI's compact verdicts (index, isCorrect, explanation) back
        // with the full local question/answer data, so displayFeedback still
        // gets the same shape it always expected.
        const results = [
          ...answers.objectives.map((a, i) => {
            const g = gradedObjectives.find(g => g.index === i) || {};
            return {
              question: a.question,
              type: "objective",
              studentAnswer: a.studentAnswer,
              correctAnswer: a.correctAnswer,
              isCorrect: g.isCorrect === true,
              explanation: g.explanation || (a.attempted ? "No explanation returned." : "Not attempted.")
            };
          }),
          ...answers.theory.map((a, i) => {
            const g = gradedTheory.find(g => g.index === i) || {};
            return {
              question: a.question,
              type: "theory",
              studentAnswer: a.studentAnswer,
              correctAnswer: null,
              isCorrect: g.isCorrect === true,
              explanation: g.explanation || (a.attempted ? "No explanation returned." : "Not attempted.")
            };
          })
        ];

        const correctCount = results.filter(r => r.isCorrect === true).length;
        const total = results.length || totalQuestions;
        const score = total > 0 ? Math.round((correctCount / total) * 100) : 0;

        const quizTitle = document.getElementById("topic")?.value.trim() || 'Custom Quiz';
        const difficulty = document.getElementById("difficulty")?.value;
        const type = document.getElementById("question-type")?.value;
        const resourceId = sessionStorage.getItem('selectedResourceId')?.trim() || '';

        saveQuizResult(score, quizTitle, difficulty, type, answers, resourceId);
        displayFeedback(results, score);
      } catch (err) {
        const area = document.getElementById("question-area");
        if (area) {
          area.innerHTML += `<p><i class='fas fa-exclamation-triangle'></i> Could not generate feedback: ${err.message}</p>`;
        }
        console.error(err);
      } finally {
        if (loadingPopup) loadingPopup.style.display = "none";
      }
    }

    if (id === "retry-quiz" && lastPrompt) {
      startLoadingSequence(["Retrying quiz generation..."]);
      try {
        const result = await queryAI(lastPrompt);
        const jsonText = extractJSON(result);
        const data = JSON.parse(jsonText);

        if (Array.isArray(data.theory)) {
          data.theory = data.theory.map(item =>
            typeof item === "string" ? { question: item } : item
          );
        }

        currentQuizData = data;

        renderQuestions(data, document.getElementById("question-type")?.value);
        startTimer(parseInt(document.getElementById("time-limit")?.value.match(/\d+/)[0] || "0") * 60);
      } catch (err) {
        const area = document.getElementById("question-area");
        if (area) {
          area.innerHTML = `<p><i class='fas fa-exclamation-triangle'></i> Retry failed: ${err.message}</p>`;
        }
        console.error(err);
      } finally {
        if (loadingPopup) loadingPopup.style.display = "none";
      }
    }

    if (id === "back-to-form") {
      clearInterval(timerInterval);
      document.getElementById("question-page").style.display = "none";
      document.getElementById("form-page").style.display = "block";

      const area = document.getElementById("question-area");
      if (area) area.innerHTML = "";

      const countdownDisplay = document.getElementById("countdown-timer");
      if (countdownDisplay) countdownDisplay.textContent = "Time: 00:00";

      sessionStorage.removeItem('selectedResourceId');
      sessionStorage.removeItem('lecturerResourceContent');

      const topicInput = document.getElementById("topic");
      if (topicInput) {
        topicInput.disabled = false;
        topicInput.value = '';
        topicInput.placeholder = 'e.g., Calculus, Ghana History, Biology';
      }

      const hiddenInput = document.getElementById("selectedResourceId");
      if (hiddenInput) hiddenInput.value = '';

      currentQuizData = null; // clear out the old answer key so it can't bleed into the next quiz
    }
  });
});

// ===================== HELPER FUNCTIONS =====================

function buildPrompt(topic, type, num, level, instructions, context) {
  const extra = instructions ? ` Additional instructions: ${instructions}.` : "";
  const topicLine = context
    ? `Use the following material as the basis for the questions:\n\n${context}\n\n`
    : `Topic: "${topic}". `;
  const base = `${topicLine}Generate ${num} ${type.toLowerCase()} questions. Difficulty: ${level}.${extra}`;

  if (type === "Quiz") {
    return `${base} Return ONLY valid JSON like: { "theory": [ { "question": "..." } ] }. Do NOT include objectives or subquestions.`;
  } else if (type === "Mid-Sem" || type === "Exams") {
    return `${base} Include both objective and theory questions. Objective questions must include 4 options and an answer. Theory questions should be multipart. Return valid JSON:

{
  "objectives": [
    { "question": "...", "options": ["...","...","...","..."], "answer": "..." }
  ],
  "theory": [
    { "question": "...", "subquestions": ["...","..."] }
  ]
}`;
  }
  return base;
}

function extractJSON(text) {
  text = text.replace(/```json/g, "").replace(/```/g, "").trim();
  const start = text.indexOf("{");
  const end = text.lastIndexOf("}") + 1;
  if (start === -1 || end === 0) throw new Error("No JSON found");
  return text.slice(start, end);
}

function switchToQuestionPage() {
  const formPage = document.getElementById("form-page");
  const questionPage = document.getElementById("question-page");
  const timerBarWrapper = document.getElementById("timer-bar-wrapper");
  if (formPage && questionPage) {
    formPage.style.display = "none";
    questionPage.style.display = "block";
  }
  if (timerBarWrapper) timerBarWrapper.style.display = "block";
}

function renderQuestions(data, type = "Exams") {
  const area = document.getElementById("question-area");
  if (!area) return;
  area.innerHTML = "";

  if (type === "Quiz") {
    area.innerHTML += `<h3 class="quiz-header">Quiz Generated by Galorem AI</h3>`;
  }

  if (Array.isArray(data.objectives) && type !== "Quiz") {
    area.innerHTML += `<h3 class="section-header"><i class="fas fa-list-ul"></i> Objectives</h3>`;
    data.objectives.forEach((q, i) => {
      const options = q.options || [];
      area.innerHTML += `
        <div class="question-card fancy-objective">
          <p class="question-text">${i + 1}. ${q.question}</p>
          <div class="options">
            ${options.map(opt => `
              <label class="option-label">
                <input type="radio" name="q${i}" value="${opt}">
                <span class="custom-radio">${opt}</span>
              </label>
            `).join("")}
          </div>
        </div>
      `;
    });
  }

  if (Array.isArray(data.theory)) {
    if (type !== "Quiz") {
      area.innerHTML += `<h3 class="section-header"><i class="fas fa-paragraph"></i> Theory</h3>`;
    }
    data.theory.forEach((q, i) => {
      area.innerHTML += `<div class="theory-question"><p><strong>${i + 1}. ${q.question}</strong></p>`;
      if (Array.isArray(q.subquestions)) {
        q.subquestions.forEach((sub, j) => {
          area.innerHTML += `
            <p>${String.fromCharCode(97 + j)}. ${sub}</p>
            <textarea data-question="${sub}" rows="3" placeholder="Type your answer..."></textarea>
          `;
        });
      } else {
        area.innerHTML += `<textarea data-question="${q.question}" rows="4" placeholder="Type your answer..."></textarea>`;
      }
      area.innerHTML += `</div>`;
    });
  }

  area.innerHTML += `
    <div class="question-actions">
      <button id="submit-answers" class="generate-btn"><i class="fas fa-check-double"></i> Submit Answers</button>
      <button id="retry-quiz" class="generate-btn"><i class="fas fa-sync-alt"></i> Retry Quiz</button>
      <button id="back-to-form" class="generate-btn"><i class="fas fa-arrow-left"></i> Back to Form</button>
    </div>
    <div id="feedback-area"></div>
  `;
}

function startTimer(durationInSeconds) {
  const display = document.getElementById("countdown-timer");
  const fill = document.getElementById("timer-fill");
  if (!display || !fill) return;
  clearInterval(timerInterval);

  let timeLeft = durationInSeconds;
  const totalTime = durationInSeconds;

  function update() {
    const mins = String(Math.floor(timeLeft / 60)).padStart(2, "0");
    const secs = String(timeLeft % 60).padStart(2, "0");
    display.textContent = `Time: ${mins}:${secs}`;
    const percent = (timeLeft / totalTime) * 100;
    fill.style.width = `${percent}%`;
    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      alert("⏰ Time's up! Submitting your quiz.");
      document.getElementById("submit-answers")?.click();
    }
    timeLeft--;
  }
  update();
  timerInterval = setInterval(update, 1000);
}

// Pulls what the student actually submitted AND pairs each objective question
// with the correct answer from the originally generated quiz (quizData), so
// grading has real ground truth instead of nothing to compare against.
function collectAnswers(quizData) {
  const answers = { objectives: [], theory: [] };

  const objectiveCards = document.querySelectorAll('.question-card.fancy-objective');
  objectiveCards.forEach((card, i) => {
    const questionText = card.querySelector('.question-text')?.innerText || "";
    const selectedRadio = card.querySelector('input[type="radio"]:checked');
    const studentAnswer = selectedRadio ? selectedRadio.value : "Not answered";
    const sourceQuestion = quizData?.objectives?.[i];

    answers.objectives.push({
      question: questionText,
      options: sourceQuestion?.options || [],
      studentAnswer,
      correctAnswer: sourceQuestion?.answer ?? null,
      attempted: !!selectedRadio
    });
  });

  const theoryTextareas = document.querySelectorAll('textarea[data-question]');
  theoryTextareas.forEach((textarea) => {
    const question = textarea.dataset.question?.trim();
    const rawValue = textarea.value.trim();
    const studentAnswer = rawValue || "Not answered";
    if (question) {
      answers.theory.push({
        question,
        studentAnswer,
        attempted: rawValue.length > 0
      });
    }
  });

  return answers;
}

// Builds the grading prompt. Sends only the compact fields the model needs to
// make a judgment (index, studentAnswer, correctAnswer/question, attempted),
// and asks for a compact indexed verdict back — question text, options, and
// correct answers are NOT echoed back by the model, since we already have
// them locally. This keeps output short enough to avoid truncation on longer
// quizzes, and the caller merges the verdicts back with the full local data.
function buildGradingPrompt(answers) {
  const compactObjectives = answers.objectives.map((a, i) => ({
    index: i,
    studentAnswer: a.studentAnswer,
    correctAnswer: a.correctAnswer,
    attempted: a.attempted
  }));
  const compactTheory = answers.theory.map((a, i) => ({
    index: i,
    question: a.question,
    studentAnswer: a.studentAnswer,
    attempted: a.attempted
  }));

  return `You are a strict, accurate quiz grader for a student. You will be given JSON data describing objective (multiple-choice) and theory (open-ended) questions, along with what the student actually submitted.

RULES YOU MUST FOLLOW EXACTLY:
1. For objective questions, "correctAnswer" is the ground truth. Compare it to "studentAnswer". If "studentAnswer" is "Not answered" or "attempted" is false, the question is automatically incorrect — do not invent an answer the student did not give.
2. For theory questions there is no provided correct answer. Use your own subject knowledge to judge whether "studentAnswer" is substantively correct or reasonable. If "studentAnswer" is "Not answered" or "attempted" is false, the question is automatically incorrect, and the explanation should simply state the student did not attempt it.
3. Never claim a question was answered correctly unless the actual text in "studentAnswer" supports that.
4. Base every judgment only on the data provided below. Do not fabricate questions, answers, or scores.
5. Keep each explanation to ONE short sentence, no more than 20 words.
6. Do NOT repeat the question text, student answer, or correct answer in your response — only return the index, isCorrect, and explanation, exactly as shown below.

Return ONLY valid JSON, with no markdown fences and no extra commentary, in exactly this shape:
{
  "objectives": [ { "index": 0, "isCorrect": true, "explanation": "..." } ],
  "theory": [ { "index": 0, "isCorrect": true, "explanation": "..." } ]
}

Here is the data to grade:
${JSON.stringify({ objectives: compactObjectives, theory: compactTheory }, null, 2)}`;
}

function escapeHtmlSafe(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// Renders feedback directly from the structured "results" array returned by
// the grading step, instead of parsing emoji counts out of free-text.
function displayFeedback(results, score) {
  const area = document.getElementById("feedback-area");
  if (!area) return;
  area.innerHTML = "";

  const color = score >= 75 ? "limegreen" : score >= 50 ? "orange" : "red";
  const correct = results.filter(r => r.isCorrect === true).length;
  const incorrect = results.length - correct;
  const total = results.length;

  area.innerHTML += `
    <div class="feedback-summary">
      <h2><i class="fas fa-flag-checkered"></i> Quiz Completed!</h2>
      <div class="score-circle" style="color:${color}; border: 4px solid ${color};">${score}%</div>
      <div class="score-details">
        <div><i class="fas fa-check-circle"></i> <strong>${correct}</strong> Correct</div>
        <div><i class="fas fa-times-circle"></i> <strong>${incorrect}</strong> Incorrect</div>
        <div><i class="fas fa-question-circle"></i> <strong>${total}</strong> Total</div>
      </div>
    </div>
  `;

  results.forEach((r) => {
    const isCorrect = r.isCorrect === true;
    area.innerHTML += `
      <div class="feedback-card ${isCorrect ? 'correct' : 'incorrect'}">
        <h4>${escapeHtmlSafe(r.question)}</h4>
        <p><strong>Your answer:</strong> ${escapeHtmlSafe(r.studentAnswer)}</p>
        <p><strong>Correct answer:</strong> ${escapeHtmlSafe(r.correctAnswer ?? 'N/A')}</p>
        <p><strong>Explanation:</strong> ${escapeHtmlSafe(r.explanation)}</p>
        <span class="icon">${isCorrect ? "✅" : "❌"}</span>
      </div>
    `;
  });

  const topic = document.getElementById("topic")?.value.trim() || "";
  sessionStorage.setItem("resourceTopic", topic);

  const buttonContainer = document.createElement("div");
  buttonContainer.classList.add("resource-buttons");
  buttonContainer.innerHTML = `
    <button id="view-pdfs" class="generate-btn"><i class="fas fa-file-pdf"></i> View Recommended PDFs</button>
    <button id="view-videos" class="generate-btn"><i class="fab fa-youtube"></i> Watch Related Videos</button>
  `;
  area.appendChild(buttonContainer);

  document.getElementById("view-pdfs")?.addEventListener("click", () => {
    window.location.href = "Additional/Documents.html";
  });
  document.getElementById("view-videos")?.addEventListener("click", () => {
    window.location.href = "Additional/videos.html";
  });
}

function startLoadingSequence(messages, delay = 2000) {
  const popup = document.getElementById("loading-popup");
  const textEl = document.getElementById("loading-text");
  if (!popup || !textEl) return;
  let index = 0;
  popup.style.display = "flex";
  textEl.textContent = messages[index];
  const interval = setInterval(() => {
    index++;
    if (index >= messages.length) clearInterval(interval);
    else textEl.textContent = messages[index];
  }, delay);
}

function saveQuizResult(score, quizTitle, difficulty, type, answers, resourceId) {
  const answersJson = JSON.stringify(answers);
  const body = [
    `score=${score}`,
    `quiz_title=${encodeURIComponent(quizTitle)}`,
    `difficulty=${encodeURIComponent(difficulty)}`,
    `type=${encodeURIComponent(type)}`,
    `answers_json=${encodeURIComponent(answersJson)}`,
    `resource_id=${encodeURIComponent(resourceId || '')}`
  ].join('&');

  fetch('save_quiz_result.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  })
  .then(res => res.json())
  .then(saveResult => {
    if (saveResult.status !== 'success') {
      console.warn('Failed to save quiz result:', saveResult.message);
    } else {
      console.log('Quiz saved, record ID:', saveResult.record_id);
    }
  })
  .catch(err => console.error('Save error:', err));
}