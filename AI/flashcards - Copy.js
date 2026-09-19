import { queryAI } from './api.js';

let flashcards = [];
let currentIndex = 0;
let correctCount = 0;
let incorrectCount = 0;

document.addEventListener("DOMContentLoaded", () => {
  const reviewBtn = document.getElementById("review-mode");
  const createBtn = document.getElementById("create-mode");
  const createPage = document.getElementById("create-page");
  const reviewPage = document.getElementById("review-page");

  function showErrorPopup() {
    const popup = document.getElementById("error-popup");
    popup.style.display = "flex";
    document.getElementById("close-error").onclick = () => {
      popup.style.display = "none";
    };
  }

  reviewBtn.onclick = () => {
    reviewBtn.classList.add("active");
    createBtn.classList.remove("active");
    createPage.style.display = "none";
    reviewPage.style.display = "block";
    updateStats();
    showCard();
  };

  createBtn.onclick = () => {
    reviewBtn.classList.remove("active");
    createBtn.classList.add("active");
    createPage.style.display = "block";
    reviewPage.style.display = "none";
  };

  document.getElementById("flashcard-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    document.getElementById("loading-overlay").style.display = "flex";

    try {
      const topic = document.getElementById("flashcard-topic").value.trim();
      const difficulty = document.getElementById("flashcard-difficulty").value;
      const category = document.getElementById("flashcard-category").value.trim();

      const prompt = `Create exactly 5 flashcards on the topic "${topic}".
STRICT RULES:
- Return ONLY a valid JSON array, no text before or after
- Each value must use exactly ONE set of double quotes, like "value"
- NEVER use double double-quotes like ""value""
- No markdown, no bold, no asterisks, no backticks
- Answers must be one plain sentence only
- No commas inside answer values

Return this exact format:
[
  {
    "question": "write question here",
    "answer": "write answer here",
    "category": "${category}",
    "difficulty": "${difficulty}"
  }
]`;

      const response = await queryAI(prompt);

      // ✅ Handle refusal
      if (!response.trim().includes("[") || response.toLowerCase().includes("i cannot")) {
        showErrorPopup();
        return;
      }

      // ✅ Extract raw JSON array from response
      const match = response.match(/\[[\s\S]*\]/);
      if (!match) {
        console.error("🛑 No JSON array found in response.");
        alert("Could not extract flashcards. Try again.");
        return;
      }

      let jsonString = match[0];

      // ✅ Step 1: Normalize all whitespace
      jsonString = jsonString.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

      // ✅ Step 2: Remove ALL double double-quotes aggressively
      // Replace ""sometext"" with "sometext"
      jsonString = jsonString.replace(/""+([^"]*)""+/g, '"$1"');

      // ✅ Step 3: Remove any remaining consecutive quotes
      jsonString = jsonString.replace(/""{2,}/g, '"');

      // ✅ Step 4: Strip markdown
      jsonString = jsonString
        .replace(/\*\*/g, '')
        .replace(/`/g, '');

      let cards = [];
      try {
        cards = JSON.parse(jsonString);
      } catch (e) {
        // ✅ Last resort: manually extract fields using regex
        try {
          cards = manualExtract(response, category, difficulty);
          if (cards.length === 0) throw new Error("Manual extract failed");
        } catch (e2) {
          console.error("🛑 Failed to parse JSON:", e2);
          console.error("🔍 JSON string was:", jsonString);
          alert("The AI response could not be parsed. Try again.");
          return;
        }
      }

      flashcards = cards.map(c => ({
        question: c.question,
        answer: c.answer,
        category: c.category || category,
        difficulty: c.difficulty || difficulty
      }));

      currentIndex = 0;
      correctCount = 0;
      incorrectCount = 0;

      reviewBtn.click();
    } catch (err) {
      alert("⚠️ Failed to generate flashcards.");
      console.error(err);
    } finally {
      document.getElementById("loading-overlay").style.display = "none";
    }
  });

  document.getElementById("flip-card").onclick = () => {
    document.getElementById("flashcard").classList.toggle("flipped");
  };

  document.getElementById("prev-card").onclick = () => {
    if (currentIndex > 0) currentIndex--;
    showCard();
  };

  document.getElementById("next-card").onclick = () => {
    if (currentIndex < flashcards.length - 1) currentIndex++;
    showCard();
  };

  document.getElementById("mark-correct").onclick = () => {
    correctCount++;
    currentIndex++;
    showCard();
  };

  document.getElementById("mark-wrong").onclick = () => {
    incorrectCount++;
    currentIndex++;
    showCard();
  };
});

// ✅ Manual extraction fallback — pulls question/answer directly with regex
function manualExtract(text, category, difficulty) {
  const cards = [];
  // Match each object block
  const blocks = text.match(/\{[\s\S]*?\}/g);
  if (!blocks) return cards;

  blocks.forEach(block => {
    // Extract question and answer values regardless of quote style
    const questionMatch = block.match(/"question"\s*:\s*"+([^"]+)"+/);
    const answerMatch = block.match(/"answer"\s*:\s*"+([^"]+)"+/);

    if (questionMatch && answerMatch) {
      cards.push({
        question: questionMatch[1].trim(),
        answer: answerMatch[1].trim(),
        category,
        difficulty
      });
    }
  });

  return cards;
}

function showCard(index = currentIndex) {
  const card = flashcards[index];
  if (!card) return;

  currentIndex = index;
  const front = document.getElementById("card-front");
  const back = document.getElementById("card-back");

  front.textContent = card.question;
  back.textContent = card.answer;

  document.getElementById("card-category").textContent = card.category;
  document.getElementById("difficulty-label").textContent = card.difficulty;
  document.getElementById("flashcard").classList.remove("flipped");
  document.getElementById("card-count").textContent = `${index + 1} of ${flashcards.length}`;

  updateStats();
}

function updateStats() {
  document.getElementById("stat-total").textContent = flashcards.length;
  document.getElementById("stat-correct").textContent = correctCount;
  document.getElementById("stat-incorrect").textContent = incorrectCount;

  const categories = [...new Set(flashcards.map(f => f.category))];
  document.getElementById("stat-category").textContent = categories.length;
}