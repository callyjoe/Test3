// ===================== INLINE CHAT FILE UPLOAD =====================
// Handles the "+" button in the chat textarea: lets the user upload
// either an image (OCR'd via image_converter.php) or a document
// (extracted via upload.php), storing the result in sessionStorage
// as "documentContent" so script.js automatically uses it as context.

document.addEventListener("DOMContentLoaded", () => {
    const plusBtn        = document.getElementById("plusBtn");
    const uploadMenu      = document.getElementById("uploadMenu");
    const uploadImageOpt   = document.getElementById("uploadImageOption");
    const uploadDocOpt      = document.getElementById("uploadDocOption");
    const imageFileInput     = document.getElementById("imageFileInput");
    const docFileInput        = document.getElementById("docFileInput");

    const uploadProgress       = document.getElementById("uploadProgress");
    const uploadProgressText    = document.getElementById("uploadProgressText");
    const cancelUploadBtn        = document.getElementById("cancelUploadBtn");

    const attachedChip            = document.getElementById("attachedChip");
    const attachedFileName         = document.getElementById("attachedFileName");
    const removeChipBtn             = document.getElementById("removeChipBtn");
    const generateQuizBtn            = document.getElementById("generateQuizBtn");

    if (!plusBtn) return; // Safety check in case script loads on a page without these elements

    let currentUploadController = null;

    // ⚠️ Update this to your actual quiz page filename/path
    const QUIZ_PAGE_PATH = "quiz.php";

    // ── Re-show the chip if a document is already attached from a
    // previous action (e.g. user navigated away and back) ───────
    const existingDoc = sessionStorage.getItem("documentContent");
    if (existingDoc) {
        showAttachedChip("Attached document");
    }

    // ── Toggle the upload menu ──────────────────────────────────
    plusBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        uploadMenu.classList.toggle("show");
        plusBtn.classList.toggle("open");
    });

    document.addEventListener("click", (e) => {
        if (!uploadMenu.contains(e.target) && !plusBtn.contains(e.target)) {
            uploadMenu.classList.remove("show");
            plusBtn.classList.remove("open");
        }
    });

    // ── Menu option clicks trigger the relevant hidden input ────
    uploadImageOpt.addEventListener("click", () => {
        uploadMenu.classList.remove("show");
        plusBtn.classList.remove("open");
        imageFileInput.click();
    });

    uploadDocOpt.addEventListener("click", () => {
        uploadMenu.classList.remove("show");
        plusBtn.classList.remove("open");
        docFileInput.click();
    });

    // ── File selected: image ─────────────────────────────────────
    imageFileInput.addEventListener("change", () => {
        if (imageFileInput.files.length > 0) {
            handleImageUpload(imageFileInput.files[0]);
        }
        imageFileInput.value = ""; // reset so same file can be re-selected later
    });

    // ── File selected: document ──────────────────────────────────
    docFileInput.addEventListener("change", () => {
        if (docFileInput.files.length > 0) {
            handleDocumentUpload(docFileInput.files[0]);
        }
        docFileInput.value = "";
    });

    // ── Cancel an in-progress upload ─────────────────────────────
    cancelUploadBtn.addEventListener("click", () => {
        if (currentUploadController) {
            currentUploadController.abort();
        }
        hideProgress();
    });

    // ── Remove the attached file chip (clears stored context) ───
    removeChipBtn.addEventListener("click", () => {
        sessionStorage.removeItem("documentContent");
        attachedChip.classList.remove("show");
    });

    // ── Redirect to quiz page using the currently attached document ─
    if (generateQuizBtn) {
        generateQuizBtn.addEventListener("click", () => {
            const docContext = sessionStorage.getItem("documentContent");
            if (!docContext) {
                alert("No document is currently attached.");
                return;
            }
            window.location.href = QUIZ_PAGE_PATH;
        });
    }

    function showProgress(text) {
        uploadProgressText.textContent = text;
        uploadProgress.classList.add("show");
    }

    function hideProgress() {
        uploadProgress.classList.remove("show");
    }

    function showAttachedChip(filename) {
        attachedFileName.textContent = filename;
        attachedChip.classList.add("show");
    }

    // ── IMAGE UPLOAD: OCR via image_converter.php ────────────────
    async function handleImageUpload(file) {
        attachedChip.classList.remove("show");
        sessionStorage.removeItem("documentContent");
        showProgress("Reading image (OCR)...");

        currentUploadController = new AbortController();

        const formData = new FormData();
        formData.append("image", file);

        try {
            const res = await fetch("Additional/image_converter.php", {
                method: "POST",
                body: formData,
                signal: currentUploadController.signal
            });
            const data = await res.json();

            if (data.error) {
                hideProgress();
                alert("OCR Error: " + data.error);
                return;
            }

            const rawText = (data.text || "").trim();

            if (!rawText) {
                hideProgress();
                alert("No text could be extracted from the image.");
                return;
            }

            showProgress("Refining extracted text...");

            // Reuse refineDocument from api.js for cleanup, same as the image converter page.
            // If refining fails for any reason, fall back to the raw OCR text instead of
            // losing the upload entirely.
            const { refineDocument } = await import("./api.js");
            let finalContent = rawText;

            try {
                const refined = await refineDocument(
                    `Refine this extracted text from an image into clean, well-structured notes.\n\nText:\n${rawText}`
                );
                if (refined) finalContent = refined;
            } catch (refineErr) {
                console.warn("Refine failed, using raw OCR text instead:", refineErr);
            }

            sessionStorage.setItem("documentContent", finalContent);
            hideProgress();
            showAttachedChip(file.name);
            notifyAIAboutUpload();

        } catch (err) {
            hideProgress();
            if (err.name !== "AbortError") {
                alert("Image upload failed: " + err.message);
                console.error(err);
            }
        }
    }

    // ── DOCUMENT UPLOAD: text extraction via upload.php ──────────
    async function handleDocumentUpload(file) {
        attachedChip.classList.remove("show");
        sessionStorage.removeItem("documentContent");
        showProgress("Processing document...");

        currentUploadController = new AbortController();

        const formData = new FormData();
        formData.append("document", file);

        try {
            const res = await fetch("upload.php?nocache=" + Date.now(), {
                method: "POST",
                body: formData,
                signal: currentUploadController.signal
            });
            const data = await res.text();

            if (!data || data.trim() === "") {
                hideProgress();
                alert("Could not extract content from this document.");
                return;
            }

            sessionStorage.setItem("documentContent", data);
            hideProgress();
            showAttachedChip(file.name);
            notifyAIAboutUpload();

        } catch (err) {
            hideProgress();
            if (err.name !== "AbortError") {
                alert("Document upload failed: " + err.message);
                console.error(err);
            }
        }
    }

    // ── Tell the chat an upload just landed, so it can greet ────
    // Dispatches a custom event that script.js can optionally listen for.
    // Also directly triggers an inline AI intro message if fetchAIResponse is available.
    async function notifyAIAboutUpload() {
        const chatArea = document.querySelector(".chat-scroll-area");
        if (!chatArea) return;

        try {
            const { fetchAIResponse } = await import("./api.js");
            const docContext = sessionStorage.getItem("documentContent");
            if (!docContext) return;

            // Typing bubble
            const typing = document.createElement("div");
            typing.classList.add("typing-indicator");
            typing.setAttribute("id", "upload-typing");
            typing.innerHTML = `
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            chatArea.appendChild(typing);
            chatArea.scrollTop = chatArea.scrollHeight;

            const introPrompt = `You are an AI study assistant. The user just uploaded a file in the chat. Read this content and reply with a short friendly message like: "I've reviewed your file. You can now ask questions related to it."\n\nContent:\n${docContext}`;

            const introReply = await fetchAIResponse(introPrompt);

            const existingTyping = document.getElementById("upload-typing");
            if (existingTyping) existingTyping.remove();

            const aiReply = document.createElement("div");
            aiReply.className = "chat";
            aiReply.innerHTML = `
                <div id="ai-img">
                    <img src="icons8-chatbot-100.png" />
                </div>
                <div class="chat-box ai">
                    <p>${introReply.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</p>
                    <span class="timestamp">${new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}</span>
                </div>
            `;
            chatArea.appendChild(aiReply);
            chatArea.scrollTop = chatArea.scrollHeight;

        } catch (err) {
            console.error("Failed to notify AI about upload:", err);
        }
    }
});