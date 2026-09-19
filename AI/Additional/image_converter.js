import { refineDocument } from "../api.js";

// ========== DROP AREA CLICK ==========
document.getElementById("drop-area").addEventListener("click", () => {
  document.getElementById("fileElem").click();
});

// ========== FILE SELECTED ==========
document.getElementById("fileElem").addEventListener("change", handleUpload);

async function handleUpload() {
  const file = document.getElementById("fileElem").files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append("image", file);

  document.getElementById("loading").classList.remove("hidden");
  document.getElementById("output-section").classList.add("hidden");

  try {
    // ── Step 1: Send image to OCR ──────────────────────────────
    const res = await fetch("image_converter.php", {
      method: "POST",
      body: formData,
    });

    const data = await res.json();

    if (data.error) {
      alert("OCR Error: " + data.error);
      document.getElementById("loading").classList.add("hidden");
      return;
    }

    const rawText = data.text || "";

    if (!rawText.trim()) {
      alert("No text could be extracted from the image. Try a clearer image.");
      document.getElementById("loading").classList.add("hidden");
      return;
    }

    // ── Step 2: Refine with AI ─────────────────────────────────
    const prompt =
      `You are a note formatting assistant. Reformat the following extracted text into clean, well-structured study notes.\n\n` +
      `Rules:\n` +
      `- Use # for the main title\n` +
      `- Use ## for major sections\n` +
      `- Use ### for subsections\n` +
      `- Use **word** for important terms — write the word ONCE inside the asterisks, do not write it again outside\n` +
      `- Use - for bullet points\n` +
      `- Use numbered lists where order matters\n` +
      `- Return ONLY the formatted notes, no explanation or preamble\n\n` +
      `Text to format:\n` +
      rawText;

    console.log("Sending to AI, length:", prompt.length);

    const refined = await refineDocument(prompt);

    console.log("AI returned:", refined);

    if (!refined) {
      alert("The AI could not refine the text. The extracted text will be used as-is.");
      // Fall back to raw OCR text so at least something downloads
      document.getElementById("refinedText").value = rawText.trim();
      document.getElementById("loading").classList.add("hidden");
      document.getElementById("output-section").classList.remove("hidden");
      return;
    }

    // ── Step 3: Display result ─────────────────────────────────
    document.getElementById("refinedText").value = refined.trim();
    document.getElementById("loading").classList.add("hidden");
    document.getElementById("output-section").classList.remove("hidden");

  } catch (err) {
    console.error("handleUpload error:", err);
    alert("Upload error: " + err.message);
    document.getElementById("loading").classList.add("hidden");
  }
}

// ========== GENERATE WORD DOCUMENT ==========
async function generateWord() {
  const text = document.getElementById("refinedText").value.trim();

  if (!text) {
    alert("No text to convert.");
    return;
  }

  try {
    const res = await fetch("generate_word.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ content: text }),
    });

    const contentType = res.headers.get("Content-Type");

    if (!res.ok || !contentType.includes("wordprocessingml")) {
      const errData = await res.json();
      alert("Error generating document: " + (errData.error || "Unknown error"));
      return;
    }

    const blob = await res.blob();
    const url  = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href     = url;
    link.download = "converted_notes.docx";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

  } catch (err) {
    alert("Download error: " + err.message);
  }
}

document.getElementById("generate-word").addEventListener("click", generateWord);