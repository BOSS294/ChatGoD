<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Academy Partners - Gyan Ganga</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .academy-theme {
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
      --card-radius: 12px;
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      background-color: #f8fafd;
      padding: 80px 20px;
    }

    .academy-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .section-header {
      text-align: center;
      margin-bottom: 50px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .section-header h2 {
      font-size: 42px;
      font-weight: 800;
      margin: 0 0 20px;
      color: var(--primary-dark);
      letter-spacing: -0.5px;
      line-height: 1.2;
    }

    .section-header .lead {
      font-size: 18px;
      line-height: 1.7;
      color: var(--text-muted);
      font-weight: 500;
      margin: 0;
    }

    .academy-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
    }

    .academy-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
      border: 1px solid var(--border-light);
    }

    .academy-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .academy-image {
      height: 180px;
      background-color: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .academy-image img {
      max-width: 100%;
      max-height: 120px;
      object-fit: contain;
    }

    .academy-image i {
      font-size: 70px;
      color: var(--primary);
    }

    .academy-info {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .academy-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0 0 15px;
      text-align: center;
      line-height: 1.4;
    }

    .academy-description {
      color: var(--text-muted);
      line-height: 1.6;
      font-size: 15px;
      text-align: center;
      margin: 0;
      display: block;
    }

    /* Mobile Responsiveness */
    @media (max-width: 1024px) {
      .academy-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .academy-theme {
        padding: 60px 15px;
      }
      
      .section-header h2 {
        font-size: 36px;
      }
      
      .section-header .lead {
        font-size: 17px;
      }
      
      .academy-grid {
        gap: 20px;
      }
      
      .academy-image {
        height: 160px;
      }
      
      .academy-image i {
        font-size: 60px;
      }
      
      .academy-info {
        padding: 18px;
      }
      
      .academy-name {
        font-size: 17px;
        margin-bottom: 10px;
      }
      
      .academy-description {
        display: none;
      }
    }

    @media (max-width: 600px) {
      .academy-grid {
        grid-template-columns: 1fr;
        max-width: 400px;
        margin: 0 auto;
      }
      
      .section-header h2 {
        font-size: 32px;
      }
      
      .section-header {
        margin-bottom: 40px;
      }
      
      .academy-image {
        height: 150px;
      }
      
      .academy-image i {
        font-size: 50px;
      }
      
      .academy-name {
        font-size: 16px;
      }
    }

    @media (max-width: 480px) {
      .academy-theme {
        padding: 50px 10px;
      }
      
      .section-header h2 {
        font-size: 28px;
      }
      
      .academy-grid {
        gap: 15px;
      }
      
      .academy-image {
        height: 140px;
        padding: 15px;
      }
      
      .academy-info {
        padding: 15px;
      }
      
      .academy-name {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>
  <!-- ACADEMY SECTION -->
  <section class="academy-theme" aria-labelledby="academyHeading">
    <div class="academy-container">
      <div class="section-header">
        <h2 id="academyHeading">Our Academy Partners</h2>
        <p class="lead">Collaborating with industry leaders to provide world-class education and certification opportunities</p>
      </div>

      <div class="academy-grid">
        <!-- Cisco Networking Academy -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fa-solid fa-network-wired"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">Cisco Networking Academy</h3>
            <p class="academy-description">Gain hands-on IT experience and career-building skills with our networking programs.</p>
          </div>
        </div>
        
        <!-- AWS Educate -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fab fa-aws"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">AWS Educate</h3>
            <p class="academy-description">Cloud computing courses and certifications to advance your career in cloud technologies.</p>
          </div>
        </div>
        
        <!-- Red Hat Academy -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fa-solid fa-server"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">Red Hat Academy</h3>
            <p class="academy-description">Open source software training and certification for IT professionals.</p>
          </div>
        </div>
        
        <!-- Oracle Academy -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fa-solid fa-database"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">Oracle Academy</h3>
            <p class="academy-description">Database management and enterprise software development courses.</p>
          </div>
        </div>
        
        <!-- Pearson VUE -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">Pearson VUE Authorized Test Center</h3>
            <p class="academy-description">Official testing center for professional certification exams.</p>
          </div>
        </div>
        
        <!-- Indian Institute of Remote Sensing -->
        <div class="academy-card">
          <div class="academy-image">
            <i class="fa-solid fa-satellite"></i>
          </div>
          <div class="academy-info">
            <h3 class="academy-name">Indian Institute of Remote Sensing</h3>
            <p class="academy-description">Advanced geospatial technology and remote sensing education.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    // Simple animation for cards when they come into view
    document.addEventListener('DOMContentLoaded', function() {
      const academyCards = document.querySelectorAll('.academy-card');
      
      // Add initial state for animation
      academyCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      });
      
      // Function to check if element is in viewport
      function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
          rect.top >= 0 &&
          rect.left >= 0 &&
          rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
          rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
      }
      
      // Animate cards when they come into view
      function checkCards() {
        academyCards.forEach(card => {
          if (isInViewport(card)) {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }
        });
      }
      
      // Check on load and scroll
      window.addEventListener('load', checkCards);
      window.addEventListener('scroll', checkCards);
      
      // Initial check
      checkCards();
    });
  </script>
</body>
</html>