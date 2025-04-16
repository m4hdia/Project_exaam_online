<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the filiere_id based on the name selected from the dropdown
        $filiere_stmt = $pdo->prepare("SELECT id FROM filieres WHERE name = :filiere_name");
        $filiere_stmt->execute(['filiere_name' => $_POST['filiere']]);
        $filiere = $filiere_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$filiere) {
            throw new PDOException("Invalid field of study selected");
        }
        
        // Get the group_id based on the name selected from the dropdown
        $group_stmt = $pdo->prepare("SELECT id FROM student_groups WHERE name = :group_name");
        $group_stmt->execute(['group_name' => $_POST['group']]);
        $group = $group_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            throw new PDOException("Invalid group selected");
        }
        
        // Hash the password for security
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Now insert the user with proper foreign keys
        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name, last_name, email, password, 
                user_type, filiere_id, group_id, status
            ) VALUES (
                :first_name, :last_name, :email, :password, 
                'student', :filiere_id, :group_id, 'pending'
            )
        ");
        
        $stmt->execute([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'password' => $hashedPassword,
            'filiere_id' => $filiere['id'],
            'group_id' => $group['id']
        ]);
        
        $_SESSION['success_message'] = "Student added successfully!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch filieres for the dropdown
$filieres_query = $pdo->query("SELECT id, name FROM filieres ORDER BY name");
$filieres = $filieres_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch groups for the dropdown
$groups_query = $pdo->query("SELECT id, name FROM student_groups ORDER BY name");
$groups = $groups_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-gradient-start: #0f172a;
            --bg-gradient-end: #1e293b;
            --card-bg: rgba(255, 255, 255, 0.05);
            --input-bg: rgba(255, 255, 255, 0.07);
            --input-focus-bg: rgba(255, 255, 255, 0.1);
            --border-color: rgba(255, 255, 255, 0.1);
            --border-hover: rgba(255, 255, 255, 0.3);
            --text-primary: #f8fafc;
            --text-secondary: rgba(248, 250, 252, 0.7);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-glow: 0 0 15px var(--primary-glow);
            --animation-timing: cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            min-height: 100vh;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Animation Elements */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .bg-animation .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(225deg, var(--primary-color), transparent);
            opacity: 0.1;
        }

        .bg-animation .circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation: float 30s infinite alternate var(--animation-timing);
        }

        .bg-animation .circle:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation: float 20s infinite alternate-reverse var(--animation-timing);
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            100% {
                transform: translate(50px, 50px) rotate(180deg);
            }
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeInDown 1s var(--animation-timing);
        }

        .header h1 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            background: linear-gradient(to right, #fff, var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.025em;
        }

        .header p {
            font-size: 1.125rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 1.5rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInDown 0.5s var(--animation-timing);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(8px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.5s var(--animation-timing);
            animation: fadeIn 1s var(--animation-timing);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-body {
            padding: 2rem;
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

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: color 0.3s var(--animation-timing);
        }

        .form-group:focus-within .form-label {
            color: var(--primary-light);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            transition: all 0.3s var(--animation-timing);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            background: var(--input-focus-bg);
            box-shadow: 0 0 0 3px var(--primary-glow);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: rgba(248, 250, 252, 0.4);
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        select.form-control option {
            background-color: var(--bg-gradient-end);
            color: var(--text-primary);
            padding: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s var(--animation-timing);
            border: none;
            font-size: 1rem;
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
            transition: width 0.6s, height 0.6s;
            z-index: 0;
        }

        .btn:active::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md), 0 0 10px var(--primary-glow);
        }

        .btn-primary:active {
            transform: translateY(0);
            opacity: 0.9;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--input-bg);
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .btn-outline:active {
            transform: translateY(0);
        }

        .btn span, .btn i {
            position: relative;
            z-index: 1;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            gap: 1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 var(--primary-glow);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        /* Custom Select Styling */
        .custom-select-wrapper {
            position: relative;
        }

        .custom-select-wrapper .form-control {
            cursor: pointer;
        }

        /* Focus Animation for Form Groups */
        .form-control:focus {
            animation: pulse 1.5s infinite;
        }

        /* Loading indicator for button */
        .loading-indicator {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn.is-loading .loading-indicator {
            display: inline-block;
        }

        .btn.is-loading span {
            opacity: 0.7;
        }

        /* Tooltip Styles */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            text-align: center;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation Elements -->
    <div class="bg-animation">
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle" style="color: var(--danger-color);"></i>
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="header">
            <h1>Student Registration</h1>
            <p>Add a new student to the system by filling out the form below</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Student Information</h2>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="studentForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="Enter first name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Enter last name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="student@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Create password" required>
                        </div>
                        
                        <div class="form-group custom-select-wrapper">
                            <label for="filiere" class="form-label">Field of Study</label>
                            <select class="form-control" id="filiere" name="filiere" required>
                                <option value="">Select field of study</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo htmlspecialchars($filiere['name']); ?>">
                                        <?php echo htmlspecialchars($filiere['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group custom-select-wrapper">
                            <label for="group" class="form-label">Student Group</label>
                            <select class="form-control" id="group" name="group" required>
                                <option value="">Select group</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo htmlspecialchars($group['name']); ?>">
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="teacher.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Dashboard</span>
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <div class="loading-indicator"></div>
                            <i class="fas fa-user-plus"></i>
                            <span>Register Student</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Form animation and validation
        document.addEventListener('DOMContentLoaded', function() {
            // Get all form inputs for animations
            const formInputs = document.querySelectorAll('.form-control');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('studentForm');

            // Add focus and blur event listeners for input animations
            formInputs.forEach(input => {
                // Initial animation on page load with slight delay for each element
                setTimeout(() => {
                    input.style.opacity = '0';
                    input.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        input.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        input.style.opacity = '1';
                        input.style.transform = 'translateY(0)';
                    }, Array.from(formInputs).indexOf(input) * 100);
                }, 500);

                // Add focus/blur animations
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('is-focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('is-focused');
                });
                
                // For select elements, add custom animation
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        if (this.value) {
                            this.classList.add('selected');
                        } else {
                            this.classList.remove('selected');
                        }
                    });
                }
            });

            // Form submission animation
            form.addEventListener('submit', function(e) {
                // We don't actually prevent default here as we want the form to submit
                // but we add the loading animation
                submitBtn.classList.add('is-loading');
                
                // Remove the loading class after form submission
                // In a real app, you might want to handle this differently
                setTimeout(() => {
                    submitBtn.classList.remove('is-loading');
                }, 2000);
            });

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>
</body>
</html>