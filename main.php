<?php
/* main.php
   ChatGoD V1 — updated UI per user request:
   - Solid color themes (no gradients) with clear separators for header / chat / input
   - Modern font (Exo 2)
   - Language toggle (EN | HI) in header (replaces theme icon)
   - Global theme cycle button bottom-left (separate from widget)
   - 3D robot avatar in header (Three.js)
   - Slide-up open and slide-down close animations (no simple fade)
   - Lowered bottom spacing for a closer-to-bottom look
   - Reduced font sizes for better readability
   - Improved send button with icon and better interaction
   - Small time widget under chats (updates every 30s)
*/
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Gyan sarthi V1 — UI</title>

  <!-- Exo 2 font -->
  <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- libs -->
  <script type="module" src="https://unpkg.com/three@0.154.0/build/three.module.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

  <style>
    :root{
      --accent-default: #2563eb; /* blue */
      --radius: 12px;
      --z-widget: 2147483647;
    }

    /* widget root */
    .my-ai{
      --bg: #0b1220;
      --header-bg: #0f1724;   /* solid header color */
      --messages-bg: #071126; /* solid chat background */
      --input-bg: #0b1320;    /* solid input bg */
      --accent: var(--accent-default);
      --muted: rgba(255,255,255,0.65);
      --text: #e6eef8;
      --user-bg: #34d399;     /* user bubble (solid) */
      --ai-bg: rgba(255,255,255,0.02);
      position: fixed;
      right: 8px;             /* lowered bottom/right spacing */
      bottom: 8px;
      z-index: var(--z-widget);
      font-family: 'Exo 2', system-ui, -apple-system, "Segoe UI", Roboto, 'Helvetica Neue', Arial;
      font-size: 13px;        /* slightly smaller for readability density */
      width: auto;
      --widget-width: 360px;
      --widget-height: 500px;
    }

    *{box-sizing:border-box;margin:0;padding:0}
    body{margin:0;background:transparent}

    /* bottom-left separate theme toggle (keeps theme control outside widget) */
    #globalThemeBtn{
      position: fixed;
      left: 12px; bottom: 12px;
      width: 46px; height: 46px; border-radius: 10px;
      display:grid; place-items:center; cursor:pointer;
      background: var(--accent-default); color: white; border: none;
      font-weight:700; z-index: var(--z-widget);
      box-shadow: 0 8px 20px rgba(2,6,23,0.45);
    }

    /* launcher - floating circle */
    .chat-launcher{
      width: 64px; height: 64px; border-radius: 999px;
      display:grid; place-items:center; cursor:pointer;
      background: var(--accent);
      box-shadow: 0 10px 28px rgba(2,6,23,0.45);
      transition: transform 220ms ease;
    }
    .chat-launcher:focus{outline:2px solid rgba(255,255,255,0.06)}
    .chat-launcher:hover{transform: translateY(-4px) scale(1.02)}
    /* ChatGoD — correction / suggestion / feedback button styles
      No CSS variables used — paste into your widget stylesheet or <style> tag. */

    /* Base color palette (explicit values) */
    :where(.cg-style-placeholder) { /* no-op selector to document palette in-file */
      /* accent: #2563eb; user-bg: #34d399; messages-bg: #071126; text: #ffffff;
        muted: rgba(255,255,255,0.72); danger: #ef4444; success: #10b981;
        surface: rgba(255,255,255,0.04); radius: 12px; */
    }

    /* Base pill button used for suggestions & corrections */
    .lang-cap,
    .suggestion-btn,
    .qa-btn,
    .accept-correction,
    .ignore-correction {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      font-size: 13px;
      line-height: 1;
      border-radius: 999px;
      cursor: pointer;
      user-select: none;
      border: 1px solid transparent;
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      color: #ffffff;
      box-shadow: 0 4px 10px rgba(2,6,23,0.45);
      transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
      white-space: nowrap;
    }

    /* Primary actionable pill (accept corrected query / primary suggestions) */
    .accept-correction,
    .suggestion-btn {
      background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(0,0,0,0.06));
      border: 1px solid rgba(255,255,255,0.06);
    }
    .accept-correction {
      background-color: #2563eb; /* accent */
      color: #ffffff;
      border-color: rgba(255,255,255,0.08);
      box-shadow: 0 6px 18px rgba(37,99,235,0.18);
      font-weight: 600;
    }

    /* Secondary (less-prominent) pill for ignoring corrections */
    .ignore-correction {
      background: transparent;
      color: rgba(255,255,255,0.72); /* muted */
      border: 1px dashed rgba(255,255,255,0.06);
      font-weight: 500;
    }

    /* QA buttons (question suggestions) — slightly larger to stand out */
    .qa-btn {
      padding: 10px 14px;
      font-size: 13px;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border: 1px solid rgba(255,255,255,0.04);
      color: #ffffff;
    }

    /* Hover / active states */
    .lang-cap:hover,
    .suggestion-btn:hover,
    .qa-btn:hover,
    .accept-correction:hover,
    .ignore-correction:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(2,6,23,0.45);
      opacity: 0.98;
    }

    /* Focus accessibility */
    .lang-cap:focus,
    .suggestion-btn:focus,
    .qa-btn:focus,
    .accept-correction:focus,
    .ignore-correction:focus,
    .feedback-btn:focus {
      outline: none;
      box-shadow: 0 0 0 4px rgba(37,99,235,0.16); /* subtle blue focus ring */
      transform: translateY(-2px);
    }

    /* Feedback small circular buttons (thumbs up / down) */
    .feedback-btn {
      border: none;
      background: #f3f4f6;
      color: #888;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      font-size: 18px;
      cursor: pointer;
      transition: background 0.2s, color 0.2s, box-shadow 0.2s;
      position: relative;
      outline: none;
    }
    .feedback-btn:hover {
      background: #e0e7ef;
      color: #2563eb;
      box-shadow: 0 2px 8px rgba(37,99,235,0.08);
    }
    .feedback-btn:active {
      background: #2563eb;
      color: #fff;
      box-shadow: 0 2px 12px rgba(37,99,235,0.18);
    }
    .feedback-btn.selected {
      background: #22c55e;
      color: #fff;
      box-shadow: 0 0 0 2px #22c55e;
    }
    .feedback-btn .tick {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 14px;
      height: 14px;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .feedback-btn.selected .tick {
      opacity: 1;
      animation: tickPop 0.5s cubic-bezier(.68,-0.55,.27,1.55);
    }
    @keyframes tickPop {
      0% { transform: scale(0.2); opacity: 0; }
      60% { transform: scale(1.2); opacity: 1; }
      100% { transform: scale(1); opacity: 1; }
    }

    /* Small responsive tweak for tiny screens */
    @media (max-width:420px) {
      .lang-cap, .suggestion-btn, .qa-btn, .accept-correction, .ignore-correction {
        padding: 8px 10px;
        font-size: 12px;
      }
      .feedback-btn { width:34px; height:34px; font-size:15px; }
    }

    /* popup container */
    .chat-popup{
      width: var(--widget-width);
      height: var(--widget-height);
      position: absolute;
      right: 0;
      bottom: 72px; /* sits above launcher: 64px height + 8px gap => 72 */
      border-radius: var(--radius);
      overflow: hidden;
      display:flex; flex-direction:column;
      transform: translateY(20px); /* off-screen-ish initially */
      opacity: 0;
      pointer-events: none;
      box-shadow: 0 18px 48px rgba(2,6,23,0.55);
      border: 1px solid rgba(255,255,255,0.04);
      background: var(--messages-bg);
    }
    .chat-popup.open{
      transform: translateY(0);
      opacity: 1;
      pointer-events: auto;
    }

    /* header (solid color) */
    .chat-header{
      display:flex; align-items:center; gap:10px;
      padding:10px 12px; background: var(--header-bg);
      border-bottom: 1px solid rgba(255,255,255,0.03);
      min-height:56px;
    }
    .robot-avatar {
      width:44px; height:44px; border-radius:10px; overflow:hidden; flex:0 0 44px;
      display:grid; place-items:center; background:#081124; border: 1px solid rgba(255,255,255,0.04);
    }
    .header-info{flex:1; color:var(--text); display:flex; flex-direction:column; gap:2px}
    .header-info .name{font-weight:700; font-size:14px; letter-spacing:0.2px}
    .header-info .meta{font-size:12px; color:var(--muted)}
    .header-actions{display:flex; gap:8px; align-items:center}
    .icon-btn{background:transparent;border:none;color:var(--text);cursor:pointer;padding:8px;border-radius:8px}
    .icon-btn:hover{background:rgba(255,255,255,0.02)}

    /* language switch button in header */
    #langToggle{
      font-weight:700;font-size:12px;padding:6px 8px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);
      background:transparent;color:var(--text);cursor:pointer;
    }

    /* messages area */
    .chat-messages{
      flex:1; overflow:auto; padding:14px; display:flex; flex-direction:column; gap:10px;
      background: var(--messages-bg);
    }
    .msg{display:flex; width:100%}
    .msg.ai{justify-content:flex-start}
    .msg.user{justify-content:flex-end}
    .bubble{
      padding:10px 12px; border-radius:12px; max-width:78%; line-height:1.35; font-size:13px;
      word-break:break-word; box-shadow: 0 6px 18px rgba(2,6,23,0.2);
    }
    .msg.ai .bubble{ background: var(--ai-bg); color:var(--text); border: 1px solid rgba(255,255,255,0.02); border-top-left-radius:6px}
    .msg.user .bubble{ background: var(--user-bg); color:#062018; border-top-right-radius:6px }

    /* timestamp small under message (optional) */
    .msg .ts{font-size:11px;color:var(--muted); margin-top:4px; display:block; text-align:left}
    .bubble .ts {
      font-size: 11px;
      color: var(--muted);
      margin-top: 6px;
      display: block;
      opacity: 0.85;
    }
    .msg.user .bubble .ts {
      text-align: right;
    }
    .msg.ai .bubble .ts {
      text-align: left;
    }

    /* input area */
    .chat-input{
      display:flex; gap:8px; padding:10px; align-items:center; background:var(--input-bg);
      border-top:1px solid rgba(255,255,255,0.03);
    }
    .chat-input input{
      flex:1; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.03);
      background:transparent; color:var(--text); outline:none; font-size:13px;
    }

    /* better send button */
    .send-btn{
      display:inline-flex; align-items:center; gap:8px; padding:10px 12px;
      border-radius:10px; border:none; cursor:pointer; font-weight:700;
      background:var(--accent); color:white; box-shadow: 0 8px 20px rgba(2,6,23,0.35);
      transition: transform 180ms ease, box-shadow 180ms ease;
    }
    .send-btn:active{ transform: translateY(1px) }
    .send-btn:hover{ transform: translateY(-3px); box-shadow: 0 14px 28px rgba(2,6,23,0.45) }

    /* small tweaks */
    .lang-cap{ padding:6px 10px; border-radius:999px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.03); color:var(--text); font-weight:600; cursor:pointer; font-size:12px }
    .suggestion{ cursor:pointer }

    /* --- Improved Monochrome (White) Theme --- */
    :root {
      /* Add these for better white theme contrast */
      --mono-bg: #f8fafc;
      --mono-header-bg: #f3f4f6;
      --mono-messages-bg: #ffffff;
      --mono-input-bg: #f1f5f9;
      --mono-accent: #2563eb;
      --mono-user-bg: #e0e7ef;
      --mono-ai-bg: #f3f4f6;
      --mono-text: #222a36;
      --mono-muted: #6b7280;
    }

    /* Override theme palette for Monochrome */
    .my-ai.mono-theme {
      --bg: var(--mono-bg);
      --header-bg: var(--mono-header-bg);
      --messages-bg: var(--mono-messages-bg);
      --input-bg: var(--mono-input-bg);
      --accent: var(--mono-accent);
      --user-bg: var(--mono-user-bg);
      --ai-bg: var(--mono-ai-bg);
      --text: var(--mono-text);
      --muted: var(--mono-muted);
    }

    /* Bubble contrast for white theme */
    .my-ai.mono-theme .msg.ai .bubble {
      background: var(--ai-bg);
      color: var(--text);
      border: 1px solid #e5e7eb;
    }
    .my-ai.mono-theme .msg.user .bubble {
      background: var(--user-bg);
      color: #222a36;
      border: 1px solid #d1d5db;
    }

    /* Input area for white theme */
    .my-ai.mono-theme .chat-input input {
      background: #f8fafc;
      color: #222a36;
      border: 1px solid #e5e7eb;
    }
    .my-ai.mono-theme .send-btn {
      background: var(--accent);
      color: #fff;
    }

    /* --- Custom Widget Scroller --- */
    .chat-messages {
      scrollbar-width: thin;
      scrollbar-color: var(--accent) var(--messages-bg);
    }
    .chat-messages::-webkit-scrollbar {
      width: 8px;
      border-radius: 8px;
      background: var(--messages-bg);
    }
    .chat-messages::-webkit-scrollbar-thumb {
      background: var(--accent);
      border-radius: 8px;
      min-height: 24px;
      border: 2px solid var(--messages-bg);
      opacity: 0.7;
    }
    .chat-messages::-webkit-scrollbar-thumb:hover {
      background: #3b82f6;
      opacity: 1;
    }

    /* typing indicator dots */
    .typing-dots {
      display: inline-flex;
      gap: 4px;
      align-items: center;
      height: 18px;
    }
    .typing-dots .dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--accent);
      opacity: 0.7;
      animation: typingDotAnim 1.2s infinite;
    }
    .typing-dots .dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots .dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingDotAnim {
      0%, 80%, 100% { transform: translateY(0); opacity: 0.7; }
      40% { transform: translateY(-6px); opacity: 1; }
    }

    /* responsive */
    @media (max-width:420px){
      .my-ai{ right:8px; bottom:8px }
      .chat-popup{ width:92vw; height:72vh; bottom:84px }
    }
/* --- fullscreen layout (REPLACE existing .my-ai.fullscreen block with this) --- */
.my-ai.fullscreen {
  position: fixed !important;
  left: 0 !important;
  top: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  max-width: none !important;
  z-index: var(--z-widget);
  border-radius: 0 !important;
  background: var(--messages-bg);
  font-size: 15px !important;
  box-shadow: none !important;
  display: flex;
  align-items: stretch;
  justify-content: center;
}

/* Make the chat-popup fill the widget in fullscreen and remove the old bottom offset */
.my-ai.fullscreen .chat-popup {
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100% !important;
  height: 100% !important;
  border-radius: 0 !important;
  padding: 0 !important;
  box-shadow: none !important;
  display: flex;
  flex-direction: column;
}

.my-ai.fullscreen .chat-header {
  position: sticky;
  top: 0;
  z-index: 30;
  min-height: 54px !important;
  padding: 12px 18px !important;
  background: var(--header-bg);
  border-bottom: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 4px 20px rgba(2,6,23,0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.my-ai.fullscreen .chat-messages {
  flex: 1 1 auto;
  overflow: auto;
  padding: 12px 18px !important;
  gap: 12px !important;
  -webkit-overflow-scrolling: touch;
  padding-top: 12px;
  padding-bottom: 88px; 
}
.my-ai.fullscreen .bubble {
  font-size: 14px !important;
  padding: 12px 16px !important;
  max-width: 98% !important;
  border-radius: 10px !important;
}

.my-ai.fullscreen .chat-input {
  position: sticky;
  bottom: 0;
  z-index: 40;
  display: flex;
  gap: 8px;
  padding: 12px 18px !important;
  align-items: center;
  background: linear-gradient(180deg, rgba(0,0,0,0.02), rgba(0,0,0,0.02));
  border-top: 1px solid rgba(0,0,0,0.04);
}

.my-ai.fullscreen .header-info { gap: 2px; }
.my-ai.fullscreen .header-actions { gap: 8px; }
.my-ai.fullscreen .robot-avatar { width: 38px !important; height: 38px !important; }
.my-ai.fullscreen .lang-cap,
.my-ai.fullscreen .suggestion-btn,
.my-ai.fullscreen .qa-btn,
.my-ai.fullscreen .accept-correction,
.my-ai.fullscreen .ignore-correction {
  font-size: 13px !important; padding: 8px 12px !important;
}
.my-ai.fullscreen .send-btn { font-size: 14px !important; padding: 10px 12px !important; }
.my-ai.fullscreen .feedback-btn { width: 32px !important; height: 32px !important; font-size: 16px !important; }

.my-ai.fullscreen #globalThemeBtn,
.my-ai.fullscreen #launcherWrap { display: none !important; }

  </style>
</head>
<body>

  <button id="globalThemeBtn" title="Cycle theme">THEME</button>

  <div class="my-ai" id="chatWidget" aria-hidden="false">
    <div id="launcherWrap" style="position:relative; display:flex; gap:8px; align-items:flex-end; justify-content:flex-end;">
      <div class="chat-launcher" id="chatLauncher" role="button" tabindex="0" aria-label="Open Chat">
        <canvas id="chatIconCanvas" width="48" height="48" style="display:block"></canvas>
      </div>
    </div>

    <div id="chatPopup" class="chat-popup" role="dialog" aria-modal="false" aria-labelledby="cg-title">
      <div class="chat-header">
        <div class="robot-avatar" aria-hidden="true">
          <canvas id="robotAvatarCanvas" width="44" height="44" style="display:block"></canvas>
        </div>

        <div class="header-info">
          <div class="name" id="cg-title">Gyan sarthi V1</div>
          <div class="meta" id="cg-status">Select language </div>
        </div>

        <div class="header-actions">
          <button id="langToggle" aria-label="Toggle language">EN | HI</button>
          <button class="icon-btn" id="chatBack" aria-label="Back">← Back</button>
          <button class="icon-btn" id="chatClose" aria-label="Close chat">✕</button>
        </div>
      </div>

      <div id="chatMessages" class="chat-messages" aria-live="polite">
        <!-- messages rendered here by JS -->
      </div>

      <div class="chat-input">
        <input id="chatInput" placeholder="Ask me anything... (Enter to send)" autocomplete="off" />
        <button id="chatSend" class="send-btn" aria-label="Send message">
          <span style="font-size:13px">Send</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="opacity:0.95">
            <path d="M3 11.5L21 3l-7.5 18L11 13l-8-1.5z" fill="white"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
<script type="module">
  import * as THREE from 'https://unpkg.com/three@0.154.0/build/three.module.js';

  // ---------- small rotating 3D icon for launcher ----------
  (function(){
    const canvas = document.getElementById('chatIconCanvas');
    if (!canvas) return;
    const renderer = new THREE.WebGLRenderer({ canvas, alpha:true, antialias: true });
    renderer.setSize(48,48);
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45,1,0.1,10); camera.position.z = 2.2;
    const geo = new THREE.TorusKnotGeometry(0.4,0.12,64,12);
    const mat = new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true, opacity: 0.9, transparent: true });
    const mesh = new THREE.Mesh(geo, mat); scene.add(mesh);
    (function animate(){ requestAnimationFrame(animate); mesh.rotation.x += 0.01; mesh.rotation.y += 0.014; renderer.render(scene,camera); })();
  })();

  // ---------- small 3D robot head in header ----------
  (function(){
    const canvas = document.getElementById('robotAvatarCanvas');
    if (!canvas) return;
    const renderer = new THREE.WebGLRenderer({ canvas, alpha:true, antialias:true });
    renderer.setSize(44,44);
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45,1,0.1,10); camera.position.z = 2;
    const geo = new THREE.SphereGeometry(0.6, 24, 24);
    const mat = new THREE.MeshBasicMaterial({ color: 0x8be4ff, wireframe: false });
    const mesh = new THREE.Mesh(geo, mat); scene.add(mesh);
    (function animate(){ requestAnimationFrame(animate); mesh.rotation.y += 0.015; renderer.render(scene,camera); })();
  })();
</script>

<script>
(function () {
  'use strict';

  // ---------- Config ----------
  const AUTH_TOKEN = 'tok_ABC_2025_example_0001'; 
  const SCH_API = '/Api/getScholarshipData.php';   // endpoint handling scholarship actions
  const MAX_RESULTS = 12;
  const TRANSLATE_ENABLED = false; // keep false by default for reliability
  const RICH_JSON = './rich_responses.json'; // local rich responses for testing
  // ---------- DOM refs ----------
  const popup = document.getElementById('chatPopup');
  const launcher = document.getElementById('chatLauncher');
  const closeBtn = document.getElementById('chatClose');
  const messages = document.getElementById('chatMessages');
  const input = document.getElementById('chatInput');
  const sendBtn = document.getElementById('chatSend');
  const status = document.getElementById('cg-status');
  const globalThemeBtn = document.getElementById('globalThemeBtn');
  const timeWidget = document.getElementById('timeWidget');

  // We'll inject a small select in the popup header area if not present
  let scholarshipSelect = null;
  let refineArea = null; // area for follow-up questions
  let suggestedCardId = null;

  // ---------- Theme / small typography ----------
  const SMALL_FONT = '13px';
  const PILL_STYLE = 'display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px;margin:2px;background:rgba(255,255,255,0.04);color:var(--accent)';

  // ---------- Local state ----------
  let richResponses = null;
  let namesCache = null; // array of scholarship names (for suggestions)
  let lastLoadedNamesAt = 0;
  let userLang = 'en'; // reserved, for possible translation
  let typingSpeed = 20; // ms per char for typewriter
  let ongoingTyping = null;

  const widgetRoot = document.getElementById('chatWidget');
  const backBtn = document.createElement('button');
  backBtn.className = 'icon-btn';
  backBtn.id = 'chatBack';
  backBtn.setAttribute('aria-label', 'Back');
  backBtn.textContent = '← Back';
  backBtn.style.display = 'none';
  document.querySelector('.chat-header').appendChild(backBtn);

  let isFullscreen = false;
  function enterFullscreen() {
    if (!widgetRoot) return;
    widgetRoot.classList.add('fullscreen');
    backBtn.style.display = '';
    isFullscreen = true;
    globalThemeBtn && (globalThemeBtn.style.display = 'none');
    document.getElementById('launcherWrap') && (document.getElementById('launcherWrap').style.display = 'none');
  }
  function exitFullscreen() {
    if (!widgetRoot) return;
    widgetRoot.classList.remove('fullscreen');
    backBtn.style.display = 'none';
    isFullscreen = false;
    globalThemeBtn && (globalThemeBtn.style.display = '');
    document.getElementById('launcherWrap') && (document.getElementById('launcherWrap').style.display = '');
  }

  backBtn.addEventListener('click', exitFullscreen);

  let userStartedChat = false;
  function addUserBubble(text) {
    if (!messages) return;
    if (!userStartedChat) {
      enterFullscreen();
      userStartedChat = true;
    }
    const wrap = document.createElement('div'); wrap.className = 'msg user';
    const bubble = document.createElement('div'); bubble.className = 'bubble';
    bubble.textContent = text;
    const ts = document.createElement('div'); ts.className = 'ts'; ts.textContent = nowStr();
    bubble.appendChild(ts); wrap.appendChild(bubble); messages.appendChild(wrap);
    messages.scrollTop = messages.scrollHeight;
  }
  // ---------- Utility helpers ----------
  function $qs(sel, root = document) { return root.querySelector(sel); }
  function $qsa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }
  function noop() {}
  function nowStr() {
    const d = new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  // ---------- Typing & bubble helpers ----------
  function showTyping() {
    if (!messages) return null;
    const wrap = document.createElement('div');
    wrap.className = 'msg ai typing-wrap';
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.innerHTML = `<span class="typing-dots"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span>`;
    wrap.appendChild(bubble);
    messages.appendChild(wrap);
    messages.scrollTop = messages.scrollHeight;
    return wrap;
  }
  function removeTyping(typingEl) { if (typingEl && typingEl.parentNode) typingEl.remove(); }

  function addAIBubbleHtml(html) {
    if (!messages) return null;
    const wrap = document.createElement('div'); wrap.className = 'msg ai';
    const bubble = document.createElement('div'); bubble.className = 'bubble';
    bubble.innerHTML = html;
    wrap.appendChild(bubble); messages.appendChild(wrap); messages.scrollTop = messages.scrollHeight;
    return bubble;
  }
  // Typewriter: creates an AI bubble and types text char-by-char
  function typeIntoBubble(text, speed = typingSpeed) {
    if (!messages) return Promise.resolve();
    // ensure single typing at a time per invocation; multiple allowed but return a Promise
    const wrap = document.createElement('div'); wrap.className = 'msg ai';
    const bubble = document.createElement('div'); bubble.className = 'bubble typing-text';
    wrap.appendChild(bubble); messages.appendChild(wrap);
    messages.scrollTop = messages.scrollHeight;
    return new Promise(resolve => {
      let i = 0;
      const tot = text.length;
      const iv = setInterval(() => {
        bubble.textContent += text.charAt(i++);
        messages.scrollTop = messages.scrollHeight;
        if (i >= tot) {
          clearInterval(iv);
          bubble.classList.remove('typing-text');
          resolve();
        }
      }, speed);
    });
  }

  function numberWithCommas(x) { if (x === null || typeof x === 'undefined') return ''; return String(x).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }
  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
  }
  function escapeAttr(s) { return String(s).replace(/"/g, '&quot;'); }
  function openUrl(u) { try { window.open(u, '_blank', 'noopener'); } catch(e) { console.warn('openUrl failed', e); } }

  // ---------- Network helpers ----------
  async function apiPost(payload, timeoutMs = 15000) {
    const mapped = Object.assign({}, payload);
    if (payload && payload.action) {
      // mapping rules:
      // 'list' -> backend 'meta'
      // 'get_by_name' -> backend 'search' with query=name
      // 'refine' -> backend 'recommend'
      // 'search' -> backend 'search'
      const a = String(payload.action).toLowerCase();
      if (a === 'list') mapped.action = 'meta';
      else if (a === 'get_by_name') {
        mapped.action = 'search';
        mapped.query = payload.name || payload.query || '';
      }
      else if (a === 'refine') mapped.action = 'recommend';
      else mapped.action = a; // pass-through for 'search','meta','recommend'
    }
    mapped.auth_token = AUTH_TOKEN;
    const ctrl = new AbortController();
    const id = setTimeout(() => ctrl.abort(), timeoutMs);
    try {
      const res = await fetch(SCH_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(mapped),
        signal: ctrl.signal
      });
      clearTimeout(id);
      if (!res.ok) {
        const txt = await res.text().catch(()=>'');
        throw new Error('Network error: ' + res.status + (txt ? (' — ' + txt) : ''));
      }
      const json = await res.json().catch(()=>{ throw new Error('Invalid JSON from server'); });
      return json;
    } catch (err) {
      clearTimeout(id);
      throw err;
    }
  }

  // ---------- Load rich responses ----------
  async function loadRichResponses() {
    if (richResponses) return richResponses;
    try {
      const r = await fetch(RICH_JSON, { cache: 'no-cache' });
      if (!r.ok) throw new Error('no rich json');
      richResponses = await r.json();
    } catch (e) {
      // fallback defaults
      richResponses = {
        greetings: [
          "Hello! 👋 How can I help you today?",
          "Hi there! I can help find scholarships, eligibility, and application steps.",
          "Hey! Tell me what you need — scholarships, eligibility or how to apply?",
          "Namaste! मैं आपकी कैसे मदद कर सकता/सकती हूँ?",
          "नमस्कार! तुम्हाला छात्रवृत्तीबद्दल मदत पाहिजे का?"
        ],
        affirmative: [
          "Of course — I can help with that. Tell me which scholarship or give me a quick detail.",
          "Sure — I can explain that scholarship in detail. Want eligibility, amount, or how to apply?",
          "Absolutely — I can fetch the details and show courses covered and documents required."
        ],
        no_results: [
          "I couldn't find an exact match. Try a different name, or I'll suggest related scholarships.",
          "No exact match — I can show similar scholarships or you can refine with course/year/category.",
          "Sorry, I couldn't find that. Want me to show popular scholarships instead?"
        ],
        flow_prompts: {
          ask_income: "What's your family's annual income? (type or choose a suggestion)",
          ask_course: "Which course are you studying / applying for? (choose from the list)",
          ask_course_year: "Which year of study are you in? (e.g. 1st Year, 2nd Year)",
          ask_category: "Which category do you belong to? (e.g. OBC, SC, ST, Minority, Girls, Orphan, Handicap)"
        }
      };
    }
    return richResponses;
  }

  // ---------- Load scholarship names (cached) ----------
  async function loadScholarshipNames(force = false) {
    // avoid frequent reloads; cache for 5 minutes
    const now = Date.now();
    if (!force && namesCache && (now - lastLoadedNamesAt) < 300_000) return namesCache;
    try {
      const typing = showTyping();
      const res = await apiPost({ action: 'list' }, 12000);
      removeTyping(typing);
      if (res && res.status === 'ok') {
        // backend meta returns res.meta.courses etc OR res.names array
        if (Array.isArray(res.names)) {
          namesCache = res.names;
        } else if (res.meta && Array.isArray(res.meta.courses)) {
          // fallbak: use course names as names cache (not ideal, but still useful)
          namesCache = res.meta.courses.slice(0, 800);
        } else if (Array.isArray(res.results)) {
          namesCache = res.results.map(r => r.name).filter(Boolean);
        } else {
          namesCache = [];
        }
      } else {
        namesCache = [];
      }
      lastLoadedNamesAt = Date.now();
      return namesCache;
    } catch (err) {
      console.warn('loadScholarshipNames error', err);
      namesCache = namesCache || [];
      lastLoadedNamesAt = Date.now();
      return namesCache;
    }
  }

  // ---------- Spell suggestion util (simple levenshtein) ----------
  function levenshtein(a,b){
    if (a === b) return 0;
    a = String(a || '').toLowerCase(); b = String(b || '').toLowerCase();
    const la = a.length, lb = b.length;
    if (la === 0) return lb;
    if (lb === 0) return la;
    const v0 = new Array(lb + 1);
    const v1 = new Array(lb + 1);
    for (let i=0;i<=lb;i++) v0[i] = i;
    for (let i=0;i<la;i++) {
      v1[0] = i + 1;
      for (let j=0;j<lb;j++) {
        const cost = (a.charAt(i) === b.charAt(j)) ? 0 : 1;
        v1[j+1] = Math.min(v1[j] + 1, v0[j+1] + 1, v0[j] + cost);
      }
      for (let j=0;j<=lb;j++) v0[j] = v1[j];
    }
    return v1[lb];
  }
  function findBestNameSuggestion(query, names) {
    if (!query || !names || !names.length) return null;
    let best = null; let bestScore = -1;
    const q = query.toLowerCase().trim();
    for (const n of names) {
      const s = n.toLowerCase().trim();
      // compute similarity ratio
      const lev = levenshtein(q, s);
      const maxl = Math.max(q.length, s.length, 1);
      const sim = 1 - (lev / maxl);
      if (sim > bestScore) { bestScore = sim; best = n; }
    }
    // threshold tuned to 0.55
    return bestScore >= 0.55 ? best : null;
  }

  // ---------- Render helpers: consolidated scholarship record ----------
  function buildCoursesTable(courses) {
    if (!Array.isArray(courses) || courses.length === 0) return '';
    let html = `<table style="width:100%;font-size:13px;border-collapse:collapse;margin-top:8px">
      <thead><tr style="text-align:left"><th style="padding:6px 8px">Course / Year</th><th style="padding:6px 8px">Sanction (text)</th><th style="padding:6px 8px">Amount (min)</th></tr></thead><tbody>`;
    for (const c of courses) {
      const label = escapeHtml(c.course_label || c.course || c.course_year || '—');
      const text = escapeHtml(c.scholarship_text || c.scholarship || '');
      const amt = (c.sch_min ? ('₹' + numberWithCommas(c.sch_min)) : (c.scholarship_amount ? ('₹' + numberWithCommas(c.scholarship_amount)) : '-'));
      html += `<tr><td style="padding:6px 8px;border-top:1px solid rgba(255,255,255,0.03)">${label}</td><td style="padding:6px 8px;border-top:1px solid rgba(255,255,255,0.03)">${text}</td><td style="padding:6px 8px;border-top:1px solid rgba(255,255,255,0.03)">${amt}</td></tr>`;
    }
    html += `</tbody></table>`;
    return html;
  }

  async function renderScholarshipRecord(rec) {
    if (!rec) return;
    const title = `${rec.name || 'Scholarship'}`;
    await typeIntoBubble(title, Math.max(12, typingSpeed));
    const incomeLines = Array.isArray(rec.income_limit_texts) ? rec.income_limit_texts : (rec.income_limit_text ? [rec.income_limit_text] : []);
    const siteLinks = Array.isArray(rec.site_urls) ? rec.site_urls : (rec.site_text ? [rec.site_text] : []);
    const docs = Array.isArray(rec.documents_required) ? rec.documents_required : [];
    const elig = Array.isArray(rec.eligibility_categories) ? rec.eligibility_categories : [];
    const courses = Array.isArray(rec.courses) ? rec.courses : [];

    let html = `<div style="margin-top:8px">`;
    // pills row
    html += `<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">`;
    if (incomeLines.length) html += `<div style="${PILL_STYLE}">${escapeHtml(incomeLines[0])}</div>`;
    if (rec.income_min) html += `<div style="${PILL_STYLE}">Min ₹${numberWithCommas(rec.income_min)}</div>`;
    if (rec.income_max) html += `<div style="${PILL_STYLE}">Max ₹${numberWithCommas(rec.income_max)}</div>`;
    if (rec.payment_remark) html += `<div style="${PILL_STYLE}">${escapeHtml(rec.payment_remark)}</div>`;
    html += `</div>`;

    html += `<div style="font-weight:700;margin-bottom:6px">Eligibility / Criteria</div>`;
    html += `<div class="muted" style="white-space:pre-wrap;font-size:${SMALL_FONT};margin-bottom:8px">${escapeHtml(rec.criteria || 'Not specified')}</div>`;

    html += `<div style="font-weight:700;margin-bottom:6px">Required Documents</div>`;
    html += `<div style="margin-bottom:8px;font-size:${SMALL_FONT}">${docs.length ? docs.map(d=>escapeHtml(d)).join(', ') : 'Not specified'}</div>`;

    html += `<div style="font-weight:700;margin-bottom:6px">Eligible Categories</div>`;
    html += `<div style="margin-bottom:8px">${elig.length ? elig.map(c=>`<span style="${PILL_STYLE}">${escapeHtml(c)}</span>`).join(' ') : '<span class="muted">Not specified</span>'}</div>`;

    html += `<div style="font-weight:700;margin-bottom:6px">Reference Links</div>`;
    if (siteLinks && siteLinks.length) {
      for (const u of siteLinks) {
        const safe = escapeHtml(String(u));
        html += `<div style="margin-bottom:6px"><a class="site-link" href="${safe}" target="_blank" rel="noopener noreferrer">${safe}</a></div>`;
      }
    } else {
      html += `<div class="muted" style="margin-bottom:8px">No reference links</div>`;
    }

    const coursesHtml = buildCoursesTable(courses);
    if (coursesHtml) {
      html += `<div style="font-weight:700;margin-top:10px;margin-bottom:6px">Courses & Sanctioned Amounts</div>`;
      html += coursesHtml;
    }
    html += `</div>`;

    const bubble = addAIBubbleHtml(html);
    if (bubble) {
      const actions = document.createElement('div');
      actions.style.display = 'flex';
      actions.style.gap = '8px';
      actions.style.marginTop = '10px';
      const applyBtn = document.createElement('button');
      applyBtn.className = 'lang-cap';
      applyBtn.textContent = 'How to apply';
      applyBtn.addEventListener('click', () => {
        sendEvent({ event: 'click', result_type: 'SCHOLAR_APPLY', query: rec.name });
        addAIBubbleHtml(`<div style="font-weight:700">How to apply</div>
          <div style="font-size:${SMALL_FONT};margin-top:6px">Visit reference link(s) above. Typically you will need: caste/domicile/income certificate, bank details, and 12th marksheet. If you'd like, tell me your category and income and I will refine recommendations.</div>`);
      });
      actions.appendChild(applyBtn);

      const refineBtn = document.createElement('button');
      refineBtn.className = 'lang-cap';
      refineBtn.textContent = 'Refine suggestion';
      refineBtn.addEventListener('click', () => {
        askRefinementQuestions(rec);
      });
      actions.appendChild(refineBtn);

      bubble.appendChild(actions);
    }
  }

function askRefinementQuestions(rec) {
  const prompts = (richResponses && richResponses.flow_prompts) ? richResponses.flow_prompts : {
    ask_income: "What's your family's annual income? (type or choose a suggestion)",
    ask_course: "Which course are you studying / applying for? (choose from the list)",
    ask_course_year: "Which year of study are you in? (e.g. 1st Year, 2nd Year)",
    ask_category: "Which category do you belong to? (e.g. OBC, SC, ST, Minority, Girls, Orphan, Handicap)"
  };

  const html = `<div style="font-weight:700;margin-bottom:6px">${escapeHtml(prompts.ask_course || 'Tell me a bit about yourself — I\\ll recommend best fit')}</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
      <button class="lang-cap quick-q" data-key="category" data-val="OBC">OBC</button>
      <button class="lang-cap quick-q" data-key="category" data-val="SC">SC</button>
      <button class="lang-cap quick-q" data-key="category" data-val="ST">ST</button>
      <button class="lang-cap quick-q" data-key="category" data-val="Minority">Minority</button>
      <button class="lang-cap quick-q" data-key="category" data-val="Girls">Girl</button>
    </div>
    <div style="font-weight:700;margin-bottom:6px">${escapeHtml(prompts.ask_income || "Annual family income")}</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
      <button class="lang-cap quick-q" data-key="income" data-val="250000">Below ₹2,50,000</button>
      <button class="lang-cap quick-q" data-key="income" data-val="300000">Below ₹3,00,000</button>
      <button class="lang-cap quick-q" data-key="income" data-val="800000">Below ₹8,00,000</button>
    </div>
    <div style="font-weight:700;margin-bottom:6px">${escapeHtml(prompts.ask_course || "Course (optional)")}</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
      <button class="lang-cap quick-q" data-key="course" data-val="B. Tech">B. Tech</button>
      <button class="lang-cap quick-q" data-key="course" data-val="B. Pharmacy">B. Pharmacy</button>
      <button class="lang-cap quick-q" data-key="course" data-val="D. Pharmacy">D. Pharmacy</button>
      <button class="lang-cap quick-q" data-key="course" data-val="MBA">MBA</button>
    </div>`;

  const bubble = addAIBubbleHtml(html);
  if (!bubble) return;
  bubble.addEventListener('click', (e) => {
    const b = e.target.closest('.quick-q');
    if (!b) return;
    const key = b.getAttribute('data-key');
    const val = b.getAttribute('data-val');
    if (!key || !val) return;
    const filters = {};
    if (key === 'income') filters['income_max'] = val;
    else filters[key] = val;
    sendRefineRequest(filters);
    b.disabled = true; b.style.opacity = 0.7;
  });
}


  async function sendRefineRequest(filters) {
    try {
      const typing = showTyping();
      // Map to backend expected param names: action 'refine' -> backend 'recommend'
      const res = await apiPost({ action: 'refine', filters: filters, limit: 6 }, 12000);
      removeTyping(typing);
      if (!res || res.status !== 'ok') {
        addAIBubbleHtml('<div>Sorry, unable to refine suggestions at this time.</div>');
        return;
      }
      if (Array.isArray(res.results) && res.results.length) {
        addAIBubbleHtml(`<div style="font-weight:700;margin-bottom:6px">Refined recommendations</div>`);
        for (const r of res.results.slice(0, 6)) {
          await renderScholarshipRecord(r);
        }
        return;
      }
      if (Array.isArray(res.nearest_suggestions) && res.nearest_suggestions.length) {
        addAIBubbleHtml(`<div style="font-weight:700;margin-bottom:6px">Try one of these scholarships</div>`);
        renderSuggestionButtons(res.nearest_suggestions);
        return;
      }
      addAIBubbleHtml('<div>No refined matches found.</div>');
    } catch (err) {
      console.error('sendRefineRequest failed', err);
      addAIBubbleHtml('<div>Network error while refining suggestions.</div>');
    }
  }

  function renderSuggestionButtons(list) {
    if (!Array.isArray(list) || !list.length) return;
    const outer = document.createElement('div'); outer.className = 'msg ai';
    const bubble = document.createElement('div'); bubble.className = 'bubble';
    bubble.innerHTML = `<div style="font-weight:700;margin-bottom:6px">Suggestions</div>`;
    const wrap = document.createElement('div');
    wrap.style.display = 'flex';
    wrap.style.gap = '8px';
    wrap.style.flexWrap = 'wrap';
    list.slice(0, 8).forEach(s => {
      const btn = document.createElement('button');
      btn.className = 'lang-cap suggestion-btn';
      btn.textContent = s;
      btn.addEventListener('click', () => {
        sendEvent({ event: 'click', result_type: 'SUGGESTION', query: s });
        fetchScholarshipByName(s);
      });
      wrap.appendChild(btn);
    });
    bubble.appendChild(wrap);
    outer.appendChild(bubble);
    messages.appendChild(outer);
    messages.scrollTop = messages.scrollHeight;
  }

  // ---------- Search & fetch handlers ----------
  async function fetchScholarshipByName(name) {
    if (!name) return;
    addUserBubble(name);
    const typing = showTyping();
    try {
      const res = await apiPost({ action: 'get_by_name', name: name, limit: MAX_RESULTS }, 12000);
      removeTyping(typing);
      if (!res || res.status !== 'ok') {
        addAIBubbleHtml('<div>Failed to load scholarship details.</div>');
        return;
      }
      // If backend returned suggestion
      if (res.suggested_name && res.suggested_name !== name) {
        showDidYouMean(res.suggested_name, name);
      }
      if (Array.isArray(res.results) && res.results.length) {
        addAIBubbleHtml(`<div style="font-weight:700;margin-bottom:6px">${res.results.length} record(s) found for "${escapeHtml(name)}"</div>`);
        for (const rec of res.results) {
          await renderScholarshipRecord(rec);
        }
        showAutoRecommendation(res.results);
        return;
      }
      if (Array.isArray(res.nearest_suggestions) && res.nearest_suggestions.length) {
        addAIBubbleHtml('<div>I could not find an exact match — try one of these:</div>');
        renderSuggestionButtons(res.nearest_suggestions);
        return;
      }
      addAIBubbleHtml('<div>No data found for that scholarship.</div>');
    } catch (err) {
      removeTyping(typing);
      console.error('fetchScholarshipByName error', err);
      addAIBubbleHtml('<div>Network error while loading scholarship.</div>');
    }
  }

  async function doSearch(query) {
    if (!query || !query.trim()) return;
    addUserBubble(query);
    const typing = showTyping();
    try {
      const res = await apiPost({ action: 'search', query: query, limit: MAX_RESULTS }, 12000);
      removeTyping(typing);
      if (!res || res.status !== 'ok') {
        addAIBubbleHtml('<div>Search failed.</div>');
        return;
      }
      // If backend returned a corrected suggestion, show DID YOU MEAN UI
      if (res.corrected_query && res.corrected_query !== (res.normalized_query || query)) {
        showDidYouMean(res.corrected_query, query);
      }
      // If results present
      if (Array.isArray(res.results) && res.results.length) {
        addAIBubbleHtml(`<div style="font-weight:700;margin-bottom:6px">${res.results.length} result(s) for "${escapeHtml(query)}"</div>`);
        for (const r of res.results) await renderScholarshipRecord(r);
        showAutoRecommendation(res.results);
        return;
      }
      // If suggested_name present
      if (res.suggested_name) {
        const html = `<div>No exact match found. Did you mean <button class="lang-cap accept-correction suggest-name">${escapeHtml(res.suggested_name)}</button> ?</div>`;
        const bubble = addAIBubbleHtml(html);
        const btn = bubble.querySelector('.suggest-name');
        if (btn) btn.addEventListener('click', () => fetchScholarshipByName(res.suggested_name));
        return;
      }
      if (Array.isArray(res.nearest_suggestions) && res.nearest_suggestions.length) {
        addAIBubbleHtml('<div>Try one of these suggestions:</div>');
        renderSuggestionButtons(res.nearest_suggestions);
        return;
      }
      // If nothing returned, try client-side suggestion via namesCache
      const names = await loadScholarshipNames().catch(()=>[]);
      const sug = findBestNameSuggestion(query, names);
      if (sug) {
        showDidYouMean(sug, query);
        return;
      }
      addAIBubbleHtml('<div>Sorry — no scholarships matched your query.</div>');
    } catch (err) {
      removeTyping(typing);
      console.error('doSearch error', err);
      addAIBubbleHtml('<div>Network/search error occurred.</div>');
    }
  }

  // ---------- Spell-correct / did-you-mean UI ----------
  function showDidYouMean(corrected, original) {
    if (!corrected) return;
    const html = `<div style="font-weight:700;margin-bottom:6px">Did you mean?</div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="lang-cap accept-correction">${escapeHtml(corrected)}</button>
        <button class="lang-cap ignore-correction">No, keep "${escapeHtml(original)}"</button>
      </div>`;
    const bubble = addAIBubbleHtml(html);
    bubble.querySelector('.accept-correction').addEventListener('click', () => {
      sendEvent({ event: 'click', result_type: 'CORRECTION', query: corrected });
      doSearch(corrected);
    });
    bubble.querySelector('.ignore-correction').addEventListener('click', () => {
      if (bubble && bubble.parentNode) bubble.parentNode.removeChild(bubble);
    });
  }

  // ---------- Auto recommendation (always present) ----------
  function showAutoRecommendation(results) {
    if (!Array.isArray(results) || results.length === 0) return;
    let best = null;
    for (const r of results) {
      let candidateValue = 0;
      if (r.scholarship_amount_min) candidateValue = Number(r.scholarship_amount_min);
      if (r.sch_min) candidateValue = Math.max(candidateValue, Number(r.sch_min));
      if (r.SCHOLARSHIP_AMOUNT_MIN) candidateValue = Math.max(candidateValue, Number(r.SCHOLARSHIP_AMOUNT_MIN));
      if (!candidateValue && r.courses && r.courses.length) {
        for (const c of r.courses) {
          if (c.sch_min && c.sch_min > candidateValue) candidateValue = Number(c.sch_min);
        }
      }
      r._best_value = candidateValue || 0;
      if (!best || r._best_value > best._best_value) best = r;
    }
    if (!best) return;
    const html = `<div style="font-weight:800;margin-bottom:6px">Recommended for you</div>
      <div style="display:flex;gap:12px;align-items:flex-start">
        <div style="flex:1">
          <div style="font-weight:700">${escapeHtml(best.name)}</div>
          <div style="font-size:${SMALL_FONT};margin-top:6px">${escapeHtml((best.criteria || '').slice(0,160))}${(best.criteria && best.criteria.length>160)?'...':''}</div>
        </div>
        <div style="text-align:right">
          <div style="font-weight:900;color:var(--accent);font-size:14px">${best._best_value ? '₹' + numberWithCommas(best._best_value) : 'Varies'}</div>
          <div style="font-size:12px;margin-top:8px">
            <button class="lang-cap rec-open">View</button>
            <button class="lang-cap rec-refine">Refine</button>
          </div>
        </div>
      </div>`;
    const bubble = addAIBubbleHtml(html);
    const openBtn = bubble.querySelector('.rec-open');
    const refineBtn = bubble.querySelector('.rec-refine');
    if (openBtn) openBtn.addEventListener('click', () => { sendEvent({ event: 'click', result_type: 'RECOMMEND_OPEN', query: best.name }); fetchScholarshipByName(best.name); });
    if (refineBtn) refineBtn.addEventListener('click', () => { sendEvent({ event: 'click', result_type: 'RECOMMEND_REFINE', query: best.name }); askRefinementQuestions(best); });
  }

  // ---------- Feedback / event sender ----------
  async function sendEvent({ event, result_id = null, result_type = null, feedback = null, query = null }) {
    try {
      const payload = { auth_token: AUTH_TOKEN, event: event || 'ui_event' };
      if (result_id) payload.result_id = String(result_id);
      if (result_type) payload.result_type = String(result_type);
      if (feedback !== null) payload.feedback = feedback;
      if (query) payload.query = String(query);
      // best-effort; don't await or block
      fetch('/Api/getCollegeData.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
      }).catch(noop);
    } catch (err) { /* ignore */ }
  }

  // ---------- Create select UI ----------
  function createSelect(names) {
    if (!popup) return;
    let header = popup.querySelector('.popup-header') || popup.querySelector('.header') || popup;
    if (!header) header = popup;
    if (scholarshipSelect && scholarshipSelect.parentNode) scholarshipSelect.parentNode.removeChild(scholarshipSelect);
    const container = document.createElement('div');
    container.style.display = 'flex';
    container.style.alignItems = 'center';
    container.style.gap = '8px';
    container.style.margin = '8px';
    container.style.flexWrap = 'wrap';

    const label = document.createElement('label');
    label.style.fontSize = '12px';
    label.style.color = 'rgba(255,255,255,0.85)';
    label.textContent = 'Scholarship:';
    container.appendChild(label);

    const sel = document.createElement('select');
    sel.style.fontSize = SMALL_FONT;
    sel.style.padding = '6px 8px';
    sel.style.background = 'var(--input-bg)';
    sel.style.color = 'white';
    sel.style.border = '1px solid rgba(255,255,255,0.04)';
    sel.style.borderRadius = '6px';
    sel.style.minWidth = '220px';
    sel.setAttribute('aria-label', 'Select scholarship');

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '— choose —';
    sel.appendChild(opt0);

    names.forEach(n => {
      const o = document.createElement('option');
      o.value = n;
      o.textContent = n;
      sel.appendChild(o);
    });

    sel.addEventListener('change', (e) => {
      const v = e.target.value;
      if (!v) return;
      fetchScholarshipByName(v);
    });

    container.appendChild(sel);

    const quick = document.createElement('input');
    quick.type = 'search';
    quick.placeholder = 'Quick search scholarships...';
    quick.style.padding = '6px 8px';
    quick.style.fontSize = SMALL_FONT;
    quick.style.border = '1px solid rgba(255,255,255,0.04)';
    quick.style.borderRadius = '6px';
    quick.style.background = 'var(--input-bg)';
    quick.style.color = 'white';
    quick.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = quick.value && quick.value.trim();
        if (q) doSearch(q);
      }
    });
    container.appendChild(quick);

    scholarshipSelect = container;
    header.parentNode.insertBefore(container, header.nextSibling);
  }

  // ---------- Initial setup & greetings ----------
  async function initWidget() {
    if (launcher) launcher.addEventListener('click', e => {
      popup && popup.classList.toggle('open');
      if (popup && popup.classList.contains('open')) setTimeout(() => input && input.focus(), 240);
    });
    if (closeBtn) closeBtn.addEventListener('click', () => popup && popup.classList.remove('open'));

    // prepare rich responses
    await loadRichResponses();

    // populate select names (best-effort)
    const names = await loadScholarshipNames().catch(()=>[]);
    if (names && names.length) createSelect(names);

    // attach send handlers
    if (sendBtn) sendBtn.addEventListener('click', () => { if (input) doSearch(input.value); });
    if (input) input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doSearch(input.value); } });

    // initial friendly greeting always
    const g = Array.isArray(richResponses.greetings) ? richResponses.greetings[Math.floor(Math.random() * richResponses.greetings.length)] : 'Hello!';
    await typeIntoBubble(g, 18);
    // show friendly suggested starters (not scholarship on top per your request)
    renderSuggestionButtons(['Help me find a scholarship', 'How to apply', 'Documents required']);
  }

  // ---------- Kick off ----------
  setTimeout(initWidget, 200);

  // ---------- Accessibility / link handling ----------
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a.site-link');
    if (a) {
      e.preventDefault();
      openUrl(a.getAttribute('href'));
    }
  });

  // expose for debugging
  window.ScholarWidget = { doSearch, fetchScholarshipByName, sendEvent, loadScholarshipNames };

})();
</script>


</body>
</html>
