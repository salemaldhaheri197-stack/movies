# Movie & TV Voice Agent

A browser-based voice agent for asking about movies and TV episodes, powered by
[ElevenLabs Conversational AI](https://elevenlabs.io) for voice + [TMDb](https://www.themoviedb.org/) for data.

## How it works

1. The browser (`index.html` + `app.js`) asks our own PHP backend for a short-lived
   **signed URL** — the real ElevenLabs API key never reaches the browser.
2. It opens a voice session with the ElevenLabs agent using that signed URL.
3. When you ask something like "What happens in Breaking Bad season 3 episode 7?",
   the agent calls a **server tool** (`api/get_episode.php`) that looks up the real
   answer from TMDb and returns it, which the agent then speaks back to you.

## Setup

1. **Copy the env template**
   ```
   cp .env.example .env
   ```
   Fill in `ELEVENLABS_API_KEY`, `ELEVENLABS_AGENT_ID`, and `TMDB_API_KEY` in `.env`.
   Never commit `.env` — it's already in `.gitignore`.

2. **ElevenLabs agent setup**
   - Create a Conversational AI agent at elevenlabs.io.
   - Give it a system prompt telling it to answer movie/TV questions using its tool.
   - Add a **Server Tool** pointing to `https://yourdomain.com/api/get_episode.php`,
     with params `type`, `show`/`title`, `season`, `episode` as needed.

3. **Deploy**
   - Host `index.html`, `style.css`, `app.js` anywhere static (e.g. GitHub Pages).
   - Host the `api/` PHP files on a PHP-capable server (e.g. your existing hosting).
   - Update `BACKEND_URL` in `app.js` if frontend and backend are on different domains.

4. **Run locally**
   - Serve the static files with any simple server (e.g. `npx serve .`).
   - Point PHP files at a local PHP server (`php -S localhost:8000 -t api`) for testing.

## Files

- `index.html`, `style.css`, `app.js` — frontend voice UI
- `api/get_signed_url.php` — issues a short-lived session URL (keeps API key server-side)
- `api/get_episode.php` — tool endpoint the agent calls for real movie/episode data
- `.env.example` — template for required secrets (copy to `.env`, fill in, never commit)
