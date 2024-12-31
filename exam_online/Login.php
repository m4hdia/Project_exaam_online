<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) { 
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            switch ($user['user_type']) {
                case 'admin':
                    header("Location: admin.php");
                    break;
                case 'teacher':
                    header("Location: teacher.php");
                    break;
                case 'student':
                    header("Location: student.php");
                    break;
            }
            exit();
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamOnline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   
</head>
<style>
    
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --accent: #06b6d4;
            --success: #10b981;
            --background: #0f172a;
            --text: #f8fafc;
            --card-bg: rgba(30, 41, 59, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-background {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(45deg, #0f172a, #1e293b);
            overflow: hidden;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--primary) 0%, transparent 20%),
                        radial-gradient(circle at 80% 20%, var(--secondary) 0%, transparent 20%),
                        radial-gradient(circle at 20% 80%, var(--accent) 0%, transparent 20%);
            animation: backgroundAnimation 15s linear infinite;
            opacity: 0.1;
        }

        @keyframes backgroundAnimation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.5rem 5%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .logo i {
            color: var(--primary);
            transform: scale(1);
            transition: transform 0.3s ease;
        }

        .logo:hover i {
            transform: scale(1.2) rotate(360deg);
        }

        /* Main Content */
        .main-content {
            padding-top: 100px;
            min-height: 100vh;
        }

        /* Hero Section */
        .hero {
            padding: 4rem 5%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
        }

        .hero-container {
            max-width: 1400px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text {
            animation: slideInLeft 1s ease;
        }

        .hero-text h1 {
            font-size: 4rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--text), var(--accent));
            -webkit-background-clip: text;
            color: transparent;
            animation: gradientText 3s linear infinite;
        }

        @keyframes gradientText {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        .hero-text p {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        /* Login Card */
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 1s ease;
            transform: translateZ(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px) translateZ(0);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .login-card h2 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .login-card h2 i {
            color: var(--primary);
            animation: bounceIcon 2s infinite;
        }

        @keyframes bounceIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group i {
            position: absolute;
            right: 1rem;
            top: 2.7rem;
            color: #94a3b8;
        }

        /* Button Styles */
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: var(--text);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Features Section */
        .features {
            padding: 4rem 5%;
            background: rgba(30, 41, 59, 0.5);
        }
        a{
            
        }

        .features-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .feature-card:hover i {
            transform: scale(1.2);
        }

        .feature-card h3 {
            color: var(--text);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #94a3b8;
        }

        /* Animations */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Error Message */
       
        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .hero-text h1 {
                font-size: 3rem;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2.5rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

</style>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="logo">
                <i class="fas fa-graduation-cap"></i>
                ExamOnline
            </a>
            <div class="nav-links">
                <a href="#features" class="btn">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <section class="hero">
            <div class="hero-container">
                <div class="hero-text">
                    <h1 id="features">Master Your Future with Online Exams</h1>
                    <p>Experience the next generation of online examination. Advanced features, real-time results, and a seamless learning experience designed for modern education.</p>
                </div>

                <div class="login-card" >
                    <h2><i class="fas fa-user-graduate"></i>  Login</h2>
                  <?php if (!empty($error)): ?>
    <div class="error" style="color: red;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                            <i class="fas fa-lock"></i>
                        </div>
                        <button type="submit" class="btn">
                            Sign In <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="features" id="">
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h3>Smart Timing</h3>
                    <p>Advanced timing system with auto-submission and flexible duration settings.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Platform</h3>
                    <p>Enhanced security measures to maintain examination integrity.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Real-time Analytics</h3>
                    <p>Instant results and detailed performance analysis.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Ready</h3>
                    <p>Access your exams on any device, anywhere, anytime.</p>
                </div>
                <div class="feature-card">
                    
  
                    <i class="fa-solid fa-gears" style="color: #0fb6cc;"></i>
                    <h3>Entre faste</h3>
                    <p>Easily access your exams anytime, on any device, wherever life takes you."</p>
                </div>

                <div class="feature-card">
                    <i class="fa-solid fa-magnifying-glass" style="color: #0fb6cc;"></i>
                    <h3>Learn Anywhere</h3>
                    <p>Access your exams effortlessly on any device,</p>
                </div>
            </div>
        </section>
    </main>

   <script>
    // Loading Screen
    window.addEventListener('load', function() {
        const loader = document.getElementById('loading');
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }, 1000);
    });

    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Dynamic Background Animation
    const background = document.querySelector('.animated-background');
    document.addEventListener('mousemove', function(e) {
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        background.style.transform = `translate(${x * 10}px, ${y * 10}px)`;
    });
</script>

</body>
</html>