// app.js
// Loads the ElevenLabs Conversation client from esm.sh (no build step needed),
// fetches a short-lived signed URL from our own backend (real API key stays server-side),
// and starts/stops a voice conversation with the movie/TV agent.

import { Conversation } from "https://esm.sh/@elevenlabs/client";

const talkBtn = document.getElementById("talkBtn");
const statusEl = document.getElementById("status");
const transcriptEl = document.getElementById("transcript");

// Point this at wherever get_signed_url.php is deployed.
// If frontend and backend are on the same domain, a relative path is fine.
const BACKEND_URL = "/api/get_signed_url.php";

let conversation = null;
let isActive = false;

function addLine(role, text) {
  const div = document.createElement("div");
  div.className = `line ${role}`;
  div.textContent = `${role === "user" ? "You" : "Agent"}: ${text}`;
  transcriptEl.appendChild(div);
  transcriptEl.scrollTop = transcriptEl.scrollHeight;
}

async function getSignedUrl() {
  const res = await fetch(BACKEND_URL);
  if (!res.ok) throw new Error("Failed to fetch signed URL from backend");
  const data = await res.json();
  if (!data.signed_url) throw new Error(data.error || "No signed_url returned");
  return data.signed_url;
}

async function startConversation() {
  try {
    statusEl.textContent = "Connecting...";
    const signedUrl = await getSignedUrl();

    conversation = await Conversation.startSession({
      signedUrl,
      onConnect: () => {
        statusEl.textContent = "Listening... ask about a movie or episode";
        talkBtn.textContent = "🛑 Stop";
        talkBtn.classList.add("active");
        isActive = true;
      },
      onDisconnect: () => {
        statusEl.textContent = "Idle";
        talkBtn.textContent = "🎙️ Start Talking";
        talkBtn.classList.remove("active");
        isActive = false;
      },
      onMessage: (msg) => {
        // msg shape depends on SDK version; commonly { source: 'user' | 'ai', message: string }
        if (msg?.message) {
          addLine(msg.source === "user" ? "user" : "agent", msg.message);
        }
      },
      onError: (err) => {
        console.error("Conversation error:", err);
        statusEl.textContent = "Error — check console";
      },
    });
  } catch (err) {
    console.error(err);
    statusEl.textContent = "Failed to start: " + err.message;
  }
}

async function stopConversation() {
  if (conversation) {
    await conversation.endSession();
    conversation = null;
  }
}

talkBtn.addEventListener("click", () => {
  if (isActive) {
    stopConversation();
  } else {
    startConversation();
  }
});
