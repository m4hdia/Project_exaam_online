
<?php
session_start();
if (!isset($_SESSION['user_id']) ) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';


try {
    // Récupérer le nombre de demandes en attente
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->fetchColumn();
      $stmt = $pdo->query("SELECT * FROM exams ORDER BY end_date DESC");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
    $stats = [
        'student' => $pdo->query("SELECT COUNT(*) FROM users where user_type='student' AND status = 'accepted'")->fetchColumn(),
        'teacher' => $pdo->query("SELECT COUNT(*) FROM users where user_type='teacher'AND status = 'accepted'")->fetchColumn(),
       
    ];
    $groupedResults=$pdo->query("SELECT COUNT(*) FROM student ")->fetchColumn();

   

    $stmt->execute();
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "A system error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard V2</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="teacher.css" rel="stylesheet">
   
</head>
<style>
/* Styles de base */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    background: #f8f9fa;
    color: #333;
}

a {
            text-decoration: none;
            color: inherit; /* Optional: Keep link text the same color as the surrounding text */
        }


/* Compact Action Buttons */
.actions {
    display: flex;
    gap: 8px; /* Espace entre les boutons */
    align-items: center; /* Alignement vertical */
}


@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}


/* Responsive Design */
@media (max-width: 768px) {
    /* Table adjustments */
    table {
        font-size: 12px;
    }

    th, td {
        padding: 12px;
    }

   

    /* Adjust button sizes */
    .btn-edit, .btn-delete {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Stack buttons vertically on small screens */
    .actions {
        flex-direction: column;
        gap: 4px;
    }

    /* Adjust header layout */
    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-bar {
        width: 100%;
        margin-bottom: 10px;
    }

    .user-menu {
        width: 100%;
        justify-content: space-between;
    }

    /* Adjust sidebar for mobile */
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 10px;
    }

    .nav-menu {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .nav-link {
        flex: 1 1 45%;
        text-align: center;
    }

    /* Adjust stats grid */
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .stat-card {
        padding: 10px;
    }

    .stat-value {
        font-size: 20px;
    }

    .stat-label {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    /* Further adjustments for very small screens */
    table {
        font-size: 10px;
    }

    th, td {
        padding: 8px;
    }

    .btn-edit, .btn-delete {
        padding: 4px 8px;
        font-size: 10px;
    }

    .stat-card {
        padding: 8px;
    }

    .stat-value {
        font-size: 18px;
    }

    .stat-label {
        font-size: 10px;
    }
}
:root {
    --primary-color: #4f46e5;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --bg-primary: #ffffff;
    --bg-secondary: #f3f4f6;
    --border-radius: 16px;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.exams-layout {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(600px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--bg-secondary);
}

.exam-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden;
}

.exam-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.exam-card-inner {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.exam-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator.published {
    background: var(--success-color);
}

.status-indicator.draft {
    background: var(--warning-color);
}

.status-text {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.exam-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.exam-header h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--text-primary);
    font-weight: 600;
}

.exam-id {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.exam-description-wrapper {
    color: var(--text-secondary);
    line-height: 1.6;
}

.exam-timing {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.timing-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.timing-item i {
    color: var(--primary-color);
    font-size: 1.25rem;
}

.timing-info {
    display: flex;
    flex-direction: column;
}

.timing-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.timing-value {
    color: var(--text-primary);
    font-weight: 500;
}

.countdown-timer {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 12px;
}

.timer-container {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.timer-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    min-width: 60px;
}

.timer-block .time {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.timer-block .label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.exam-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--bg-secondary);
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.publish-btn {
    background: var(--success-color);
    color: white;
}

.publish-btn:hover {
    background: #059669;
}

.edit-btn {
    background: var(--primary-color);
    color: white;
}

.edit-btn:hover {
    background: #4338ca;
}

.delete-btn {
    background: var(--danger-color);
    color: white;
}

.delete-btn:hover {
    background: #dc2626;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    margin: 2rem;
}

.empty-state-icon {
    font-size: 3rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .exams-layout {
        grid-template-columns: 1fr;
    }
    
    .exam-timing {
        flex-direction: column;
        gap: 1rem;
    }
    
    .timer-container {
        flex-wrap: wrap;
        justify-content: center;
    }
}
/
  /* Search Bar Styling */
    .search-bar {
        display: flex;
        align-items: center;
        background-color: #f1f3f4;
        padding: 0.5rem 1rem;
        border-radius: 24px;
        width: 300px;
    }

    .search-icon {
        margin-right: 0.5rem;
        color: #666;
    }

    .search-input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 1rem;
        width: 100%;
    }

    /* User Menu Styling */
    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .profile-icon:hover {
        opacity: 0.8;
    }

    /* Logout Button Styling */
   /* From Uiverse.io by vinodjangid07 */ 
.Btn {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 45px;
  height: 45px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition-duration: .3s;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199);
  background-color: rgb(255, 65, 65);
}

/* plus sign */
.sign {
  width: 100%;
  transition-duration: .3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sign svg {
  width: 17px;
}

.sign svg path {
  fill: white;
}
/* text */
.text {
  position: absolute;
  right: 0%;
  width: 0%;
  opacity: 0;
  color: white;
  font-size: 1.2em;
  font-weight: 600;
  transition-duration: .3s;
}
/* hover effect on button width */
.Btn:hover {
  width: 125px;
  border-radius: 40px;
  transition-duration: .3s;
}

.Btn:hover .sign {
  width: 30%;
  transition-duration: .3s;
  padding-left: 20px;
}
/* hover effect button's text */
.Btn:hover .text {
  opacity: 1;
  width: 70%;
  transition-duration: .3s;
  padding-right: 10px;
}
/* button click effect*/
.Btn:active {
  transform: translate(2px ,2px);
}
</style>
<body>
   <div class="sidebar">
        <div class="logo">Teacher</div>
        <nav class="nav-menu">
            <a class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="Addstudents.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Add Users</span>
            </a>
            <a href="createxam.php" class="nav-link">
                <i class="fas fa-edit"></i>
                <span>Create Exam</span>
            </a>
           <a href="allstudent.php" class="nav-link">
                <i class="fas fa-user-graduate"></i>
                <span>View Students</span>
            </a>
           
        </nav>
    </div>
    <main class="main-content">
      <header class="header">

    <div class="search-bar">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search..." id="searchInput">
    </div>

 
    <div class="user-menu">
        <!-- Profile Icon -->
        <a href="profile.php" class="profile-link" style="text-decoration: none; color: inherit;">
            <i class="fas fa-user-circle profile-icon" style="font-size: 1.5rem; cursor: pointer; margin-right: 1rem;"></i>
        </a>

     
<a href="logout.php" style="text-decoration: none;">
    <button class="Btn">
        <div class="sign">
            <svg viewBox="0 0 512 512">
                <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
            </svg>
        </div>
        <div class="text">Logout</div>
    </button>
</a>



    </div>
</header>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-graduate" style="color: #6366f1;"></i>
                <div class="stat-value"><?php echo number_format($stats['student']); ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher" style="color: #3b82f6;"></i>
                <div class="stat-value"><?php echo number_format($stats['teacher']); ?></div>
                <div class="stat-label">Teachers</div>
            </div>
         
           
        </div>

    </div>
    <h1>Liste des Examens</h1>

  <?php if (!empty($exams)): ?>
    <div class="exams-layout">
        <?php foreach ($exams as $exam): ?>
            <div class="exam-card">
                <div class="exam-card-inner">
                    <div class="exam-status">
                        <span class="status-indicator <?php echo strtolower($exam['status']); ?>"></span>
                        <span class="status-text"><?php echo htmlspecialchars($exam['status']); ?></span>
                    </div>

                    <div class="exam-main-content">
                        <div class="exam-header">
                            <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <div class="exam-meta">
                                <span class="exam-id">#<?php echo $exam['id']; ?></span>
                            </div>
                        </div>

                        <div class="exam-description-wrapper">
                            <p class="exam-description"><?php echo htmlspecialchars($exam['description']); ?></p>
                        </div>

                        <div class="exam-details">
                            <div class="exam-timing">
                                <div class="timing-item">
                                    <i class="far fa-calendar-alt"></i>
                                    <div class="timing-info">
                                        <span class="timing-label">Début</span>
                                        <span class="timing-value"><?php echo date('d M Y - H:i', strtotime($exam['start_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="timing-item">
                                    <i class="far fa-calendar-check"></i>
                                    <div class="timing-info">
                                        <span class="timing-label">Fin</span>
                                        <span class="timing-value"><?php echo date('d M Y - H:i', strtotime($exam['end_date'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="countdown-timer" data-end-date="<?php echo htmlspecialchars($exam['end_date']); ?>">
                                <div class="timer-container">
                                    <div class="timer-block">
                                        <span class="time days">00</span>
                                        <span class="label">Jours</span>
                                    </div>
                                    <div class="timer-block">
                                        <span class="time hours">00</span>
                                        <span class="label">Heures</span>
                                    </div>
                                    <div class="timer-block">
                                        <span class="time minutes">00</span>
                                        <span class="label">Minutes</span>
                                    </div>
                                    <div class="timer-block">
                                        <span class="time seconds">00</span>
                                        <span class="label">Secondes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="exam-actions">
                        <?php if ($exam['status'] !== 'published'): ?>
                            <button class="action-btn publish-btn" data-exam-id="<?php echo $exam['id']; ?>">
                                <i class="fas fa-upload"></i>
                                <span>Publier</span>
                            </button>
                        <?php endif; ?>
                        
                        <button class="action-btn edit-btn" onclick="window.location.href='edit_exam.php?id=<?php echo $exam['id']; ?>'">
                            <i class="fas fa-edit"></i>
                            <span>Modifier</span>
                        </button>
                        
                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $exam['id']; ?>)">
                            <i class="fas fa-trash"></i>
                            <span>Supprimer</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h3>Aucun examen disponible</h3>
        <p>Commencez par créer votre premier examen</p>
    </div>
<?php endif; ?>
    
    </div>



    <a href="tech.php">
  <div class="floating-action-btn" id="addButton">
    <i class="fas fa-plus" id="addIcon" ></i>
    <div class="notification-badge"><?= $pendingCount > 0 ? $pendingCount : '' ?></div>
     <?php if ($pendingCount > 0): ?>
    <?php endif; ?>
    
</div>
</a>


</section>
</main>
<script>
function updateTimers() {
    document.querySelectorAll('.countdown-timer').forEach(timerContainer => {
        const endDate = new Date(timerContainer.dataset.endDate);
        const now = new Date();
        const diff = endDate - now;

        if (diff <= 0) {
            timerContainer.innerHTML = '<div class="timer-ended">Examen terminé</div>';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        timerContainer.querySelector('.days').textContent = String(days).padStart(2, '0');
        timerContainer.querySelector('.hours').textContent = String(hours).padStart(2, '0');
        timerContainer.querySelector('.minutes').textContent = String(minutes).padStart(2, '0');
        timerContainer.querySelector('.seconds').textContent = String(seconds).padStart(2, '0');
    });
}

function confirmDelete(examId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
        window.location.href = `delete_exam.php?id=${examId}`;
    }
}

// Handle publish button clicks
document.querySelectorAll('.publish-btn').forEach(button => {
    button.addEventListener('click', async function() {
        const examId = this.dataset.examId;
        if (confirm('Voulez-vous publier cet examen ?')) {
            try {
                const response = await fetch(`publish_exam.php?id=${examId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Erreur lors de la publication de l\'examen');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            }
        }
    });
});

// Update timers every second
setInterval(updateTimers, 1000);
updateTimers(); // Initial update
</script>