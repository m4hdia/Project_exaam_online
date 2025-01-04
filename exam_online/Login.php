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
    <link rel="stylesheet" href="login.css">
   
</head>
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