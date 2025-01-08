<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name, last_name, email, password, 
                user_type, created_at, fillier, group_column
            ) VALUES (
                :first_name, :last_name, :email, :password, 
                'student', CURRENT_TIMESTAMP, :fillier, :group_column
            )
        ");
        
        $stmt->execute([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'fillier' => $_POST['fillier'],
            'group_column' => $_POST['group_column']
        ]);
        
        $_SESSION['success_message'] = "✨ Student successfully added!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "⚠️ Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --accent: #06b6d4;
            --success: #10b981;
            --error: #ef4444;
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
            min-height: 100vh;
        }

        .nav-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1002;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-toggle i {
            color: var(--text);
            font-size: 1.5rem;
        }

        .nav-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1001;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            color: var(--text);
        }

        .logo i {
            color: var(--primary);
            animation: float 3s ease-in-out infinite;
        }

        .nav-menu {
            padding: 2rem;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: var(--text);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .nav-link i {
            font-size: 1.2rem;
            min-width: 24px;
        }

        .sidebar-footer {
            padding: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .container {
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .nav-toggle {
                display: block;
            }

            .container {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2rem;
            }
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: 300px;
            }

            .form-card {
                padding: 1.5rem;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .alert {
                margin: 1rem;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.5s ease;
        }

        .header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            background: linear-gradient(135deg, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            animation: slideInDown 0.4s ease;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .form-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            position: relative;
        }

        .form-floating input,
        .form-floating select {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text);
            transition: all 0.3s ease;
        }

        .form-floating input:focus,
        .form-floating select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
        }

        .form-floating label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            padding: 0 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .form-floating input:focus ~ label,
        .form-floating input:not(:placeholder-shown) ~ label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            background: rgba(15, 23, 42, 0.6);
            color: var(--primary);
        }

        select {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5rem;
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--text);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary i {
            font-size: 1.1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-1rem);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-card {
                padding: 1.5rem;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>
    <button class="nav-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay"></div>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Portal</span>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="#" class="nav-link active">
                <i class="fas fa-user-plus"></i>
                <span>Add Student</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="teacher.php" class="nav-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Teacher Page</span>
            </a>
        </div>
    </aside>
    <div class="container">
        <div class="header">
            <h1>Add New Student</h1>
        </div>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        <div class="form-card">
            <form action="" method="POST">
                <div class="form-grid">
                    <div class="form-group form-floating">
                        <input type="text" id="first_name" name="first_name" placeholder=" " required>
                        <label for="first_name">First Name</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="text" id="last_name" name="last_name" placeholder=" " required>
                        <label for="last_name">Last Name</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label for="email">Email Address</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label for="password">Password</label>
                    </div>
                    <div class="form-group">
                        <select id="fillier" name="fillier" required>
                            <option value="">Select Field of Study</option>
                            <option value="developpement digital">Developpement Digital</option>
                            <option value="gestion entreprise">Gestion Entreprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select id="group_column" name="group_column" required>
                            <option value="">Select Group</option>
                            <option value="group1">Group 1</option>
                            <option value="group2">Group 2</option>
                            <option value="group3">Group 3</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Student
                </button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.querySelector('.nav-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');

        navToggle.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        function toggleMenu() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            navToggle.querySelector('i').classList.toggle('fa-bars');
            navToggle.querySelector('i').classList.toggle('fa-times');
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && sidebar.classList.contains('open')) {
                toggleMenu();
            }
        });
    });
    </script>
</body>
</html>