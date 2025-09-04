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

  <!-- scripts: Three icons + widget logic -->
  <script type="module">
    import * as THREE from 'https://unpkg.com/three@0.154.0/build/three.module.js';

    // ---------- small rotating 3D icon for launcher ----------
    (function(){
      const canvas = document.getElementById('chatIconCanvas');
      const renderer = new THREE.WebGLRenderer({ canvas, alpha:true, antialias:true });
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
      // DOM refs
      const widgetRoot = document.getElementById('chatWidget');
      const launcher = document.getElementById('chatLauncher');
      const popup = document.getElementById('chatPopup');
      const closeBtn = document.getElementById('chatClose');
      const messages = document.getElementById('chatMessages');
      const input = document.getElementById('chatInput');
      const sendBtn = document.getElementById('chatSend');
      const status = document.getElementById('cg-status');
      const globalThemeBtn = document.getElementById('globalThemeBtn');
      const langToggle = document.getElementById('langToggle');

      // theme palette (solid colors only)
      const themes = [
        { id:'blue', name:'Blue', vars:{
          '--bg':'#071126','--header-bg':'#08203a','--messages-bg':'#071126','--input-bg':'#061126','--accent':'#2563eb','--user-bg':'#34d399'} },
        { id:'mid', name:'Midnight', vars:{
          '--bg':'#05070a','--header-bg':'#081221','--messages-bg':'#05101a','--input-bg':'#05111b','--accent':'#0ea5e9','--user-bg':'#60a5fa'} },
        { id:'sage', name:'Sage', vars:{
          '--bg':'#07120a','--header-bg':'#0b2416','--messages-bg':'#06140c','--input-bg':'#06140c','--accent':'#16a34a','--user-bg':'#86efac'} },
        { id:'mono', name:'Monochrome', vars:{
          '--bg':'#ffffff','--header-bg':'#f3f4f6','--messages-bg':'#ffffff','--input-bg':'#f8fafc','--accent':'#374151','--user-bg':'#9ca3af'} }
      ];
      let themeIndex = 0;

      function applyTheme(idx){
        themeIndex = (idx + themes.length) % themes.length;
        const t = themes[themeIndex];
        Object.entries(t.vars).forEach(([k,v]) => document.documentElement.style.setProperty(k, v));
        const root = document.querySelector('.my-ai');
        root.style.setProperty('--accent', t.vars['--accent']);
        root.style.setProperty('--user-bg', t.vars['--user-bg']);
        root.style.setProperty('--header-bg', t.vars['--header-bg']);
        root.style.setProperty('--messages-bg', t.vars['--messages-bg']);
        root.style.setProperty('--input-bg', t.vars['--input-bg']);
        // Add/remove mono-theme class for white theme
        if(t.id === 'mono') root.classList.add('mono-theme');
        else root.classList.remove('mono-theme');
      }
      applyTheme(themeIndex); // default

      globalThemeBtn.addEventListener('click', (e)=>{
        e.stopPropagation();
        applyTheme(themeIndex + 1);
        // quick pulse
        anime.timeline({duration:220}).add({targets: '#globalThemeBtn', scale:1.07}).add({targets:'#globalThemeBtn', scale:1});
      });

      // slide-up open / slide-down close animations (no fade)
      function openPopup(){
        if(popup.classList.contains('open')) return;
        // animate popup from below
        popup.style.pointerEvents = 'auto';
        anime({
          targets: popup,
          translateY: [20, 0],
          opacity: [0, 1],
          duration: 420,
          easing: 'cubicBezier(.17,.67,.2,1)',
          begin: ()=> popup.classList.add('open')
        });
        // hide launcher smoothly (scale down -> visibility hidden)
        anime({
          targets: launcher,
          translateY: [0, 10],
          scale: [1, 0.95],
          opacity: [1, 0],
          duration: 260,
          easing: 'easeInCubic',
          complete: ()=> { launcher.style.visibility = 'hidden'; }
        });
        setTimeout(()=> input.focus(), 360);
      }

      function closePopup(){
        if(!popup.classList.contains('open')) return;
        // slide down popup
        anime({
          targets: popup,
          translateY: [0, 20],
          opacity: [1, 0],
          duration: 360,
          easing: 'cubicBezier(.17,.67,.2,1)',
          complete: ()=> {
            popup.classList.remove('open');
            popup.style.pointerEvents = 'none';
          }
        });
        // show launcher back
        launcher.style.visibility = 'visible';
        anime({ targets: launcher, translateY: [10,0], scale: [0.96,1], opacity: [0,1], duration:360, easing: 'easeOutElastic(1, .6)' });
      }

      // click handlers
      launcher.addEventListener('click', (e)=> { e.stopPropagation(); openPopup(); });
      launcher.addEventListener('keydown', (e)=> { if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPopup(); } });
      closeBtn.addEventListener('click', (e)=> { e.stopPropagation(); closePopup(); });

      // clicking outside popup closes it
      document.addEventListener('click', (e)=>{
        if(!popup.contains(e.target) && !launcher.contains(e.target)) closePopup();
      });
      popup.addEventListener('click', e=> e.stopPropagation());
      document.addEventListener('keydown', (e)=> { if(e.key === 'Escape') closePopup(); });

      // messaging helpers
      function scrollToBottom(){
        messages.scrollTop = messages.scrollHeight + 200;
      }
      function addBubble(text, who='ai', withTs=true){
        const wrap = document.createElement('div');
        wrap.className = 'msg ' + (who==='user' ? 'user' : 'ai');
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = text;
        if(withTs){
          const ts = document.createElement('div');
          ts.className = 'ts';
          ts.textContent = new Date().toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit'});
          bubble.appendChild(ts); // <-- timestamp INSIDE bubble
        }
        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        scrollToBottom();
        return bubble;
      }
      function showTyping(){
        const wrap = document.createElement('div'); wrap.className = 'msg ai';
        const bubble = document.createElement('div'); bubble.className = 'bubble';
        bubble.innerHTML = `
          <span class="typing-dots">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
          </span>
        `;
        bubble.style.opacity = 0.85;
        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
      }
      function typeIntoBubble(text, speed=26){
        const wrap = document.createElement('div'); wrap.className = 'msg ai';
        const bubble = document.createElement('div'); bubble.className = 'bubble';
        wrap.appendChild(bubble);
        messages.appendChild(wrap); scrollToBottom();
        let i=0;
        return new Promise(resolve=>{
          const iv = setInterval(()=>{ bubble.textContent += text.charAt(i++); scrollToBottom(); if(i>=text.length){ clearInterval(iv); resolve(); } }, speed);
        });
      }

      // initial prompt: language select quick chips
      function renderInitial(){
        const outer = document.createElement('div'); outer.className = 'msg ai';
        const bubble = document.createElement('div'); bubble.className = 'bubble';
        bubble.innerHTML = `<div style="font-weight:700;margin-bottom:8px">Please select a language</div>`;
        const caps = document.createElement('div'); caps.style.display='flex'; caps.style.gap='8px';
        ['English','Hindi'].forEach(lang=>{
          const b = document.createElement('button'); b.className='lang-cap'; b.textContent=lang;
          b.addEventListener('click', ()=> handleLanguageSelection(lang, b));
          caps.appendChild(b);
        });
        bubble.appendChild(caps); outer.appendChild(bubble);
        messages.appendChild(outer); scrollToBottom();
      }

      const greetings = {
        'English': 'Hello! 👋 How can I help you today?',
        'Hindi': 'नमस्ते! 👋 मैं आपकी कैसे मदद कर सकता/सकती हूँ?'
      };

      // language state toggled by header button or chips
      let currentLang = 'English';
      function setLanguage(lang){
        currentLang = lang;
        status.textContent = `${lang} • ChatGoD V1`;
        // update header lang toggle label to show active language left
        langToggle.textContent = (lang === 'English') ? 'EN | HI' : 'EN | HI';
        // (we keep both visible but currentLang drives replies)
      }

      async function handleLanguageSelection(lang, btnEl){
        // quick micro-interaction
        anime.timeline({duration:160}).add({targets: btnEl, scale:1.06}).add({targets: btnEl, scale:1});
        setLanguage(lang);
        addBubble(lang, 'user');
        // disable chips
        document.querySelectorAll('.lang-cap').forEach(x => x.disabled = true);
        const t = showTyping();
        await new Promise(r=>setTimeout(r,700));
        t.remove();
        await typeIntoBubble(greetings[lang] || greetings.English, 26);
        // quick suggestions after greeting
        await new Promise(r=>setTimeout(r,220));
        const follow = document.createElement('div'); follow.className='msg ai';
        const fb = document.createElement('div'); fb.className='bubble';
        fb.innerHTML = `<div style="font-weight:700;margin-bottom:6px">Quick suggestions</div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                          <button class="lang-cap suggestion">Ask about assignments</button>
                          <button class="lang-cap suggestion">Find notes</button>
                          <button class="lang-cap suggestion">Exam tips</button>
                        </div>`;
        follow.appendChild(fb); messages.appendChild(follow); scrollToBottom();
      }

      // header language toggle (EN | HI) -> toggles between English and Hindi
      langToggle.addEventListener('click', ()=>{
        setLanguage(currentLang === 'English' ? 'Hindi' : 'English');
        // give small confirmation message from ai
        const t = showTyping();
        setTimeout(async ()=> {
          t.remove();
          await typeIntoBubble(currentLang === 'English' ? greetings['English'] : greetings['Hindi'], 18);
        }, 500);
      });

      // sending messages (static demo)
      async function sendUser(text){
        if(!text || !text.trim()) return;
        addBubble(text.trim(), 'user');
        input.value = '';
        const t = showTyping();
        await new Promise(r=>setTimeout(r, 600 + Math.min(900, text.length*14)));
        t.remove();
        // basic keyword responses
        const lower = text.toLowerCase();
        let reply = "Thanks — noted. (static demo reply)";
        if(lower.includes('hello')||lower.includes('hi')) reply = currentLang==='Hindi' ? 'हैलो! कैसे मदद करूँ?' : 'Hey! How can I help you?';
        else if(lower.includes('assignment')) reply = currentLang==='Hindi' ? 'असाइनमेंट सेक्शन देखें। क्या नमूने चाहिए?' : "Try checking the 'Assignments' section. Want sample topics?";
        else if(lower.includes('notes')) reply = currentLang==='Hindi' ? 'आप किस विषय के लिए नोट्स चाहते हैं?' : "Which subject notes are you looking for?";
        else if(lower.includes('exam')|| lower.includes('tips')) reply = currentLang==='Hindi' ? 'स्मार्ट स्टडी: active recall + spaced repetition.' : 'Study smart: active recall + spaced repetition.';
        await typeIntoBubble(reply, 22);
      }

      sendBtn.addEventListener('click', ()=> sendUser(input.value));
      input.addEventListener('keydown', (e)=> { if(e.key === 'Enter'){ e.preventDefault(); sendUser(input.value); } });

      messages.addEventListener('click', (e)=>{
        const s = e.target.closest('.suggestion');
        if(s) sendUser(s.textContent);
      });

      // initial render
      renderInitial();

      // live time widget below chats
      function updateTimeWidget(){
        const now = new Date();
        const opts = { hour: '2-digit', minute: '2-digit', hour12: false };
        timeWidget.textContent = `Local time: ${now.toLocaleTimeString(undefined, opts)}`;
      }
      updateTimeWidget(); setInterval(updateTimeWidget, 30_000);

      // initial small attention animation for launcher
      setInterval(()=> anime({ targets: '#chatLauncher', scale:[1,1.06,1], duration:3000, easing:'easeInOutSine' }), 9000);

      // accessibility: keyboard open
      launcher.addEventListener('keydown', (e)=> { if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPopup(); } });

      // ensure scroll anchored
      messages.scrollTop = messages.scrollHeight;

    })();
  </script>
</body>
</html>
