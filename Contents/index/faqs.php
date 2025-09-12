<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News & FAQs - Gyan Ganga</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .news-faqs-theme {
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

    .news-faqs-container {
      max-width: 1500px;
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

    .content-grid {
      display: grid;
      grid-template-columns: 1fr 1.5fr;
      gap: 30px;
    }

    .news-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .news-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      padding: 20px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .news-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .news-header {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      margin-bottom: 15px;
    }

    .news-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 18px;
      flex-shrink: 0;
      margin-top: 5px;
    }

    .news-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
      line-height: 1.4;
      flex-grow: 1;
    }

    .news-desc {
      color: var(--text-muted);
      font-size: 15px;
      line-height: 1.6;
      margin: 0 0 15px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .news-actions {
      margin-top: auto;
    }

    .news-read-more {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 8px;
      border: 2px solid var(--primary);
      color: var(--primary);
      background: transparent;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 14px;
    }

    .news-read-more:hover {
      background: var(--primary);
      color: var(--text-light);
    }

    /* FAQ Section */
    .faq-section {
      background: #ffffff;
      border-radius: var(--card-radius);
      padding: 30px;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow);
      height: 100%;
    }

    .faq-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary-dark);
      margin: 0 0 25px;
      text-align: center;
    }

    .faq-item {
      margin-bottom: 15px;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border-light);
    }

    .faq-question {
      padding: 16px 20px;
      background: var(--primary-light);
      color: var(--primary-dark);
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.3s ease;
      font-size: 16px;
    }

    .faq-question:hover {
      background: var(--primary);
      color: var(--text-light);
    }

    .faq-question i {
      transition: transform 0.3s ease;
      font-size: 14px;
    }

    .faq-answer {
      padding: 0 20px;
      max-height: 0;
      overflow: hidden;
      transition: all 0.3s ease;
      background: #ffffff;
      color: var(--text-muted);
      line-height: 1.7;
      font-size: 15px;
    }

    .faq-item.active .faq-answer {
      padding: 20px;
      max-height: 300px;
    }

    .faq-item.active .faq-question i {
      transform: rotate(180deg);
    }

    /* Mobile Responsiveness */
    @media (max-width: 1200px) {
      .content-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      
      .news-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 900px) {
      .news-faqs-theme {
        padding: 60px 15px;
      }
      
      .section-header h2 {
        font-size: 36px;
      }
      
      .section-header .lead {
        font-size: 17px;
      }
      
      .news-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .news-desc {
        display: none;
      }
      
      .news-header {
        margin-bottom: 10px;
      }
    }

    @media (max-width: 768px) {
      .section-header h2 {
        font-size: 32px;
      }
      
      .section-header {
        margin-bottom: 40px;
      }
      
      .news-card {
        padding: 18px;
      }
      
      .news-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
      }
      
      .news-title {
        font-size: 16px;
      }
      
      .news-read-more {
        padding: 7px 14px;
        font-size: 13px;
      }
      
      .faq-section {
        padding: 25px;
      }
      
      .faq-title {
        font-size: 24px;
      }
      
      .faq-question {
        padding: 14px 18px;
        font-size: 15px;
      }
    }

    @media (max-width: 600px) {
      .news-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .news-faqs-theme {
        padding: 50px 10px;
      }
      
      .section-header h2 {
        font-size: 28px;
      }
      
      .news-card {
        padding: 15px;
      }
      
      .news-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
        border-radius: 8px;
      }
      
      .news-title {
        font-size: 15px;
      }
      
      .news-read-more {
        padding: 6px 12px;
        font-size: 12px;
      }
      
      .faq-section {
        padding: 20px;
      }
      
      .faq-title {
        font-size: 22px;
        margin-bottom: 20px;
      }
      
      .faq-question {
        padding: 12px 15px;
        font-size: 14px;
      }
      
      .faq-answer {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- NEWS & FAQS SECTION -->
  <section class="news-faqs-theme" aria-labelledby="newsFaqsHeading">
    <div class="news-faqs-container">
      <div class="section-header">
        <h2 id="newsFaqsHeading">Campus News & FAQs</h2>
        <p class="lead">Stay updated with the latest happenings at Gyan Ganga and find answers to common questions.</p>
      </div>

      <div class="content-grid">
        <!-- News Grid -->
        <div class="news-grid">
          <!-- News 1 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-trophy"></i></div>
              <h3 class="news-title">Gyan Ganga Wins National Robotics Competition</h3>
            </div>
            <p class="news-desc">Our engineering team secured first place in the National Robotics Championship held at IIT Delhi.</p>
            <div class="news-actions">
              <a href="/news/robotics-win" class="news-read-more">Read more</a>
            </div>
          </article>
          
          <!-- News 2 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-calendar-days"></i></div>
              <h3 class="news-title">Annual Tech Fest 'Technovate' Starts Next Week</h3>
            </div>
            <p class="news-desc">Three days of technical workshops, competitions and guest speakers from industry leaders.</p>
            <div class="news-actions">
              <a href="/news/technovate" class="news-read-more">Read more</a>
            </div>
          </article>
          
          <!-- News 3 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-graduation-cap"></i></div>
              <h3 class="news-title">Placement Season Begins with 90% Early Offers</h3>
            </div>
            <p class="news-desc">Top companies have already made offers to our final year students with record compensation packages.</p>
            <div class="news-actions">
              <a href="/news/placements" class="news-read-more">Read more</a>
            </div>
          </article>
          
          <!-- News 4 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-microscope"></i></div>
              <h3 class="news-title">New Research Lab Inaugurated by Education Minister</h3>
            </div>
            <p class="news-desc">State-of-the-art research facility opened for advanced studies in AI and renewable energy.</p>
            <div class="news-actions">
              <a href="/news/research-lab" class="news-read-more">Read more</a>
            </div>
          </article>
          
          <!-- News 5 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-basketball"></i></div>
              <h3 class="news-title">Basketball Team Qualifies for Inter-University Finals</h3>
            </div>
            <p class="news-desc">After a thrilling semi-final match, our team will compete in the national finals next month.</p>
            <div class="news-actions">
              <a href="/news/basketball" class="news-read-more">Read more</a>
            </div>
          </article>
          
          <!-- News 6 -->
          <article class="news-card">
            <div class="news-header">
              <div class="news-icon"><i class="fa-solid fa-book"></i></div>
              <h3 class="news-title">New Library Extended Hours During Examination Period</h3>
            </div>
            <p class="news-desc">24/7 access to library resources available for students during the final examination weeks.</p>
            <div class="news-actions">
              <a href="/news/library" class="news-read-more">Read more</a>
            </div>
          </article>
        </div>
        
        <!-- FAQ Section -->
        <div class="faq-section">
          <h3 class="faq-title">Frequently Asked Questions</h3>
          
          <div class="faq-item active">
            <div class="faq-question">
              What are the eligibility criteria for B.Tech admissions? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              Candidates must have passed 10+2 with Physics and Mathematics as compulsory subjects along with one of Chemistry/Biology/Biotechnology/Technical Vocational subject. Minimum 50% marks (45% for reserved categories) in the above subjects taken together.
            </div>
          </div>
          
          <div class="faq-item">
            <div class="faq-question">
              Does the institute provide hostel facilities? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              Yes, we provide separate hostel facilities for boys and girls with modern amenities including Wi-Fi, recreational areas, 24/7 security, and mess facilities serving nutritious meals.
            </div>
          </div>
          
          <div class="faq-item">
            <div class="faq-question">
              What is the placement record of Gyan Ganga? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              We have an excellent placement record with over 90% of our students placed in top companies. Our highest package offered was ₹42 LPA, with an average package of ₹8.5 LPA for the 2023 batch.
            </div>
          </div>
          
          <div class="faq-item">
            <div class="faq-question">
              Are there scholarship programs available? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              Yes, we offer merit-based scholarships for top performers in entrance exams and board examinations. Need-based scholarships are also available for economically weaker sections.
            </div>
          </div>

          <div class="faq-item">
           <div class="faq-question">
             Scholarship Programs Announced <i class="fa-solid fa-chevron-down"></i>
           </div>
           <div class="faq-answer">
             Merit-based and need-based scholarships are open for new admissions. Eligible students can apply for government scholarships, institute-level financial aid, and special scholarships for top-performing students.
           </div>
          </div>

          <div class="faq-item">
           <div class="faq-question">
             What is the admission process for B.Tech? <i class="fa-solid fa-chevron-down"></i>
           </div>
           <div class="faq-answer">
            Admissions are based on JEE Main scores followed by MP state counseling. Direct admissions are also available under institute-level quota as per government norms.
           </div>
           </div>
          
          <div class="faq-item">
            <div class="faq-question">
              What industries do your graduates typically work in? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              Our graduates work across various sectors including IT services, product companies, core engineering industries, research organizations, consulting firms, and many have also pursued higher studies at prestigious institutions.
            </div>
          </div>
          
          <div class="faq-item">
            <div class="faq-question">
              How does the institute support research and innovation? <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              We have dedicated research centers, innovation labs, provide seed funding for student projects, organize hackathons, and encourage faculty-student collaboration on research papers and patents.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    // FAQ functionality
    document.addEventListener('DOMContentLoaded', function() {
      const faqItems = document.querySelectorAll('.faq-item');
      
      faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
          // Close all other FAQ items
          faqItems.forEach(otherItem => {
            if (otherItem !== item) {
              otherItem.classList.remove('active');
            }
          });
          
          // Toggle current item
          item.classList.toggle('active');
        });
      });
    });
  </script>
</body>
</html>