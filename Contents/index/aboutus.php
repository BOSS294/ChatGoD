<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enhanced About Section - Gyan Ganga</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .about-theme {
      --primary: #1a4f8e;
      --primary-dark: #0d3a6e;
      --primary-light: #e8f1fc;
      --accent: #ff9e1b;
      --accent-dark: #e0890c;
      --text-dark: #06213a;
      --text-muted: #6b7b95;
      --text-light: #ffffff;
      --border-light: rgba(6, 33, 58, 0.08);
      --border-dark: rgba(6, 33, 58, 0.2);
      --shadow: 0 6px 18px rgba(8, 20, 40, 0.08);
      --shadow-hover: 0 12px 28px rgba(8, 20, 40, 0.15);
      --card-radius: 16px;
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      background-color: #f8fafd;
      padding: 80px 20px;
    }

    .about-container {
      max-width: 1400px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 24px;
      box-shadow: 0 10px 40px rgba(8, 20, 40, 0.08);
      padding: 70px 50px;
    }

    .about-header {
      text-align: center;
      margin-bottom: 60px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .about-header h2 {
      font-size: 42px;
      font-weight: 800;
      margin: 0 0 20px;
      color: var(--primary-dark);
      letter-spacing: -0.5px;
      line-height: 1.2;
    }

    .about-header .lead {
      font-size: 18px;
      line-height: 1.7;
      color: var(--text-muted);
      font-weight: 500;
      margin: 0;
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    .feature-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      padding: 35px 30px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow);
      display: flex;
      flex-direction: column;
      gap: 20px;
      transition: all 0.3s ease;
      cursor: pointer;
      min-height: 280px;
      position: relative;
      overflow: hidden;
    }

    .feature-card:hover {
      background: var(--primary);
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
      border-color: var(--primary-dark);
    }

    .feature-card:hover::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: var(--accent);
    }

    .feature-head {
      display: flex;
      gap: 18px;
      align-items: center;
    }

    .feature-icon {
      width: 70px;
      height: 70px;
      border-radius: 18px;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 28px;
      flex-shrink: 0;
      transition: all 0.3s ease;
    }

    .feature-card:hover .feature-icon {
      background: var(--primary-dark);
      color: var(--text-light);
      transform: scale(1.05);
    }

    .feature-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
      transition: color 0.3s ease;
    }

    .feature-card:hover .feature-title {
      color: var(--text-light);
    }

    .feature-desc {
      color: var(--text-muted);
      font-size: 16px;
      line-height: 1.7;
      margin: 0;
      transition: color 0.3s ease;
      flex-grow: 1;
    }

    .feature-card:hover .feature-desc {
      color: rgba(255, 255, 255, 0.9);
    }

    .feature-actions {
      margin-top: 10px;
    }

    .read-more {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 24px;
      border-radius: 10px;
      border: 2px solid var(--text-dark);
      color: var(--text-dark);
      background: transparent;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .read-more:hover {
      background: rgba(0, 0, 0, 0.05);
    }

    .feature-card:hover .read-more {
      border-color: rgba(255, 255, 255, 0.4);
      color: var(--text-light);
    }

    .feature-card:hover .read-more:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    /* Mobile Responsiveness */
    @media (max-width: 1200px) {
      .cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
      }
      
      .about-container {
        padding: 60px 40px;
      }
    }

    @media (max-width: 900px) {
      .cards-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
        margin: 0 auto;
      }
      
      .feature-desc {
        display: block;
      }
      
      .about-header h2 {
        font-size: 36px;
      }
    }

    @media (max-width: 768px) {
      .about-theme {
        padding: 60px 15px;
      }
      
      .about-container {
        padding: 50px 30px;
        border-radius: 20px;
      }
      
      .about-header {
        margin-bottom: 50px;
      }
      
      .about-header h2 {
        font-size: 32px;
      }
      
      .about-header .lead {
        font-size: 17px;
      }
      
      .feature-card {
        padding: 30px 25px;
        min-height: 250px;
      }
      
      .feature-icon {
        width: 60px;
        height: 60px;
        font-size: 24px;
      }
    }

    @media (max-width: 480px) {
      .about-container {
        padding: 40px 20px;
      }
      
      .about-header h2 {
        font-size: 28px;
      }
      
      .feature-card {
        padding: 25px 20px;
        min-height: auto;
      }
      
      .feature-head {
        gap: 15px;
      }
      
      .feature-icon {
        width: 55px;
        height: 55px;
        font-size: 22px;
        border-radius: 14px;
      }
      
      .feature-title {
        font-size: 20px;
      }
      
      .feature-desc {
        font-size: 15px;
      }
      
      .read-more {
        padding: 10px 20px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- ABOUT US SECTION -->
  <section class="about-theme" aria-labelledby="aboutHeading">
    <div class="about-container">
      <div class="about-header">
        <h2 id="aboutHeading">About Gyan Ganga Institute</h2>
        <p class="lead">Gyan Ganga Institute blends academic rigor with real-world training, campus life and student development — preparing graduates who lead with skill and integrity.</p>
      </div>

      <div class="cards-grid" id="featuresGrid">
        <!-- 1 -->
        <article class="feature-card" data-key="sports" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-basketball"></i></div>
            <h3 class="feature-title">Sports Facilities</h3>
          </div>
          <p class="feature-desc">State-of-the-art grounds, courts and coaching support for cricket, football, basketball and athletics. Regular inter-college competitions and fitness programs.</p>
          <div class="feature-actions">
            <a href="/sports" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>

        <!-- 2 -->
        <article class="feature-card" data-key="fests" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-music"></i></div>
            <h3 class="feature-title">Fests & Cultural Events</h3>
          </div>
          <p class="feature-desc">Annual tech and cultural fests that bring industry, students and artists together — workshops, competitions, concerts and networking opportunities.</p>
          <div class="feature-actions">
            <a href="/fests" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>

        <!-- 3 -->
        <article class="feature-card" data-key="training" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-chalkboard-teacher"></i></div>
            <h3 class="feature-title">Training Programs</h3>
          </div>
          <p class="feature-desc">Industry-aligned training: coding bootcamps, soft-skills sessions, internships and placement preparation run by our training & placement cell.</p>
          <div class="feature-actions">
            <a href="/training" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>

        <!-- 4 -->
        <article class="feature-card" data-key="courses" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></div>
            <h3 class="feature-title">Academic Programs</h3>
          </div>
          <p class="feature-desc">Undergraduate and postgraduate programs across engineering, management and applied sciences — updated curriculum, experienced faculty and research focus.</p>
          <div class="feature-actions">
            <a href="/courses" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>

        <!-- 5 -->
        <article class="feature-card" data-key="management" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
            <h3 class="feature-title">Leadership & Management</h3>
          </div>
          <p class="feature-desc">Strong governance and experienced leadership focused on academic excellence, student welfare and transparent administration.</p>
          <div class="feature-actions">
            <a href="/management" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>

        <!-- 6 -->
        <article class="feature-card" data-key="research" tabindex="0" role="button" aria-pressed="false">
          <div class="feature-head">
            <div class="feature-icon" aria-hidden="true"><i class="fa-solid fa-flask"></i></div>
            <h3 class="feature-title">Research & Labs</h3>
          </div>
          <p class="feature-desc">Dedicated laboratories, research centers and faculty-led projects that encourage innovation, publications and industry collaboration.</p>
          <div class="feature-actions">
            <a href="/research" class="read-more">Read more <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>
      </div>
    </div>
  </section>

  <script>
    // Make cards keyboard & click accessible and enable "click to toggle active" (same effect as hover).
    (function () {
      const grid = document.getElementById('featuresGrid');
      if (!grid) return;
      const cards = Array.from(grid.querySelectorAll('.feature-card'));

      function clearActive(except) {
        cards.forEach(c => {
          if (c !== except) {
            c.classList.remove('active');
            c.setAttribute('aria-pressed', 'false');
          }
        });
      }

      cards.forEach(card => {
        // Click toggles active state (useful on touch)
        card.addEventListener('click', (e) => {
          const isActive = card.classList.toggle('active');
          card.setAttribute('aria-pressed', String(isActive));
          if (isActive) clearActive(card);
          e.currentTarget.focus(); // keep focus for accessibility
        });

        // Keyboard: Enter or Space should toggle
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            card.click();
          }
          // Escape clears active
          if (e.key === 'Escape') {
            card.classList.remove('active');
            card.setAttribute('aria-pressed', 'false');
          }
        });
      });

      // Clicking outside any card clears active states (mobile convenience)
      document.addEventListener('click', (ev) => {
        if (!grid.contains(ev.target)) clearActive(null);
      }, { passive: true });
    })();
  </script>
</body>
</html>