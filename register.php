<?php
// register.php
session_start();

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $userType = $_POST['userType'] ?? '';
    $filiere_id = ($userType === 'student') ? ($_POST['filiere'] ?? null) : null;
    $group_id = ($userType === 'student') ? (int)($_POST['group'] ?? null) : null;

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword) || empty($userType)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = 'Email already exists';
        } else {
            // For students, validate that the selected group exists
            if ($userType === 'student') {
                $stmt = $pdo->prepare("SELECT id FROM student_groups WHERE id = ?");
                $stmt->execute([$group_id]);
                if ($stmt->rowCount() === 0) {
                    $error = 'Invalid group selected';
                }
            }

            if (empty($error)) {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Set status based on user type
                $status = ($userType === 'student') ? 'pending' : 'approved';

                try {
                    // Insert new user into the database
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, user_type, filiere_id, group_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, $userType, $filiere_id, $group_id, $status])) {
                        // Get the user ID of the newly registered user
                        $userId = $pdo->lastInsertId();
                        
                        // Store user information in session
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_type'] = $userType;
                        $_SESSION['status'] = $status;
                        $_SESSION['first_name'] = $firstName;
                        
                        // Redirect based on user type and status
                        if ($userType === 'student') {
                            header("Location: game.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit();
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamOnline - Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #0ea5e9;
            --accent: #06b6d4;
            --success: #10b981;
            --background: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --card-bg: rgba(30, 41, 59, 0.8);
            --error: #ef4444;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .animated-background {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, var(--background) 0%, var(--surface) 100%);
            overflow: hidden;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 30%),
                        radial-gradient(circle at 20% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 30%);
            animation: backgroundAnimation 20s ease infinite;
            opacity: 0.5;
        }

        @keyframes backgroundAnimation {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.25rem 5%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            color: var(--primary);
            transition: transform 0.3s ease;
        }

        .logo:hover i {
            transform: rotate(20deg);
        }

        .main-content {
            padding-top: 100px;
            min-height: calc(100vh - 400px);
            display: flex;
            align-items: center;
        }

        .hero {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 5%;
        }

        .hero-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .hero-text {
            text-align: center;
            max-width: 600px;
            margin-bottom: 2rem;
        }

        .hero-text h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            color: var(--text-secondary);  
            font-size: 1.125rem;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 680px;
        }

        .login-card h2 {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text);
            font-size: 1.75rem;
            text-align: center;
            justify-content: center;
        }

        .login-card h2 i {
            color: var(--primary-light);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1.25rem;
            padding-left: 3rem;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group i {
            position: absolute;
            left: 1.25rem;
            top: 2.6rem;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .form-group input:focus + i,
        .form-group select:focus + i {
            color: var(--primary-light);
        }

        .student-fields {
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: var(--text);
            border: none;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.4);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(0);
        }

        .bt {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 1.25rem;
        }

        .bt:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.75rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer {
            background: var(--surface);
            color: var(--text);
            padding: 4rem 5%;
            margin-top: 4rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .user-type-selection {
            display: flex;
            margin-bottom: 2rem;
            gap: 1.5rem;
            justify-content: center;
        }

        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 1.5rem;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .user-type-option.active {
            border-color: var(--primary-light);
            background: rgba(99, 102, 241, 0.1);
        }

        .user-type-option i {
            font-size: 2rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .user-type-option.active i {
            color: var(--primary-light);
        }

        .user-type-option span {
            font-weight: 600;
        }

        /* Hidden radio input */
        .user-type-option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        @media (max-width: 768px) {
            .hero-container {
                padding: 1rem;
            }

            .login-card {
                padding: 1.75rem;
            }

            .hero-text h1 {
                font-size: 2rem;
            }

            .footer-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .user-type-selection {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>

    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="logo">
                <i class="fas fa-graduation-cap"></i>
                ExamOnline
            </a>
            <div class="nav-links">
                <a href="login.php" class="bt">Login</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <section class="hero">
            <div class="hero-container">
                <div class="hero-text">
                    <h1>Join Our Learning Community</h1>
                    <p>Create your account today and start your journey with ExamOnline. Access premium features and take control of your educational experience.</p>
                </div>

                <div class="login-card">
                    <h2><i class="fas fa-user-plus"></i> Create Your Account</h2>
                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="user-type-selection">
                            <label class="user-type-option" id="studentOption">
                                <input type="radio" name="userType" value="student" required>
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </label>
                            <label class="user-type-option" id="teacherOption">
                                <input type="radio" name="userType" value="teacher" required>
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teacher</span>
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" required>
                                <i class="fas fa-user"></i>
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" required>
                                <i class="fas fa-user"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                            <i class="fas fa-envelope"></i>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password (min 8 characters)</label>
                                <input type="password" id="password" name="password" minlength="8" required>
                                <i class="fas fa-lock"></i>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" minlength="8" required>
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>

                        <div class="student-fields" id="studentFields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="filiere">Filiere (Department)</label>
                                    <select id="filiere" name="filiere">
                                        <option value="">Select your department</option>
                                        <option value="1">Développement Digital</option>
                                        <option value="2">Gestion Entreprise</option>
                                    </select>
                                    <i class="fas fa-university"></i>
                                </div>

                                <div class="form-group">
                                    <label for="group">Group</label>
                                    <select id="group" name="group">
                                        <option value="">Select your group</option>
                                        <option value="1">Group 1</option>
                                        <option value="2">Group 2</option>
                                        <option value="3">Group 3</option>
                                    </select>
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn">
                            Create Account <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="login.php" class="bt">
                            <i class="fas fa-sign-in-alt"></i> Already have an account? Login
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>About ExamOnline</h3>
                <p>ExamOnline is a cutting-edge platform designed to revolutionize online examinations. We provide advanced features, real-time analytics, and a seamless experience for both students and teachers.</p>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Education Street, Knowledge City</li>
                    <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                    <li><i class="fas fa-envelope"></i> contact@examonline.com</li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // User type selection
        const studentOption = document.getElementById('studentOption');
        const teacherOption = document.getElementById('teacherOption');
        const studentFields = document.getElementById('studentFields');
        const studentInputs = studentFields.querySelectorAll('select');
        
        studentOption.addEventListener('click', function() {
            studentOption.classList.add('active');
            teacherOption.classList.remove('active');
            studentFields.style.display = 'block';
            studentInputs.forEach(input => input.required = true);
        });
        
        teacherOption.addEventListener('click', function() {
            teacherOption.classList.add('active');
            studentOption.classList.remove('active');
            studentFields.style.display = 'none';
            studentInputs.forEach(input => input.required = false);
        });
        
        // Password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            // Check if a user type is selected
            const userTypeSelected = document.querySelector('input[name="userType"]:checked');
            
            if (!userTypeSelected) {
                e.preventDefault();
                alert('Please select whether you are a Student or Teacher');
            }
        });

        // Page load animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
    </script>
</body>
</html>