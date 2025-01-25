
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, fillier, group_column 
        FROM users 
        WHERE user_id = :user_id AND user_type = 'student'
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: logout.php");
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM exams e 
        WHERE (e.group_id = :group_id OR e.group_id IS NULL)
        AND (e.filiere_id = :filiere_id OR e.filiere_id IS NULL)
        AND e.end_date > NOW() 
        AND e.status = 'not_started'
        ORDER BY e.start_date ASC
    ");
    $stmt->execute([
        'group_id' => $student['group_column'],
        'filiere_id' => $student['fillier']
    ]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get student's exam results
    $stmt = $pdo->prepare("
        SELECT e.title as exam_name, er.score, er.submitted_at
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        WHERE er.student_id = :student_id
        ORDER BY er.submitted_at DESC
    ");
    $stmt->execute(['student_id' => $_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "An error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ExamOnline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #0ea5e9;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --background: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --card-bg: rgba(30, 41, 59, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1rem 5%;
            background: rgba(15, 23, 42, 0.9);
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .main-content {
            padding-top: 5rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 5%;
        }

        .dashboard-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            color: transparent;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            background: var(--surface);
            border-left: 4px solid var(--warning);
        }

        .alert-error {
            border-left-color: var(--error);
        }

        .student-info {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .exam-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        .exam-card:hover {
            transform: translateY(-5px);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--text);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: var(--card-bg);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .results-table th,
        .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .update-form {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: var(--surface);
            color: var(--text);
        }

        @media (max-width: 768px) {
            .nav-links {
                position: fixed;
                top: 4rem;
                left: 0;
                right: 0;
                background: var(--surface);
                padding: 1rem;
                flex-direction: column;
                display: none;
            }

            .nav-links.active {
                display: flex;
            }

            .menu-toggle {
                display: block;
                cursor: pointer;
                color: var(--text);
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="logo">
                <i class="fas fa-graduation-cap"></i>
                ExamOnline
            </a>
           
            <div class="nav-links">
                <a href="#home" class="btn btn-primary nav-link">Dashboard</a>
                <a href="#exams" class="btn btn-primary nav-link">Available Exams</a>
                <a href="#results" class="btn btn-primary nav-link">Results</a>
                <a href="#profile" class="btn btn-primary nav-link">Profile</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <section id="home" class="page active">
                <div class="dashboard-header">
                    <h1>Welcome, <?= htmlspecialchars($student['first_name']) ?>!</h1>
                    <p>Your Exam Portal Dashboard</p>
                </div>

                <div class="student-info">
                    <h2>Student Information</h2>
                    <p><strong>Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                    <p><strong>Field:</strong> <?= htmlspecialchars($student['fillier']) ?></p>
                    <p><strong>Group:</strong> <?= htmlspecialchars($student['group_column']) ?></p>
                </div>
            </section>

            <section id="exams" class="page">
                <h2>Available Exams</h2>
                <div class="exams-grid">
                    <?php if (!empty($exams)): ?>
                        <?php foreach ($exams as $exam): ?>
                            <div class="exam-card">
                                <h3><?= htmlspecialchars($exam['title']) ?></h3>
                                <p><?= htmlspecialchars($exam['description']) ?></p>
                                <p><strong>Duration:</strong> <?= htmlspecialchars($exam['duration']) ?> minutes</p>
                                <p><strong>Starts:</strong> <?= date('M j, Y g:i A', strtotime($exam['start_date'])) ?></p>
                                <p><strong>Ends:</strong> <?= date('M j, Y g:i A', strtotime($exam['end_date'])) ?></p>
                                
                                <?php
                                $now = new DateTime();
                                $start = new DateTime($exam['start_date']);
                                $end = new DateTime($exam['end_date']);
                                ?>

                                <?php if ($now >= $start && $now <= $end): ?>
                                    <a href="take_exam.php?id=<?= $exam['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-pen"></i> Take Exam
                                    </a>
                                <?php elseif ($now < $start): ?>
                                    <button class="btn btn-primary" disabled>
                                        <i class="fas fa-clock"></i> Not Started
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No exams are currently available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="results" class="page">
                <h2>Exam Results</h2>
                <?php if (!empty($results)): ?>
                    <div class="table-responsive">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Submission Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['exam_name']) ?></td>
                                        <td><?= htmlspecialchars($result['score']) ?>%</td>
                                        <td><?= date('M j, Y g:i A', strtotime($result['submitted_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No exam results available.</p>
                <?php endif; ?>
            </section>

            <section id="profile" class="page">
                <h2>Update Profile</h2>
               <form class="update-form" action="update_profile.php" method="POST">
    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
    </div>
    <div class="form-group">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>
    </div>
    <div class="form-group">
        <label for="new_password">New Password (leave blank to keep current)</label>
        <input type="password" id="new_password" name="new_password">
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password">
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Update Profile
    </button>
</form>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation handling
            const navLinks = document.querySelectorAll('.nav-link');
            const pages = document.querySelectorAll('.page');
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinksContainer = document.querySelector('.nav-links');

            // Mobile menu toggle
            menuToggle?.addEventListener('click', () => {
                navLinksContainer.classList.toggle('active');
            });

            // Navigation between pages
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Hide mobile menu after click
                    navLinksContainer.classList.remove('active');

                    // Get target page ID
                    const targetId = link.getAttribute('href').substring(1);
                    
                    // Update active states
                    pages.forEach(page => {
                        page.classList.remove('active');
                        if (page.id === targetId) {
                            page.classList.add('active');
                            // Update URL hash without scrolling
                            history.pushState(null, '', `#${targetId}`);
                        }
                    });

                    // Update active nav link
                    navLinks.forEach(navLink => {
                        navLink.classList.remove('active');
                    });
                    link.classList.add('active');
                });
            });

            // Handle password confirmation validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const updateForm = document.querySelector('.update-form');

            updateForm?.addEventListener('submit', function(e) {
                if (newPasswordInput.value) {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        e.preventDefault();
                        alert('New password and confirmation do not match!');
                    }
                }
            });

            // Handle URL hash on page load
            const hash = window.location.hash;
            if (hash) {
                const targetLink = document.querySelector(`[href="${hash}"]`);
                if (targetLink) {
                    targetLink.click();
                }
            }

            // Add countdown timer for active exams
            const examCards = document.querySelectorAll('.exam-card');
            
            examCards.forEach(card => {
                const startDate = new Date(card.dataset.startDate);
                const endDate = new Date(card.dataset.endDate);
                const now = new Date();

                if (now >= startDate && now <= endDate) {
                    // Create timer element
                    const timer = document.createElement('div');
                    timer.classList.add('exam-timer');
                    
                    // Update timer every second
                    const updateTimer = () => {
                        const now = new Date();
                        const timeLeft = endDate - now;
                        
                        if (timeLeft <= 0) {
                            timer.innerHTML = 'Exam ended';
                            card.querySelector('.btn-primary').disabled = true;
                            return;
                        }

                        const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                        timer.innerHTML = `Time remaining: ${hours}h ${minutes}m ${seconds}s`;
                    };

                    updateTimer();
                    setInterval(updateTimer, 1000);
                    card.appendChild(timer);
                }
            });
        });

        // Add some CSS dynamically for new elements
        const style = document.createElement('style');
        style.textContent = `
            .exam-timer {
                margin-top: 1rem;
                padding: 0.5rem;
                background: var(--surface);
                border-radius: 0.5rem;
                text-align: center;
                font-weight: 500;
                color: var(--warning);
            }

            .nav-link.active {
                background: var(--primary-dark);
            }

            .page {
                display: none;
                animation: fadeIn 0.3s ease-in-out;
            }

            .page.active {
                display: block;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .table-responsive {
                overflow-x: auto;
                margin: 1rem -1rem;
                padding: 0 1rem;
            }

            @media (max-width: 768px) {
                .menu-toggle {
                    display: block;
                }

                .nav-links {
                    display: none;
                }

                .nav-links.active {
                    display: flex;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
