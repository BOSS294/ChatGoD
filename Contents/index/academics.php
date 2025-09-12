<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Academic Programs - Gyan Ganga</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .academics-theme {
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
      background-color: #ffffff;
      padding: 80px 20px;
    }

    .academics-header {
      text-align: center;
      margin-bottom: 60px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .academics-header h2 {
      font-size: 42px;
      font-weight: 800;
      margin: 0 0 20px;
      color: var(--primary-dark);
      letter-spacing: -0.5px;
      line-height: 1.2;
    }

    .academics-header .lead {
      font-size: 18px;
      line-height: 1.7;
      color: var(--text-muted);
      font-weight: 500;
      margin: 0;
    }

    .programs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 40px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .program-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      padding: 0;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow);
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
      cursor: pointer;
      overflow: hidden;
      height: 100%;
    }

    .program-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
    }

    .program-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-top-left-radius: var(--card-radius);
      border-top-right-radius: var(--card-radius);
    }

    .program-content {
      padding: 25px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    .program-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0 0 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .program-title .star {
      color: var(--accent);
      font-size: 18px;
    }

    .program-desc {
      color: var(--text-muted);
      font-size: 16px;
      line-height: 1.7;
      margin: 0 0 20px;
      flex-grow: 1;
    }

    .program-highlights {
      background-color: var(--primary-light);
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .program-highlights p {
      margin: 0;
      font-size: 15px;
      color: var(--primary-dark);
      font-weight: 600;
    }

    .program-actions {
      margin-top: auto;
    }

    .learn-more {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 24px;
      border-radius: 10px;
      border: 2px solid var(--primary);
      color: var(--primary);
      background: transparent;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .learn-more:hover {
      background: var(--primary);
      color: var(--text-light);
    }

    /* Mobile Responsiveness */
    @media (max-width: 900px) {
      .programs-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
      }
      
      .academics-header h2 {
        font-size: 36px;
      }
    }

    @media (max-width: 768px) {
      .academics-theme {
        padding: 60px 15px;
      }
      
      .academics-header {
        margin-bottom: 50px;
      }
      
      .academics-header h2 {
        font-size: 32px;
      }
      
      .academics-header .lead {
        font-size: 17px;
      }
      
      .program-content {
        padding: 20px;
      }
      
      .program-title {
        font-size: 20px;
      }
    }

    @media (max-width: 480px) {
      .academics-header h2 {
        font-size: 28px;
      }
      
      .program-content {
        padding: 18px;
      }
      
      .program-title {
        font-size: 19px;
      }
      
      .program-desc {
        font-size: 15px;
      }
      
      .learn-more {
        padding: 10px 20px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- ACADEMIC PROGRAMS SECTION -->
  <section class="academics-theme" aria-labelledby="academicsHeading">
    <div class="academics-header">
      <h2 id="academicsHeading">Our Academic Programs</h2>
      <p class="lead">Explore our range of undergraduate and postgraduate programs designed to shape the future of technology and engineering.</p>
    </div>

    <div class="programs-grid" id="programsGrid">
      <!-- 1. CSE -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1555949963-ff9fe0c870eb?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Computer Science students working on code" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Computer Science & Engineering (CSE)</h3>
          <p class="program-desc">Core program with electives across software, algorithms, and computing fundamentals.</p>
          <div class="program-highlights">
            <p>Focus areas: Software Development, Algorithms, Computing Fundamentals</p>
          </div>
          <div class="program-actions">
            <a href="/cse" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>

      <!-- 2. AIML -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1573164713714-d95e436ab8d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Artificial Intelligence visualization" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Artificial Intelligence & Machine Learning (AIML) <span class="star">⭐</span></h3>
          <p class="program-desc">Specialization in Deep Learning, NLP, Computer Vision, Neural Networks.</p>
          <div class="program-highlights">
            <p>Direct path to careers in automation, AI research, and product innovation.</p>
          </div>
          <div class="program-actions">
            <a href="/aiml" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>

      <!-- 3. Data Science -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Data visualization on multiple screens" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Data Science <span class="star">⭐</span></h3>
          <p class="program-desc">Data Analytics, Big Data, Predictive Modeling, Data Engineering.</p>
          <div class="program-highlights">
            <p>Highly sought-after in finance, healthcare, e-commerce, and R&D.</p>
          </div>
          <div class="program-actions">
            <a href="/data-science" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>

      <!-- 4. ECE -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Electronics and communication components" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Electronics & Communication Engineering (ECE)</h3>
          <p class="program-desc">VLSI, IoT, Telecommunication, Embedded Systems.</p>
          <div class="program-highlights">
            <p>Career opportunities in telecommunications, embedded systems, and IoT development.</p>
          </div>
          <div class="program-actions">
            <a href="/ece" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>

      <!-- 5. Mechanical Engineering -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1581094288338-231b058b38b8?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Robotics and mechanical engineering" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Mechanical Engineering (ME)</h3>
          <p class="program-desc">Robotics, Manufacturing, CAD/CAM, Smart Materials.</p>
          <div class="program-highlights">
            <p>Prepare for careers in robotics, advanced manufacturing, and materials engineering.</p>
          </div>
          <div class="program-actions">
            <a href="/mechanical" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>

      <!-- 6. Civil Engineering -->
      <article class="program-card" tabindex="0">
        <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Civil engineering construction site" class="program-image">
        <div class="program-content">
          <h3 class="program-title">B.Tech in Civil Engineering (CE)</h3>
          <p class="program-desc">Sustainable construction & infrastructure development.</p>
          <div class="program-highlights">
            <p>Focus on sustainable construction practices and modern infrastructure development.</p>
          </div>
          <div class="program-actions">
            <a href="/civil" class="learn-more">Explore Program <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </article>
    </div>
  </section>
</body>
</html>