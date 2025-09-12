<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - Gyan Ganga</title>
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .contact-theme {
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

    .contact-container {
      width: 1400px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .contact-content {
      display: flex;
    }

    .info-section {
      flex: 1;
      background: var(--primary);
      color: var(--text-light);
      padding: 50px 40px;
      position: relative;
      overflow: hidden;
    }

    .info-section::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }

    .info-section::after {
      content: '';
      position: absolute;
      bottom: -80px;
      left: -80px;
      width: 250px;
      height: 250px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }

    .college-name2 {
      font-size: 32px;
      font-weight: 800;
      margin: 0 0 20px;
      line-height: 1.2;
      position: relative;
      z-index: 2;
    }

    .college-intro {
      font-size: 16px;
      line-height: 1.7;
      margin: 0 0 30px;
      opacity: 0.9;
      position: relative;
      z-index: 2;
    }

    .contact-details {
      margin-bottom: 40px;
      position: relative;
      z-index: 2;
    }

    .contact-item {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      margin-bottom: 20px;
    }

    .contact-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 18px;
    }

    .contact-text {
      font-size: 15px;
      line-height: 1.6;
      opacity: 0.9;
    }

    .contact-text strong {
      display: block;
      font-weight: 600;
      margin-bottom: 3px;
    }

    .cta-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      position: relative;
      z-index: 2;
    }

    .btn {
      padding: 14px 28px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 15px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn-primary {
      background: var(--accent);
      color: var(--text-dark);
      border: none;
    }

    .btn-primary:hover {
      background: var(--accent-dark);
      transform: translateY(-2px);
    }

    .btn-outline {
      background: transparent;
      color: var(--text-light);
      border: 2px solid rgba(255, 255, 255, 0.4);
    }

    .btn-outline:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.8);
      transform: translateY(-2px);
    }

    .form-section {
      flex: 1;
      padding: 50px 40px;
    }

    .form-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary-dark);
      margin: 0 0 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-size: 15px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid var(--border-light);
      border-radius: 8px;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
      background: #f9fbfe;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(26, 79, 142, 0.2);
    }

    textarea.form-input {
      min-height: 120px;
      resize: vertical;
    }

    .form-submit {
      background: var(--primary);
      color: white;
      border: none;
      padding: 16px 32px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 10px;
    }

    .form-submit:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    .callback-option {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 20px;
      font-size: 15px;
      color: var(--text-muted);
    }

    .callback-option input {
      width: 18px;
      height: 18px;
    }

    .mobile-contact-details {
      display: none;
    }

    /* Mobile Responsiveness */
    @media (max-width: 992px) {
      .contact-content {
        flex-direction: column;
      }
      
      .info-section {
        display: none;
      }
      
      .mobile-contact-details {
        display: block;
        padding: 30px;
        background: var(--primary-light);
        border-top: 1px solid var(--border-light);
      }
      
      .mobile-contact-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 0 0 20px;
        text-align: center;
      }
      
      .mobile-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
      }
      
      .mobile-contact-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
      }
      
      .mobile-contact-text {
        font-size: 15px;
        line-height: 1.6;
        color: var(--text-dark);
      }
      
      .mobile-contact-text strong {
        display: block;
        font-weight: 600;
        margin-bottom: 3px;
        color: var(--primary-dark);
      }
    }

    @media (max-width: 768px) {
      .contact-theme {
        padding: 60px 15px;
      }
      
      .form-section {
        padding: 40px 30px;
      }
      
      .form-title {
        font-size: 24px;
      }
      
      .college-name2 {
        font-size: 28px;
      }
      
      .btn {
        padding: 12px 24px;
        font-size: 14px;
      }
    }

    @media (max-width: 576px) {
      .contact-theme {
        padding: 50px 10px;
      }
      
      .form-section {
        padding: 30px 20px;
      }
      
      .form-title {
        font-size: 22px;
        margin-bottom: 25px;
      }
      
      .cta-buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .mobile-contact-details {
        padding: 25px 20px;
      }
    }
  </style>
</head>
<body>
  <!-- CONTACT SECTION -->
  <section class="contact-theme">
    <div class="contact-container">
      <div class="contact-content">
        <!-- Left Info Section (Hidden on Mobile) -->
        <div class="info-section">
          <h2 class="college-name2">Gyan Ganga Institute of Technology & Sciences</h2>
          <p class="college-intro">A premier institute offering quality education in engineering and technology with state-of-the-art infrastructure and experienced faculty.</p>
          
          <div class="contact-details">
            <div class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="contact-text">
                <strong>Address</strong>
                P.O. Tilwara Ghat, Near Bargi Hills, Jabalpur 482003
              </div>
            </div>
            
            <div class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-phone-alt"></i>
              </div>
              <div class="contact-text">
                <strong>GGITS Telephone</strong>
                8770834794
              </div>
            </div>
            
            <div class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-user-tie"></i>
              </div>
              <div class="contact-text">
                <strong>Registrar: Dr. Sudeepto Mukherjee</strong>
                9584667722
              </div>
            </div>
          </div>
          
          <div class="cta-buttons">
            <a href="#" class="btn btn-primary">
              <i class="fas fa-download"></i> Get Brochure
            </a>
            <a href="#" class="btn btn-outline">
              <i class="fas fa-info-circle"></i> Admission Inquiry
            </a>
          </div>
        </div>
        
        <!-- Right Form Section -->
        <div class="form-section">
          <h3 class="form-title">Get in Touch</h3>
          <form id="contactForm">
            <div class="form-group">
              <label for="name" class="form-label">Your Name</label>
              <input type="text" id="name" class="form-input" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" id="email" class="form-input" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" id="phone" class="form-input" placeholder="Enter your phone number" required>
            </div>
            
            <div class="form-group">
              <label for="message" class="form-label">Your Message</label>
              <textarea id="message" class="form-input" placeholder="How can we help you?" required></textarea>
            </div>
            
            <button type="submit" class="form-submit">
              <i class="fas fa-paper-plane"></i> Send Message
            </button>
            
            <div class="callback-option">
              <input type="checkbox" id="callback" checked>
              <label for="callback">Please call me back to discuss</label>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Mobile Contact Details (Visible only on Mobile) -->
      <div class="mobile-contact-details">
        <h4 class="mobile-contact-title">Contact Information</h4>
        
        <div class="mobile-contact-item">
          <div class="mobile-contact-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="mobile-contact-text">
            <strong>Address</strong>
            P.O. Tilwara Ghat, Near Bargi Hills, Jabalpur 482003
          </div>
        </div>
        
        <div class="mobile-contact-item">
          <div class="mobile-contact-icon">
            <i class="fas fa-phone-alt"></i>
          </div>
          <div class="mobile-contact-text">
            <strong>GGITS Telephone</strong>
            8770834794
          </div>
        </div>
        
        <div class="mobile-contact-item">
          <div class="mobile-contact-icon">
            <i class="fas fa-user-tie"></i>
          </div>
          <div class="mobile-contact-text">
            <strong>Registrar: Dr. Sudeepto Mukherjee</strong>
            9584667722
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    // Form submission handling
    document.addEventListener('DOMContentLoaded', function() {
      const contactForm = document.getElementById('contactForm');
      
      contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const message = document.getElementById('message').value;
        const callback = document.getElementById('callback').checked;
        
        // Simple validation
        if (!name || !email || !phone || !message) {
          alert('Please fill all required fields');
          return;
        }
        
        // In a real application, you would send this data to a server
        console.log('Form submitted:', { name, email, phone, message, callback });
        
        // Show success message
        alert('Thank you for your message! We will contact you soon.');
        
        // Reset form
        contactForm.reset();
        document.getElementById('callback').checked = true;
      });
    });
  </script>
</body>
</html>