<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gyan Ganga Institute</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            overflow-x: hidden;
        }

        .hero {
            position: relative;
            height: 100vh;
            min-height: 600px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            z-index: -2;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(4, 16, 49, 0.85) 0%, rgba(7, 33, 70, 0.8) 100%);
            z-index: -1;
        }

        .hero-content {
            max-width: 1000px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4fc3ff 0%, #00c853 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .logo i {
            font-size: 40px;
            color: white;
        }

        .college-name1{
            font-size: 2.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4fc3ff, #00c853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tagline {
            font-size: 1.2rem;
            font-weight: 300;
            margin-bottom: 30px;
            color: #cfeeff;
        }

        .description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4fc3ff 0%, #00c853 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(79, 195, 255, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(79, 195, 255, 0.6);
        }

        .btn-secondary {
            background: transparent;
            color: #4fc3ff;
            border: 2px solid #4fc3ff;
        }

        .btn-secondary:hover {
            background: rgba(79, 195, 255, 0.1);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .college-name {
                font-size: 2rem;
            }
            
            .tagline {
                font-size: 1.1rem;
            }
            
            .description {
                font-size: 1rem;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .college-name {
                font-size: 1.7rem;
            }
            
            .hero-content {
                padding: 20px;
            }
            
            .logo {
                width: 70px;
                height: 70px;
            }
            
            .logo i {
                font-size: 35px;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-background"></div>
        <div class="overlay"></div>
        
        <div class="hero-content">
            <div class="logo-container">
                <div class="logo">
                     <img src="https://i.ibb.co/DfP3F87p/ggits-logo-removebg-preview.png" alt="Gyan Ganga Logo" class="logo" />
                </div>
                <h1 class="college-name1">Gyan Ganga Institute</h1>
            </div>
            
            <p class="tagline">Knowledge • Excellence • Innovation</p>
            
            <p class="description">
                Gyan Ganga Institute of Technology and Sciences is a premier institution dedicated to excellence in education, research, and innovation. We empower students to become future leaders through quality education, state-of-the-art facilities, and industry-relevant programs.
            </p>
            
            <div class="cta-buttons">
                <button class="btn btn-primary">Explore Programs</button>
                <button class="btn btn-secondary">Campus Tour</button>
            </div>
        </div>
    </section>

    <script>
        // Simple animation for elements
        document.addEventListener('DOMContentLoaded', function() {
            const heroContent = document.querySelector('.hero-content');
            heroContent.style.opacity = 0;
            heroContent.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroContent.style.transition = 'opacity 1s ease, transform 1s ease';
                heroContent.style.opacity = 1;
                heroContent.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>