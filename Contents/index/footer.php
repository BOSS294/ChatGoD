<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gyan Ganga University - Footer</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafd;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .content {
      flex: 1;
      padding: 40px 20px;
      text-align: center;
      max-width: 1000px;
      margin: 0 auto;
    }
    
    .content h1 {
      color: #0d3a6e;
      margin-bottom: 20px;
    }
    
    .content p {
      color: #6b7b95;
      line-height: 1.6;
    }
    
    /* Footer Styles */
    .footer {
      background: linear-gradient(135deg, #0d3a6e 0%, #1a4f8e 100%);
      color: white;
      padding: 60px 0 30px;
      position: relative;
    }
    
    .footer-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .footer-logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo-image {
      width: 120px;
      height: 120px;
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      border-radius: 50%;
      padding: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .logo-image img {
      max-width: 100%;
      max-height: 100%;
    }
    
    .footer-logo h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 15px;
    }
    
    .footer-logo p {
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.6;
      opacity: 0.9;
    }
    
    .footer-nav {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      margin: 40px 0;
    }
    
    .nav-column h3 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }
    
    .nav-column h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 40px;
      height: 3px;
      background: #ff9e1b;
      border-radius: 3px;
    }
    
    .nav-links {
      list-style: none;
    }
    
    .nav-links li {
      margin-bottom: 12px;
    }
    
    .nav-links a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-block;
    }
    
    .nav-links a:hover {
      color: white;
      transform: translateX(5px);
    }
    
    .nav-links i {
      margin-right: 8px;
      font-size: 14px;
    }
    
    .footer-social {
      text-align: center;
      margin: 30px 0;
    }
    
    .social-icons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }
    
    .social-icon {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      transition: all 0.3s ease;
    }
    
    .social-icon:hover {
      background: #ff9e1b;
      transform: translateY(-3px);
    }
    
    .footer-bottom {
      text-align: center;
      padding-top: 30px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-bottom p {
      opacity: 0.8;
      font-size: 14px;
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 992px) {
      .footer-nav {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (max-width: 768px) {
      .footer {
        padding: 50px 0 20px;
      }
      
      .logo-image {
        width: 100px;
        height: 100px;
      }
      
      .footer-logo h2 {
        font-size: 24px;
      }
      
      .footer-logo p {
        font-size: 14px;
      }
      
      .nav-column h3 {
        font-size: 16px;
      }
      
      .nav-links a {
        font-size: 14px;
      }
    }
    
    @media (max-width: 576px) {
      .footer-nav {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      
      .nav-column {
        text-align: center;
      }
      
      .nav-column h3::after {
        left: 50%;
        transform: translateX(-50%);
      }
      
      .social-icons {
        gap: 15px;
      }
      
      .social-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
      }
      .footer-bottom {
        margin-bottom:90px;
      }
    }
  </style>
</head>
<body>


  <footer class="footer">
    <div class="footer-container">
      <!-- Logo and College Name -->
      <div class="footer-logo">
        <div class="logo-image">
          <!-- Replace with your actual logo image -->
          <img src="https://i.ibb.co/DfP3F87p/ggits-logo-removebg-preview.png" alt="Gyan Ganga University Logo">
        </div>
        <h2>Gyan Ganga University</h2>
        <p>Empowering minds through quality education, innovation, and research to create future leaders and responsible global citizens.</p>
      </div>
      
      <!-- Navigation Links -->
      <div class="footer-nav">
        <!-- Column 1 -->
        <div class="nav-column">
          <h3>Academics</h3>
          <ul class="nav-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> Programs</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Courses</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Faculty</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Research</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Library</a></li>
          </ul>
        </div>
        
        <!-- Column 2 -->
        <div class="nav-column">
          <h3>Admissions</h3>
          <ul class="nav-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> Apply Now</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Requirements</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Scholarships</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Campus Tour</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> FAQs</a></li>
          </ul>
        </div>
        
        <!-- Column 3 -->
        <div class="nav-column">
          <h3>Campus Life</h3>
          <ul class="nav-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> Events</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Student Clubs</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Housing</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Sports</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Facilities</a></li>
          </ul>
        </div>
        
        <!-- Column 4 -->
        <div class="nav-column">
          <h3>Quick Links</h3>
          <ul class="nav-links">
            <li><a href="#"><i class="fas fa-chevron-right"></i> About Us</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Contact</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> News & Events</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Careers</a></li>
            <li><a href="#"><i class="fas fa-chevron-right"></i> Support</a></li>
          </ul>
        </div>
      </div>
      
      <!-- Social Media Icons -->
      <div class="footer-social">
        <h3>Connect With Us</h3>
        <div class="social-icons">
          <a href="#" class="social-icon">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="social-icon">
            <i class="fab fa-linkedin-in"></i>
          </a>
          <a href="#" class="social-icon">
            <i class="far fa-envelope"></i>
          </a>
          <a href="#" class="social-icon">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="#" class="social-icon">
            <i class="fab fa-twitter"></i>
          </a>
        </div>
      </div>
      
      <!-- Copyright -->
      <div class="footer-bottom">
        <p>&copy; 2023 Gyan Ganga University. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    // Simple animation for the social icons
    document.addEventListener('DOMContentLoaded', function() {
      const socialIcons = document.querySelectorAll('.social-icon');
      
      socialIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-5px)';
        });
        
        icon.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0)';
        });
      });
    });
  </script>
</body>
</html>