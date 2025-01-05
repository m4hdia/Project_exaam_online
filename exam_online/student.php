<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}
// Get available exams
$stmt = $pdo->prepare("
    SELECT e.id, e.title, e.description, e.duration, e.start_date, e.end_date,
           CASE 
               WHEN ea.completion_time IS NOT NULL THEN 'Completed'
               WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'Available'
               WHEN NOW() < e.start_date THEN 'Upcoming'
               ELSE 'Expired'
           END as status
    FROM exams e
    LEFT JOIN exam_attempts ea ON e.id = ea.exam_id 
        AND ea.student_id = ?
    WHERE e.is_active = 1
    ORDER BY e.start_date DESC
");
$stmt->execute([$_SESSION['student_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    :root {
    --primary: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --text: #1f2937;
    --text-light: #6b7280;
    --background: #f3f4f6;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--background);
    margin: 0;
    min-height: 100vh;
}

.dashboard {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
    width: 280px;
    background: white;
    padding: 2rem;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    position: fixed;
    height: 100vh;
}

.logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 3rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 12px;
    color: var(--text);
    text-decoration: none;
    transition: all 0.2s ease;
}

.nav-item:hover {
    background: #f3f4f6;
}

.nav-item.active {
    background: var(--primary);
    color: white;
}

/* Main Content */
.main-content {
    flex: 1;
    margin-left: 280px;
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: var(--text);
}

.student-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Exam Grid */
.exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.exam-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.exam-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.exam-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.exam-status.available {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.exam-status.upcoming {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.exam-status.completed {
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary);
}

.exam-status.expired {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.exam-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 1rem;
}

.exam-description {
    color: var(--text-light);
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.exam-details {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
    font-size: 0.875rem;
}

.start-exam-btn, .view-result-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.start-exam-btn {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}

.start-exam-btn:hover {
    background: #4338ca;
    transform: translateY(-2px);
}

.view-result-btn {
    background: #f3f4f6;
    color: var(--text);
}

.view-result-btn:hover {
    background: #e5e7eb;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .exam-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .dashboard {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 1rem;
    }
    
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }
    
    .exam-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Portal</span>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="exams.php" class="nav-item active">
                    <i class="fas fa-pen"></i>
                    Exams
                </a>
                <a href="results.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Results
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <h1>Available Exams</h1>
                <div class="student-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                </div>
            </header>

            <div class="exam-grid">
                <?php foreach ($exams as $exam): ?>
                    <div class="exam-card">
                        <div class="exam-status <?php echo strtolower($exam['status']); ?>">
                            <?php echo $exam['status']; ?>
                        </div>
                        <h2 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h2>
                        <p class="exam-description"><?php echo htmlspecialchars($exam['description']); ?></p>
                        <div class="exam-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $exam['duration']; ?> minutes</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('M d, Y', strtotime($exam['start_date'])); ?></span>
                            </div>
                        </div>
                        <?php if ($exam['status'] === 'Available'): ?>
                            <a href="take_exam.php?id=<?php echo $exam['id']; ?>" 
                               class="start-exam-btn"
                               onclick="return confirm('Are you ready to start the exam? The timer will begin immediately.')">
                                <i class="fas fa-play"></i>
                                Start Exam
                            </a>
                        <?php elseif ($exam['status'] === 'Completed'): ?>
                            <a href="view_result.php?id=<?php echo $exam['id']; ?>" class="view-result-btn">
                                <i class="fas fa-eye"></i>
                                View Result
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>