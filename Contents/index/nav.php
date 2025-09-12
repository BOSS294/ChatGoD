<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gyan Ganga Institute — Enhanced Navbar (EJS)</title>

  <!-- Font Awesome (for icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-RXf+QSDCUqs6/1XQ3z4f2bH1Qe6u9z9k8K2V1Z6eG1qU5Kq3e5pVt2bJ5m1QY6Z4K1v3qF1u4P1g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Google Material Icons (for mobile bottom nav) -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
   
    :root {
      --primary: #1a4f8e;
      --primary-dark: #0d3a6e;
      --primary-light: #2c6cb0;
      --secondary: #ff9e1b;
      --secondary-dark: #e0890c;
      --light: #ffffff;
      --dark: #06213a;
      --gray-light: #f5f7fa;
      --gray: #e2e8f0;
    }
    /*margin reset*/
    html, body { margin: 0; padding: 0; }

    /* Base styles */
    .page {
      font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;

      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;

    }

    /* Desktop Navbar - floating centered bar */
    .desktop-nav {
      display: none;                 /* shown by media query */
      position: fixed;
      top: 18px;                     /* distance from top */
      left: 50%;
      transform: translateX(-50%);   /* center horizontally */
      width: calc(100% - 48px);      /* gap from viewport edges */
      max-width: 1200px;             /* container cap */
      z-index: 1300;
      pointer-events: auto;
    }

    .desktop-nav .nav-inner {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 12px 22px;
      border-radius: 14px;
      /* mobile theme color, slightly translucent so it floats over content */
      background: linear-gradient(180deg, rgba(26,79,142,0.98), rgba(13,58,110,0.95));
      color: var(--light);
      backdrop-filter: blur(8px) saturate(120%);
      -webkit-backdrop-filter: blur(8px) saturate(120%);
      box-shadow: 0 14px 40px rgba(2,22,54,0.18);
      position: relative;
      overflow: visible; /* allow underline / shadows to show */
      border: 1px solid rgba(255,255,255,0.06);
    }

    /* small top accent bar stays inside the floating nav */
    .desktop-nav .nav-inner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--secondary), var(--secondary-dark));
      border-top-left-radius: 14px;
      border-top-right-radius: 14px;
    }

    /* Desktop link styles + guaranteed underline */
    .desktop-nav .nav-links a {
      display: inline-block;
      color: var(--light);
      text-decoration: none;
      font-weight: 500;
      padding: 10px 16px;
      position: relative;
      border-radius: 8px;
      transition: background 0.18s ease, color 0.18s ease;
      overflow: visible;
    }

    .desktop-nav .nav-links a:hover { background: rgba(255,255,255,0.06); }

    /* Underline pseudo-element centered origin for smooth animation */
    @media (min-width: 992px) {
      .desktop-nav .nav-links a::after {
        content: '';
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 6px;
        height: 3px;
        background: var(--secondary);
        transform: scaleX(0);
        transform-origin: center;
        transition: transform 260ms cubic-bezier(.2,.9,.2,1);
        border-radius: 2px;
        pointer-events: none;
      }

      .desktop-nav .nav-links a:hover::after,
      .desktop-nav .nav-links a:focus::after,
      .desktop-nav .nav-links a.active::after {
        transform: scaleX(1);
      }

      /* ensure parents don't clip the underline */
      .desktop-nav .nav-inner, .desktop-nav .nav-links { overflow: visible; }
    }

    .nav-links a.active { background: rgba(255,255,255,0.04); font-weight: 600; }

    /* Mobile Top Navbar */
    .mobile-top { position: fixed; left: 12px; right: 12px; top: 12px; z-index: 1200; display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-radius: 12px; background: var(--primary); backdrop-filter: blur(6px) saturate(120%); -webkit-backdrop-filter: blur(6px) saturate(120%); box-shadow: 0 8px 22px rgba(2, 22, 54, 0.15); }
    .mobile-top .brand { gap: 10px; }
    .mobile-top .college-name { font-size: 15px; color: var(--light); font-weight: 700; }

    /* Hamburger (replaces info icon) */
    .hamburger-btn {
      display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 10px; cursor: pointer; color: var(--light); font-size: 18px; background: rgba(255,255,255,0.06); border: none; transition: transform 0.12s ease, background 0.12s ease;
    }
    .hamburger-btn:hover { transform: scale(1.04); background: rgba(255,255,255,0.09); }

    /* Mobile Bottom Navbar - use Google Material Icons */
    .mobile-bottom { position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 1200; display: flex; justify-content: space-around; gap: 6px; padding: 12px 14px; border-radius: 16px; background: var(--primary); backdrop-filter: blur(6px) saturate(120%); -webkit-backdrop-filter: blur(6px) saturate(120%); box-shadow: 0 10px 30px rgba(2, 22, 54, 0.15); align-items: center; }

    .mobile-bottom .nav-item { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; color: var(--light); cursor: pointer; padding: 10px 12px; border-radius: 10px; min-width: 56px; justify-content: center; transition: all 0.18s ease; }

    /* Material Icons styling */
    .material-icons { font-size: 20px; line-height: 1; display: block; color: var(--light); }

    .mobile-bottom .nav-item:hover { background: rgba(255,255,255,0.06); }
    .mobile-bottom .nav-item.active { background: rgba(255,255,255,0.12); font-weight: 700; }
    .mobile-bottom .nav-item.active .material-icons { color: var(--secondary); }

    /* Mobile menu overlay (replaces previous info-overlay) */
    .mobile-menu { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1400; display: none; align-items: flex-start; justify-content: flex-end; }
    .mobile-menu.open { display: flex; }
    .mobile-menu .sheet { width: 86%; max-width: 380px; height: 100%; background: var(--light); box-shadow: -18px 0 40px rgba(2,22,54,0.18); padding: 22px; display: flex; flex-direction: column; gap: 12px; }
    .mobile-menu .sheet header { display:flex; align-items:center; justify-content:space-between; }
    .mobile-menu .sheet header h4 { margin:0; color:var(--primary-dark); }
    .mobile-menu .sheet .menu-link { display:flex; align-items:center; gap:12px; padding:12px 10px; border-radius:8px; text-decoration:none; color:var(--dark); font-weight:600; }
    .mobile-menu .sheet .menu-link:hover { background: var(--gray-light); transform: translateX(4px); }

    /* Info overlay kept as small contextual panel for quick links (optional) */
    .info-overlay { position: fixed; right: 12px; top: 68px; z-index: 1300; width: calc(100% - 48px); max-width: 360px; border-radius: 12px; padding: 16px; background: var(--light); color: var(--dark); box-shadow: 0 12px 32px rgba(2, 44, 97, 0.15); display: none; flex-direction: column; gap: 8px; border: 1px solid var(--gray); }
    .info-overlay.open { display:flex; }

    .info-overlay a { display:flex; gap:12px; align-items:center; padding:12px 14px; border-radius:8px; color:var(--dark); text-decoration:none; font-weight:500; }
    .info-overlay a:hover { background: var(--gray-light); transform: translateX(3px); }

    /* Content area */
    .content { max-width: 1200px; margin: 48px auto; padding: 28px; border-radius: 16px; background: var(--light); box-shadow: 0 6px 16px rgba(2,12,27,0.05); color: var(--dark); border: 1px solid var(--gray); }

    /* brand: keep logo and name on a single row */
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      white-space: nowrap; /* prevent wrapping to next line */
    }

    .brand img.logo {
      width: 56px;
      height: 56px;
      object-fit: contain;
      border-radius: 10px;
      flex: 0 0 auto;
      background: rgba(255,255,255,0.04);
      padding: 4px;
    }

    .college-name {
      margin: 0;
      line-height: 1;
      font-weight: 700;
      font-size: 18px;
      color: var(--light); /* matches floating nav theme */
      display: inline-block;
    }

    /* mobile adjustments */
    @media (max-width: 991px) {
      .brand img.logo { width: 40px; height: 40px; }
      .college-name { font-size: 15px; color: var(--light); }
    }

    /* Responsive behavior */
    @media (min-width: 992px) { 
      .desktop-nav { display: flex; } 
      .mobile-top, .mobile-bottom { display: none; } 
      .page { padding-top: 0; } /* previously set to reserve space */
    }
    @media (max-width: 991px) { .desktop-nav { display:none; } .mobile-top, .mobile-bottom { display:flex; } }

    /* Focus states for accessibility */
    a:focus, button:focus, .nav-item:focus { outline: 3px solid rgba(26, 79, 142, 0.22); outline-offset: 2px; }

  </style>
</head>
<body>

  <div class="page">

    <!-- Desktop Navbar -->
    <nav class="desktop-nav" aria-label="Primary">
      <div class="nav-inner">
        <div class="brand">
          <img src="https://i.ibb.co/DfP3F87p/ggits-logo-removebg-preview.png" alt="Gyan Ganga Logo" class="logo" />
          <div class="college-name">Gyan Ganga Institute of Technology and Sciences</div>
        </div>

        <div class="nav-links">
          <a href="/" class="<%= active === 'home' ? 'active' : '' %>">Home</a>
          <a href="/academics" class="<%= active === 'academics' ? 'active' : '' %>">Academics</a>
          <a href="/news" class="<%= active === 'news' ? 'active' : '' %>">News</a>
          <a href="/contact" class="<%= active === 'contact' ? 'active' : '' %>">Contact</a>
          <a href="/about" class="<%= active === 'about' ? 'active' : '' %>">About Us</a>
        </div>
      </div>
    </nav>

    <!-- Mobile Top Navbar (hamburger replaces info icon) -->
    <nav class="mobile-top" aria-label="Mobile top navigation">
      <div class="brand">
        <img src="https://i.ibb.co/DfP3F87p/ggits-logo-removebg-preview.png" alt="logo" class="logo" style="width:40px;height:40px;" />
        <div class="college-name">Gyan Ganga Institute</div>
      </div>

      <button class="hamburger-btn" id="hamburgerBtn" aria-expanded="false" aria-controls="mobileMenu" title="Menu">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
    </nav>

    <!-- Mobile menu sheet (off-canvas) -->
    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
      <div class="sheet" role="dialog" aria-labelledby="mobileMenuTitle">
        <header>
          <h4 id="mobileMenuTitle">Gyan Ganga</h4>
          <button id="closeSheet" aria-label="Close menu" style="background:none;border:0;font-size:20px;cursor:pointer;color:var(--primary-dark);">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </header>

        <nav style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
          <a href="/" class="menu-link"><i class="fa-solid fa-home" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Home</a>
          <a href="/academics" class="menu-link"><i class="fa-solid fa-graduation-cap" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Academics</a>
          <a href="/news" class="menu-link"><i class="fa-solid fa-newspaper" style="width:20px;text-align:center;color:var(--primary-dark);"></i> News</a>
          <a href="/contact" class="menu-link"><i class="fa-solid fa-envelope" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Contact</a>
          <a href="/syllabus" class="menu-link"><i class="fa-solid fa-book" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Syllabus</a>
          <a href="/about" class="menu-link"><i class="fa-solid fa-building" style="width:20px;text-align:center;color:var(--primary-dark);"></i> About Us</a>
          <a href="/privacy" class="menu-link"><i class="fa-solid fa-shield-halved" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Privacy Policy</a>
          <a href="/terms" class="menu-link"><i class="fa-solid fa-file-contract" style="width:20px;text-align:center;color:var(--primary-dark);"></i> Terms &amp; Conditions</a>
        </nav>
      </div>
    </div>

    <!-- Info overlay (optional quick panel) -->
    <div class="info-overlay" id="infoOverlay" role="menu" aria-hidden="true">
      <a href="/syllabus"><i class="fa-solid fa-book"></i> Syllabus</a>
      <a href="/about"><i class="fa-solid fa-building"></i> About Us</a>
      <a href="/contact"><i class="fa-solid fa-envelope"></i> Contact Us</a>
      <a href="/privacy"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a>
      <a href="/terms"><i class="fa-solid fa-file-contract"></i> Terms &amp; Conditions</a>
    </div>

    <!-- Mobile Bottom Navbar (icons only) - Google Material Icons used here -->
    <nav class="mobile-bottom" aria-label="Mobile bottom navigation">
      <div class="nav-item <%= active === 'home' ? 'active' : '' %>" data-key="home" role="link" aria-label="Home">
        <span class="material-icons">home</span>
        <span>Home</span>
      </div>

      <div class="nav-item <%= active === 'academics' ? 'active' : '' %>" data-key="academics" role="link" aria-label="Academics">
        <span class="material-icons">school</span>
        <span>Academics</span>
      </div>

      <div class="nav-item <%= active === 'news' ? 'active' : '' %>" data-key="news" role="link" aria-label="News">
        <span class="material-icons">article</span>
        <span>News</span>
      </div>

      <div class="nav-item <%= active === 'contact' ? 'active' : '' %>" data-key="contact" role="link" aria-label="Contact">
        <span class="material-icons">mail</span>
        <span>Contact</span>
      </div>
    </nav>
  </div>
 


  <script>
    // Mobile sheet (hamburger) toggle
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeSheet = document.getElementById('closeSheet');

    function openMobileMenu() {
      mobileMenu.classList.add('open');
      mobileMenu.setAttribute('aria-hidden', 'false');
      hamburgerBtn.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }
    function closeMobileMenu() {
      mobileMenu.classList.remove('open');
      mobileMenu.setAttribute('aria-hidden', 'true');
      hamburgerBtn.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    if (hamburgerBtn) {
      hamburgerBtn.addEventListener('click', (e) => {
        const open = mobileMenu.classList.toggle('open');
        hamburgerBtn.setAttribute('aria-expanded', open);
        mobileMenu.setAttribute('aria-hidden', !open);
        if (open) document.body.style.overflow = 'hidden'; else document.body.style.overflow = '';
        e.stopPropagation();
      });
    }

    if (closeSheet) closeSheet.addEventListener('click', closeMobileMenu);

    // Close mobile menu by clicking outside the sheet
    document.addEventListener('click', (ev) => {
      if (!mobileMenu.classList.contains('open')) return;
      const sheet = mobileMenu.querySelector('.sheet');
      if (sheet && !sheet.contains(ev.target) && !hamburgerBtn.contains(ev.target)) {
        closeMobileMenu();
      }
    });

    // Mobile bottom nav active state & navigation (client-side)
    const bottomItems = document.querySelectorAll('.mobile-bottom .nav-item');
    bottomItems.forEach(item => {
      item.addEventListener('click', () => {
        bottomItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        const key = item.dataset.key;
        // client side navigation commented — enable if desired:
        // window.location.href = key === 'home' ? '/' : `/${key}`;
      });
    });

    // Accessibility: close mobile menu or info overlay with Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (mobileMenu.classList.contains('open')) closeMobileMenu();
        if (infoOverlay && infoOverlay.classList.contains('open')) {
          infoOverlay.classList.remove('open');
          infoOverlay.setAttribute('aria-hidden', 'true');
        }
      }
    });

    // optional: make desktop links keyboard-friendly by focusing their anchors
    document.querySelectorAll('.desktop-nav .nav-links a').forEach(a => a.setAttribute('tabindex', '0'));
  </script>
</body>
</html>
