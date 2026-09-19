// Cloudflare API key
const apiKey = "COE44lUR8WR95rvozEaRl8JkSR9ocT6U";

// System prompt
const systemPrompt = `
You are Galorem AI, a smart academic assistant helping university students.

Your goals:
- Format responses clearly and neatly.
- Use bullet points or numbered steps when applicable.
- Use short, clear paragraphs.
- Structure answers based on context (definition, comparison, guide, etc).
- Add spacing between sections.

Never repeat yourself or give overly long answers. Keep responses organized and friendly.
`;

// 🧠 Temporary memory
let conversationMemory = [];
let lastMeaningfulInput = "";

// --------------------
// Main Chat Function
// --------------------
export async function fetchAIResponse(userInput) {
  const lowered = userInput.toLowerCase().trim();

  // Document-based prompt
  if (
    userInput.startsWith("Based on the following document content:") ||
    userInput.includes("Document content:")
  ) {
    try {
      return await callTogetherAI([
        { role: "system", content: systemPrompt },
        { role: "user", content: userInput }
      ]);
    } catch (err) {
      console.error(err);
      return "❌ Error: Couldn't connect. Please try again.";
    }
  }

  // Greetings / thanks / praise
  const greetings = ["hi", "hello", "hey", "good morning", "good afternoon", "good evening","good day","whatsapp","wossop","gm"];
  const thanks = ["thank you","thanks","appreciate it","grateful","thank u","tnx","thank"];
  const praise = ["you're the best","you’re good","nice work","awesome","great job","i love you"];

  if (greetings.some(g => lowered.includes(g))) return "👋 Hello! How can I assist you today? I'm here for anything academic!";
  if (thanks.some(t => lowered.includes(t)) || praise.some(t => lowered.includes(t))) return "😊 You're very welcome! Let me know what you need help with next!";

  // Follow-up handling
  const isFollowUp = lowered.startsWith("what about") || lowered.startsWith("how about") || lowered.startsWith("and ") || lowered === "what about linux";
  const combinedInput = isFollowUp && lastMeaningfulInput ? `${lastMeaningfulInput} Also, ${userInput}` : userInput;
  if (!isFollowUp && lowered.length > 10) lastMeaningfulInput = userInput;

  // Add to memory (limit last 3 messages)
  conversationMemory.push({ role: "user", content: combinedInput });
  if (conversationMemory.length > 3) conversationMemory.shift();

  const messages = [
    { role: "system", content: systemPrompt },
    ...conversationMemory.map(entry => ({ role: "user", content: entry.content }))
  ];

  try {
    const aiMessage = await callTogetherAI(messages);

    // Save assistant reply
    conversationMemory.push({ role: "assistant", content: aiMessage });
    if (conversationMemory.length > 3) conversationMemory.shift();

    return aiMessage;
  } catch (err) {
    console.error(err);
    return "❌ Error: Couldn't connect. Please try again.";
  }
}

// --------------------
// Quiz / Flashcard Function
// --------------------
export async function queryAI(prompt) {
  const messages = [
    { role: "system", content: systemPrompt },
    { role: "user", content: prompt }
  ];

  return await callTogetherAI(messages, 3000); // increased tokens; throws on failure so callers can handle real errors
}

// --------------------
// Refine extracted text (OCR / raw document text) into clean notes
// --------------------
export async function refineDocument(prompt) {
  const messages = [
    { role: "system", content: systemPrompt },
    { role: "user", content: prompt }
  ];

  return await callTogetherAI(messages, 1500);
}

// --------------------
// Helper: Call Together AI
// --------------------
async function callTogetherAI(messages, maxTokens = 800) {
  const response = await fetch("https://api.mistral.ai/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${apiKey}`
    },
    body: JSON.stringify({
      model: "open-mistral-7b",
      messages,
      temperature: 0.7,
      max_tokens: maxTokens // uses passed value
    }),
  });

  if (!response.ok) {
    const errData = await response.json().catch(() => ({}));
    console.error("API error:", errData);
    throw new Error(`Mistral API error (${response.status}): ${errData?.message || 'unknown error'}`);
  }

  const data = await response.json();
  const content = data.choices?.[0]?.message?.content?.trim();

  if (!content) {
    throw new Error("Empty response from Mistral");
  }

  return content;
}