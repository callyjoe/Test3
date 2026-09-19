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

    const prompt = `Generate 5 flashcards on the topic "${topic}". Format the response as a JSON array of objects like this:
    [
      {
        "question": "What is ...?",
        "answer": "...",
        "category": "Category",
        "difficulty": "easy|medium|hard"
      }
    ]`;

    const response = await queryAI(prompt);

    // ✅ Handle inappropriate topic (refusal)
    if (!response.trim().startsWith("[") || response.toLowerCase().includes("i cannot")) {
      showErrorPopup();
      return;
    }

    const jsonStart = response.indexOf("[");
    const jsonEnd = response.lastIndexOf("]") + 1;
    const jsonString = response.slice(jsonStart, jsonEnd);

    let cards = [];
    try {
      cards = JSON.parse(jsonString);
    } catch (e) {
      console.error("🛑 Failed to parse JSON:", e);
      console.error("🔍 Raw response was:", response);
      alert("The AI response could not be parsed. Try again.");
      return;
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

    reviewBtn.click(); // switch to review mode
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