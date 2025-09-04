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
  <title>ChatGoD V1 — UI</title>

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
  </style>
</head>
<body>

  <!-- separate theme cycle button bottom-left -->
  <button id="globalThemeBtn" title="Cycle theme">THEME</button>

  <!-- widget root -->
  <div class="my-ai" id="chatWidget" aria-hidden="false">
    <!-- launcher -->
    <div id="launcherWrap" style="position:relative; display:flex; gap:8px; align-items:flex-end; justify-content:flex-end;">
      <div class="chat-launcher" id="chatLauncher" role="button" tabindex="0" aria-label="Open Chat">
        <canvas id="chatIconCanvas" width="48" height="48" style="display:block"></canvas>
      </div>
    </div>

    <!-- popup chat -->
    <div id="chatPopup" class="chat-popup" role="dialog" aria-modal="false" aria-labelledby="cg-title">
      <div class="chat-header">
        <div class="robot-avatar" aria-hidden="true">
          <canvas id="robotAvatarCanvas" width="44" height="44" style="display:block"></canvas>
        </div>

        <div class="header-info">
          <div class="name" id="cg-title">ChatGoD V1</div>
          <div class="meta" id="cg-status">Select language • v1.0</div>
        </div>

        <div class="header-actions">
          <!-- language toggle EN | HI -->
          <button id="langToggle" aria-label="Toggle language">EN | HI</button>
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
<!-- full updated JS: Three icons + widget logic with backend integration + runtime translation -->
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
  (function(){
    // ---- DOM refs ----
    const widgetRoot = document.getElementById('chatWidget');
    const launcher = document.getElementById('chatLauncher');
    const popup = document.getElementById('chatPopup');
    const closeBtn = document.getElementById('chatClose');
    const messages = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const status = document.getElementById('cg-status');
    const globalThemeBtn = document.getElementById('globalThemeBtn'); // optional
    const langToggle = document.getElementById('langToggle'); // optional
    const timeWidget = document.getElementById('timeWidget'); // optional

    // ---- config ----
    // DEV: Replace with secure approach in production (server-side proxy). For testing only.
    const AUTH_TOKEN = 'tok_ABC_2025_example_0001';

    // Translator service config (LibreTranslate demo endpoint). Replace with your own if preferred.
    // NOTE: Using an external translation endpoint may be blocked by CORS in some environments.
    const TRANSLATE_API_URL = 'https://libretranslate.com/translate'; // public demo endpoint
    const TRANSLATE_ENABLED = true; // flip to false to disable translation calls

    // language -> target code mapping
    const LANG_CODE = { 'English': 'en', 'Hindi': 'hi', 'Marathi': 'mr' };

    // ---- themes (unchanged) ----
    const themes = [
      { id:'blue', name:'Blue', vars:{ '--bg':'#071126','--header-bg':'#08203a','--messages-bg':'#071126','--input-bg':'#061126','--accent':'#2563eb','--user-bg':'#34d399'} },
      { id:'mid', name:'Midnight', vars:{ '--bg':'#05070a','--header-bg':'#081221','--messages-bg':'#05101a','--input-bg':'#05111b','--accent':'#0ea5e9','--user-bg':'#60a5fa'} },
      { id:'sage', name:'Sage', vars:{ '--bg':'#07120a','--header-bg':'#0b2416','--messages-bg':'#06140c','--input-bg':'#06140c','--accent':'#16a34a','--user-bg':'#86efac'} },
      { id:'mono', name:'Monochrome', vars:{ '--bg':'#ffffff','--header-bg':'#f3f4f6','--messages-bg':'#ffffff','--input-bg':'#f8fafc','--accent':'#374151','--user-bg':'#9ca3af'} }
    ];
    let themeIndex = 0;
    function applyTheme(idx){
      themeIndex = (idx + themes.length) % themes.length;
      const t = themes[themeIndex];
      Object.entries(t.vars).forEach(([k,v]) => document.documentElement.style.setProperty(k, v));
      const root = document.querySelector('.my-ai');
      if (root) {
        root.style.setProperty('--accent', t.vars['--accent']);
        root.style.setProperty('--user-bg', t.vars['--user-bg']);
        root.style.setProperty('--header-bg', t.vars['--header-bg']);
        root.style.setProperty('--messages-bg', t.vars['--messages-bg']);
        root.style.setProperty('--input-bg', t.vars['--input-bg']);
        if (t.id === 'mono') root.classList.add('mono-theme'); else root.classList.remove('mono-theme');
      }
    }
    applyTheme(themeIndex);
    if (globalThemeBtn) {
      globalThemeBtn.addEventListener('click', (e)=>{ e.stopPropagation(); applyTheme(themeIndex + 1); anime.timeline({duration:220}).add({targets: '#globalThemeBtn', scale:1.07}).add({targets:'#globalThemeBtn', scale:1}); });
    }

    // ---- open/close popup ----
    function openPopup(){
      if (popup.classList.contains('open')) return;
      popup.style.pointerEvents = 'auto';
      anime({ targets: popup, translateY:[20,0], opacity:[0,1], duration:420, easing:'cubicBezier(.17,.67,.2,1)', begin: ()=> popup.classList.add('open') });
      anime({ targets: launcher, translateY:[0,10], scale:[1,0.95], opacity:[1,0], duration:260, easing:'easeInCubic', complete: ()=> { launcher.style.visibility='hidden'; }});
      setTimeout(()=> input && input.focus(), 360);
    }
    function closePopup(){
      if (!popup.classList.contains('open')) return;
      anime({ targets: popup, translateY:[0,20], opacity:[1,0], duration:360, easing:'cubicBezier(.17,.67,.2,1)', complete: ()=> { popup.classList.remove('open'); popup.style.pointerEvents='none'; }});
      launcher.style.visibility = 'visible';
      anime({ targets: launcher, translateY:[10,0], scale:[0.96,1], opacity:[0,1], duration:360, easing:'easeOutElastic(1,.6)' });
    }

    launcher && launcher.addEventListener('click', (e)=> { e.stopPropagation(); openPopup(); });
    launcher && launcher.addEventListener('keydown', (e)=> { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPopup(); }});
    closeBtn && closeBtn.addEventListener('click', (e)=> { e.stopPropagation(); closePopup(); });

    document.addEventListener('click', (e)=> { if (!popup.contains(e.target) && !launcher.contains(e.target)) closePopup(); });
    popup && popup.addEventListener('click', e=> e.stopPropagation());
    document.addEventListener('keydown', (e)=> { if (e.key === 'Escape') closePopup(); });

    // ---- messaging helpers ----
    function scrollToBottom(){ if(messages) messages.scrollTop = messages.scrollHeight + 200; }
    function addBubble(text, who='ai', withTs=true){
      if (!messages) return null;
      const wrap = document.createElement('div'); wrap.className = 'msg ' + (who==='user' ? 'user' : 'ai');
      const bubble = document.createElement('div'); bubble.className = 'bubble';
      bubble.textContent = text;
      if (withTs) {
        const ts = document.createElement('div'); ts.className = 'ts';
        try { ts.textContent = new Date().toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit'}); } catch(e){ ts.textContent = ''; }
        bubble.appendChild(ts);
      }
      wrap.appendChild(bubble); messages.appendChild(wrap); scrollToBottom();
      return bubble;
    }
    function showTyping(){
      if (!messages) return null;
      const wrap = document.createElement('div'); wrap.className = 'msg ai';
      const bubble = document.createElement('div'); bubble.className = 'bubble';
      bubble.innerHTML = `<span class="typing-dots"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span>`;
      bubble.style.opacity = 0.85;
      wrap.appendChild(bubble); messages.appendChild(wrap); scrollToBottom();
      return wrap;
    }
    function typeIntoBubble(text, speed=26){
      if (!messages) return Promise.resolve();
      const wrap = document.createElement('div'); wrap.className = 'msg ai';
      const bubble = document.createElement('div'); bubble.className = 'bubble typing-text';
      wrap.appendChild(bubble); messages.appendChild(wrap); scrollToBottom();
      return new Promise(resolve=>{
        let i = 0;
        const iv = setInterval(()=> {
          bubble.textContent += text.charAt(i++);
          scrollToBottom();
          if (i >= text.length) { clearInterval(iv); resolve(); }
        }, speed);
      });
    }

    // ---- translation helpers ----
    async function translateText(text, targetLangCode='en') {
      // no-op if translation disabled or target is English
      if (!TRANSLATE_ENABLED || !text || targetLangCode === 'en') return text;
      try {
        // perform a simple POST to LibreTranslate demo endpoint
        const payload = { q: text, source: 'auto', target: targetLangCode, format: 'text' };
        const res = await fetch(TRANSLATE_API_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        if (!res.ok) {
          // fail silently and return original
          return text;
        }
        const j = await res.json();
        if (j && (j.translatedText || j.translated_text)) return j.translatedText || j.translated_text;
        // some LibreTranslate endpoints return { translatedText }
        return text;
      } catch (err) {
        // network/CORS or other – fallback to original
        return text;
      }
    }

    // wrapper: translate if required by currentLang and then type it
    async function showTranslatedAndType(text, speed=26) {
      try {
        const code = LANG_CODE[currentLang] || 'en';
        if (code === 'en' || !TRANSLATE_ENABLED) {
          await typeIntoBubble(text, speed);
        } else {
          const translated = await translateText(text, code);
          await typeIntoBubble(translated, speed);
        }
      } catch (err) {
        await typeIntoBubble(text, speed);
      }
    }

    // translate an array of suggestion strings and render (parallel)
    async function translateSuggestionsAndRender(list) {
      if (!Array.isArray(list) || list.length === 0) return renderSuggestions(list); // nothing to do; render as-is
      const code = LANG_CODE[currentLang] || 'en';
      if (code === 'en' || !TRANSLATE_ENABLED) return renderSuggestions(list);
      try {
        // translate suggestions in parallel but limit concurrency (simple)
        const translatedPromises = list.slice(0,8).map(s => translateText(s, code));
        const translated = await Promise.all(translatedPromises);
        renderSuggestions(translated);
      } catch (err) {
        renderSuggestions(list); // fallback
      }
    }

    // ---- initial language chips ----
    function renderInitial(){
      if (!messages) return;
      const outer = document.createElement('div'); outer.className = 'msg ai';
      const bubble = document.createElement('div'); bubble.className = 'bubble';
      bubble.innerHTML = `<div style="font-weight:700;margin-bottom:8px">Please select a language</div>`;
      const caps = document.createElement('div'); caps.style.display='flex'; caps.style.gap='8px';
      ['English','Hindi','Marathi'].forEach(lang=>{
        const b = document.createElement('button'); b.className='lang-cap'; b.textContent=lang;
        b.addEventListener('click', ()=> handleLanguageSelection(lang, b));
        caps.appendChild(b);
      });
      bubble.appendChild(caps); outer.appendChild(bubble); messages.appendChild(outer); scrollToBottom();
    }

    const greetings = {
      'English': 'Hello! 👋 How can I help you today?',
      'Hindi': 'नमस्ते! 👋 मैं आपकी कैसे मदद कर सकता/सकती हूँ?',
      'Marathi': 'नमस्कार! 👋 मी आज तुम्हाला कशी मदत करू शकतो/शकते?'
    };
    let currentLang = 'English';
    function setLanguage(lang){
      currentLang = lang;
      if (status) status.textContent = `${lang} • ChatGoD V1`;
      if (langToggle) { langToggle.textContent = (lang === 'English') ? 'EN | HI' : 'EN | HI'; }
    }

    async function handleLanguageSelection(lang, btnEl){
      try { anime.timeline({duration:160}).add({targets: btnEl, scale:1.06}).add({targets: btnEl, scale:1}); } catch(e){}
      setLanguage(lang);
      addBubble(lang, 'user');
      document.querySelectorAll('.lang-cap').forEach(x => x.disabled = true);
      const t = showTyping();
      await new Promise(r=>setTimeout(r,700));
      t && t.remove();
      // greeting already localized — show it with typing effect (no extra translation)
      await showTranslatedAndType(greetings[lang] || greetings.English, 26);
      await new Promise(r=>setTimeout(r,220));
      const follow = document.createElement('div'); follow.className='msg ai';
      const fb = document.createElement('div'); fb.className='bubble';
      fb.innerHTML = `<div style="font-weight:700;margin-bottom:6px">Quick suggestions</div>
                      <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="lang-cap suggestion">Ask about placements</button>
                        <button class="lang-cap suggestion">Find notes</button>
                        <button class="lang-cap suggestion">Exam tips</button>
                      </div>`;
      follow.appendChild(fb); messages.appendChild(follow); scrollToBottom();

      // translate the quick suggestions right away if language != English
      if ((LANG_CODE[currentLang] || 'en') !== 'en' && TRANSLATE_ENABLED) {
        // get those suggestion nodes and translate labels
        const nodes = follow.querySelectorAll('.suggestion');
        nodes.forEach(async n => {
          const orig = n.textContent || n.innerText;
          try {
            const tr = await translateText(orig, LANG_CODE[currentLang]);
            n.textContent = tr || orig;
          } catch (e) { /* ignore */ }
        });
      }

      // attach handlers
    }

    if (langToggle) {
      langToggle.addEventListener('click', ()=> {
        setLanguage(currentLang === 'English' ? 'Hindi' : 'English');
        const t = showTyping();
        setTimeout(async ()=> { t && t.remove(); await showTranslatedAndType(currentLang === 'English' ? greetings['English'] : greetings['Hindi'], 18); }, 500);
      });
    }

    // Main Executable File 
    async function sendToAPI(queryText){ 
      if (!queryText || !queryText.trim()) return;
      addBubble(queryText.trim(), 'user');
      if (input) input.value = '';
      const typingEl = showTyping();

      try {
        const res = await fetch('/Api/getCollegeData.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ auth_token: AUTH_TOKEN, query: queryText, limit: 6 })
        });

        if (!res.ok) throw new Error('Network error ' + res.status);
        const data = await res.json();
        typingEl && typingEl.remove();

        if (!data || data.status !== 'ok') {
          const msg = (data && data.message) ? data.message : 'Unable to fetch results.';
          await showTranslatedAndType('Sorry — ' + msg, 22);
          return;
        }

        // CASE A: direct matched results -> pick best result and show its answer (typewriter)
        if (Array.isArray(data.results) && data.results.length > 0) {
          const top = data.results[0];

          // Prefer presenting: CLG_BASIC.detailed_description -> snippet -> custom answer
          let mainText = '';
          if (top.CLG_BASIC && top.CLG_BASIC.detailed_description) mainText = top.CLG_BASIC.detailed_description;
          else if (top.snippet) mainText = top.snippet;
          else if (top.CLG_BASIC && top.CLG_BASIC.short_description) mainText = top.CLG_BASIC.short_description;
          else mainText = 'Here is some information I found.';

          // show main response using typing animation only (translated if needed)
          await showTranslatedAndType(mainText, 22);

          // then render structured parts as normal bubbles (courses/faqs) — translate labels & items where possible
          // COURSES
          if (top.CLG_COURSES && (top.CLG_COURSES.courses || Array.isArray(top.CLG_COURSES))) {
            const list = Array.isArray(top.CLG_COURSES.courses) ? top.CLG_COURSES.courses : (Array.isArray(top.CLG_COURSES) ? top.CLG_COURSES : []);
            if (list.length) {
              const wrap = document.createElement('div'); wrap.className = 'msg ai';
              const b = document.createElement('div'); b.className = 'bubble';
              let html = '<div style="font-weight:700;margin-bottom:6px">Courses</div><ul style="margin:0;padding-left:18px">';
              for (const c of list) {
                const nm = c.name || c.course || JSON.stringify(c);
                const dur = c.duration ? ` — ${c.duration}` : '';
                // translate course name if needed
                let itemText = nm + (dur ? dur : '');
                if ((LANG_CODE[currentLang] || 'en') !== 'en' && TRANSLATE_ENABLED) {
                  try { itemText = await translateText(itemText, LANG_CODE[currentLang]); } catch(e){ /* ignore */ }
                }
                html += `<li>${escapeHtml(itemText)}</li>`;
              }
              html += '</ul>';
              b.innerHTML = html; wrap.appendChild(b); messages.appendChild(wrap);
            }
          }

          // FAQ
          if (top.CLG_BASIC && Array.isArray(top.CLG_BASIC.faqs) && top.CLG_BASIC.faqs.length) {
            const wrap = document.createElement('div'); wrap.className = 'msg ai';
            const b = document.createElement('div'); b.className = 'bubble';
            let html = '<div style="font-weight:700;margin-bottom:6px">FAQs</div>';
            const faqs = top.CLG_BASIC.faqs.slice(0,3);
            for (const f of faqs) {
              let q = f.q || '';
              let a = f.a || '';
              if ((LANG_CODE[currentLang] || 'en') !== 'en' && TRANSLATE_ENABLED) {
                try {
                  [q, a] = await Promise.all([translateText(q, LANG_CODE[currentLang]), translateText(a, LANG_CODE[currentLang])]);
                } catch(e) {}
              }
              html += `<div style="margin-bottom:6px"><strong>${escapeHtml(q)}</strong><div>${escapeHtml(a)}</div></div>`;
            }
            b.innerHTML = html; wrap.appendChild(b); messages.appendChild(wrap);
          }

          // finally suggestions (translate then render)
          if (Array.isArray(data.suggestions) && data.suggestions.length) {
            await translateSuggestionsAndRender(data.suggestions);
          }
          return;
        }

        // CASE B: no direct results -> API returned nearest_qa OR nearest_suggestions
        if (Array.isArray(data.nearest_qa) && data.nearest_qa.length) {
          // present top nearest QA answer using typing animation (translate)
          const qa = data.nearest_qa[0];
          const mainText = qa.answer || qa.question || 'I found something related: ' + (qa.question || '');
          await showTranslatedAndType(mainText, 22);

          // show question buttons (nearest QA) below — translate questions labels
          const questions = data.nearest_qa.slice(0,6).map(q => q.question || '');
          if (questions.length) {
            // translate questions if necessary then render as suggestion buttons
            if ((LANG_CODE[currentLang] || 'en') !== 'en' && TRANSLATE_ENABLED) {
              const translatedQs = await Promise.all(questions.map(q => translateText(q, LANG_CODE[currentLang])));
              renderSuggestions(translatedQs);
            } else {
              renderSuggestions(questions);
            }
          }

          return;
        }

        // CASE C: no qa either -> show nearest_suggestions (buttons). Use one typed line only
        if (Array.isArray(data.nearest_suggestions) && data.nearest_suggestions.length) {
          await showTranslatedAndType('I could not find an exact match. You can try one of these:', 22);
          await translateSuggestionsAndRender(data.nearest_suggestions);
          return;
        }

        // final fallback
        await showTranslatedAndType('Sorry, I could not find relevant information. Try asking about placements, fees, or hostel.', 22);

      } catch (err) {
        typingEl && typingEl.remove();
        console.error(err);
        await showTranslatedAndType('Sorry — an error occurred while fetching data.', 20);
      } finally {
        scrollToBottom();
      }
    }

    // helper: add structured result DOM (kept for potential other uses)
    function addStructuredResult(title, r){
      const wrap = document.createElement('div'); wrap.className = 'msg ai';
      const b = document.createElement('div'); b.className = 'bubble';

      let html = `<div style="font-weight:800;margin-bottom:6px">${escapeHtml(title)}</div>`;
      if (r.snippet) html += `<div style="margin-bottom:8px">${escapeHtml(r.snippet)}</div>`;

      if (r.CLG_BASIC && r.CLG_BASIC.detailed_description) {
        html += `<div style="margin-bottom:8px">${escapeHtml(r.CLG_BASIC.detailed_description)}</div>`;
      }

      if (r.CLG_COURSES && (r.CLG_COURSES.courses || Array.isArray(r.CLG_COURSES))) {
        const list = Array.isArray(r.CLG_COURSES.courses) ? r.CLG_COURSES.courses : (Array.isArray(r.CLG_COURSES) ? r.CLG_COURSES : []);
        if (list.length) {
          html += `<div style="font-weight:700;margin-top:6px">Courses</div><ul style="margin:6px 0 0 16px;padding:0">`;
          list.forEach(c => {
            const nm = c.name || c.course || JSON.stringify(c);
            const dur = c.duration ? ` — ${escapeHtml(c.duration)}` : '';
            html += `<li>${escapeHtml(nm)}${dur}</li>`;
          });
          html += `</ul>`;
        }
      }

      if (r.CLG_FEES) {
        try {
          const fees = r.CLG_FEES;
          if (typeof fees === 'object' && fees !== null) {
            html += `<div style="font-weight:700;margin-top:6px">Fees (summary)</div><ul style="margin:6px 0 0 16px;padding:0">`;
            for (const k in fees) {
              if (!fees.hasOwnProperty(k)) continue;
              html += `<li>${escapeHtml(k)}: ${escapeHtml(String(fees[k]))}</li>`;
            }
            html += `</ul>`;
          } else {
            html += `<div style="margin-top:6px">${escapeHtml(String(fees))}</div>`;
          }
        } catch(e){}
      }

      b.innerHTML = html;
      wrap.appendChild(b);
      messages.appendChild(wrap);
    }

    // helper: render suggestion buttons
    function renderSuggestions(list){
      if (!Array.isArray(list) || list.length === 0) return;
      const follow = document.createElement('div'); follow.className = 'msg ai';
      const fb = document.createElement('div'); fb.className = 'bubble';
      let html = '<div style="font-weight:700;margin-bottom:6px">Try one of these</div><div style="display:flex;gap:8px;flex-wrap:wrap">';
      list.slice(0,8).forEach(s => {
        html += `<button class="lang-cap suggestion">${escapeHtml(s)}</button>`;
      });
      html += '</div>';
      fb.innerHTML = html; follow.appendChild(fb); messages.appendChild(follow);
      scrollToBottom();
    }

    // escape helper for text inserted into innerHTML in minimal capacity
    function escapeHtml(str){
      if (typeof str !== 'string') return String(str);
      return str.replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    // ---- wire send controls ----
    sendBtn && sendBtn.addEventListener('click', ()=> sendToAPI(input ? input.value : ''));
    input && input.addEventListener('keydown', (e)=> { if (e.key === 'Enter'){ e.preventDefault(); sendToAPI(input.value); } });

    // delegate suggestion clicks (legacy)
    messages && messages.addEventListener('click', (e)=> {
      const s = e.target.closest('.suggestion');
      if (s) sendToAPI(s.textContent || s.innerText);
    });

    // initial render + time
    renderInitial();
    if (timeWidget){
      function updateTimeWidget(){ const now = new Date(); const opts = { hour:'2-digit', minute:'2-digit', hour12:false }; timeWidget.textContent = `Local time: ${now.toLocaleTimeString(undefined, opts)}`; }
      updateTimeWidget(); setInterval(updateTimeWidget, 30_000);
    }

    // launcher pulse
    setInterval(()=> anime({ targets: '#chatLauncher', scale:[1,1.06,1], duration:3000, easing:'easeInOutSine' }), 9000);

    // accessibility
    launcher && launcher.addEventListener('keydown', (e)=> { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPopup(); } });

    // ensure anchor scroll
    if (messages) messages.scrollTop = messages.scrollHeight;

    // Expose sendToAPI for debugging
    window.ChatGoD = window.ChatGoD || {};
    window.ChatGoD.sendToAPI = sendToAPI;
    window.ChatGoD.translateText = translateText; // exposed for debugging

  })();
</script>


</body>
</html>
