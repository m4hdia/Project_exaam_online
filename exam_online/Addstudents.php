<?php
session_start();
if (!isset($_SESSION['user_id']) ) {
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
            'group_column' => $_POST['group_column'] // This will store the selected group value
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
    <link href="addstudent.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<style>
    /* Variables */
:root {
  --primary: #470a79;
  --secondary: #7329b8;
  --success: #4cc9f0;
  --error: #ef476f;
  --dark: #2b2d42;
  --light: #f8f9fa;
  --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Reset & Base */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  background: var(--light);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: var(--gradient);
  padding: 2rem;
  position: fixed;
  height: 100vh;
  color: white;
  transition: transform 0.3s ease;
  z-index: 1000;
}

.logo {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 1.5rem;
  margin-bottom: 3rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo i {
  font-size: 2rem;
  animation: float 3s ease-in-out infinite;
}

.back-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: white;
  text-decoration: none;
  padding: 1rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  transition: var(--transition);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(-5px);
}

.back-button i {
  font-size: 1.2rem;
  transition: var(--transition);
}

.back-button:hover i:first-child {
  transform: translateX(-3px);
}

/* Hamburger Menu */
.hamburger {
  display: none;
  cursor: pointer;
  font-size: 24px;
  color: var(--dark);
  position: fixed;
  top: 20px;
  left: 20px;
  z-index: 1001;
}

/* Main Container */
.container {
  flex: 1;
  margin-left: 280px;
  padding: 2rem 3rem;
  transition: margin-left 0.3s ease;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2.5rem;
  color: var(--dark);
  animation: slideDown 0.5s ease-out;
}

/* Form Styles */
.form-card {
  background: white;
  border-radius: 16px;
  padding: 2.5rem;
  box-shadow: var(--shadow-lg);
  animation: slideUp 0.5s ease-out;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 2.5rem;
}

.form-group {
  position: relative;
}

.form-floating input,
select {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 1rem;
  transition: var(--transition);
  background: white;
}

.form-floating input:focus,
select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
  outline: none;
}

.form-floating {
  position: relative;
}

.form-floating label {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: white;
  padding: 0 0.25rem;
  color: #666;
  transition: var(--transition);
  pointer-events: none;
}

.form-floating input:focus + label,
.form-floating input:not(:placeholder-shown) + label {
  top: 0;
  transform: translateY(-50%) scale(0.85);
  color: var(--primary);
}

.required::after {
  content: '*';
  color: var(--error);
  margin-left: 4px;
}

/* Button */
.btn-primary {
  background: var(--gradient);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: var(--transition);
  box-shadow: var(--shadow-md);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn-primary:active {
  transform: translateY(0);
}

.btn-primary i {
  font-size: 1.2rem;
}

/* Alerts */
.alert {
  padding: 1rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  animation: slideIn 0.3s ease-out;
}

.alert-success {
  background: var(--success);
  color: white;
}

.alert-error {
  background: var(--error);
  color: white;
}

/* Animations */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .hamburger {
    display: block;
  }

  .container {
    margin-left: 0;
    padding: 1.5rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<body>
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            Add student
        </div>
         
        <!-- Add sidebar menu items here if needed -->
         <a href="teacher.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
    <i class="fas fa-chalkboard-teacher"></i>
    Back to Teacher Page
</a>
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
                        <label for="first_name" class="required">First Name</label>
                    </div>

                    <div class="form-group form-floating">
                        <input type="text" id="last_name" name="last_name" placeholder=" " required>
                        <label for="last_name" class="required">Last Name</label>
                    </div>

                    <div class="form-group form-floating">
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label for="email" class="required">Email Address</label>
                    </div>

                    <div class="form-group form-floating">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label for="password" class="required">Password</label>
                    </div>

                    <div class="form-group">
                        <label for="fillier" class="required">Field of Study</label>
                        <select id="fillier" name="fillier" required>
                            <option value="">Select Field</option>
                            <option value="developpement digital">Developpement digital</option>
                            <option value="gestion entreprise">gestion entreprise</option>
                          
                        </select>
                    </div>

                         <div class="form-group">
                        <label for="group_column" class="required">Group</label>
                        <select id="group_column" name="group_column" required>
                            <option value="" disabled selected>Select Group</option>
                            <option value="group1">GROUP 1</option>
                            <option value="group2">GROUP 2</option>
                            <option value="group3">GROUP 3</option>
                        </select>
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
        const form = document.querySelector('form');
        const submitButton = document.querySelector('.btn-primary');

        form.addEventListener('submit', function(e) {
            const email = this.querySelector('input[type="email"]').value;
            const password = this.querySelector('#password').value;

            if (!validateEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters long');
                return;
            }

            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            submitButton.disabled = true;
        });

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            
            const container = document.querySelector('.container');
            const existingAlert = container.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            container.insertBefore(errorDiv, container.firstChild);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }
    });
    </script>
</body>
</html>