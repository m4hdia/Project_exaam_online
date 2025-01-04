<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

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