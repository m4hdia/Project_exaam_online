
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
        'student' => $pdo->query("SELECT COUNT(*) FROM users where user_type='student' ")->fetchColumn(),
        'teacher' => $pdo->query("SELECT COUNT(*) FROM users where user_type='teacher'")->fetchColumn(),
       
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

/* Table Styling */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
    animation: tableAppear 0.6s ease-out;
}
a {
            text-decoration: none;
            color: inherit; /* Optional: Keep link text the same color as the surrounding text */
        }
@keyframes tableAppear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header Styling */
thead {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

th {
    padding: 18px 24px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

/* Row Styling */
tr {
    position: relative;
}

td {
    padding: 16px 24px;
    color: #4b5563;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: linear-gradient(45deg, #f8fafc, #f1f5f9);
}

tbody tr:hover td {
    transform: translateX(5px);
}

/* Compact Action Buttons */
.actions {
    display: flex;
    gap: 8px; /* Espace entre les boutons */
    align-items: center; /* Alignement vertical */
}

/* Edit Button */
.btn-edit {
    background: #3b82f6; /* Couleur de fond */
    color: white; /* Couleur du texte */
    border: none; /* Pas de bordure */
    border-radius: 6px; /* Coins arrondis */
    padding: 8px 16px; /* Espacement intérieur */
    font-size: 14px; /* Taille de la police */
    font-weight: 500; /* Poids de la police */
    cursor: pointer; /* Curseur en forme de main */
    text-decoration: none; /* Pas de soulignement */
    transition: background-color 0.3s ease; /* Transition fluide */
    display: flex;
    align-items: center;
    gap: 6px; /* Espace entre l'icône et le texte */
}

.btn-edit:hover {
    background: #2563eb; /* Couleur de fond au survol */
}

/* Delete Button */
.btn-delete {
    background: #ef4444; /* Couleur de fond */
    color: white; /* Couleur du texte */
    border: none; /* Pas de bordure */
    border-radius: 6px; /* Coins arrondis */
    padding: 8px 16px; /* Espacement intérieur */
    font-size: 14px; /* Taille de la police */
    font-weight: 500; /* Poids de la police */
    cursor: pointer; /* Curseur en forme de main */
    text-decoration: none; /* Pas de soulignement */
    transition: background-color 0.3s ease; /* Transition fluide */
    display: flex;
    align-items: center;
    gap: 6px; /* Espace entre l'icône et le texte */
}

.btn-delete:hover {
    background: #dc2626; /* Couleur de fond au survol */
}

/* Button Icons */
.btn i {
    font-size: 14px; /* Taille des icônes */
}

/* Status Column */
td:nth-child(5) {
    position: relative;
}

td:nth-child(5)::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* Timer Column */
td:nth-child(6) {
    font-family: monospace;
    font-size: 15px;
    color: #3b82f6;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
}

/* Time's up animation */
td:contains("Time's up!") {
    color: #ef4444;
    animation: flash 1.5s infinite;
}

@keyframes flash {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Completed status */
td:contains("Completed") {
    color: #10b981;
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

    /* Hide less important columns on small screens */
    td:nth-child(3), /* Date de début */
    td:nth-child(4), /* Date de fin */
    th:nth-child(3),
    th:nth-child(4) {
        display: none;
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
}</style>
<body>
   <aside class="sidebar">
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
            <a href="Logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log out</span>
            </a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="header">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search..." id="searchInput">
            </div>
            <div class="user-menu">
                <i class="fas fa-bell" id="notificationIcon" style="margin-right: 1rem; cursor: pointer;"><a href="tech.php"></a></i>
                <div class="user-profile" style="display: inline-block; position: relative;">
                    <i class="fas fa-user-circle" style="font-size: 1.5rem; cursor: pointer;" id="profileIcon"></i>
                    <div class="profile-dropdown" style="display: none; position: absolute; right: 0; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 10px;">
                        <a href="profile.php" style="display: block; padding: 8px 20px; text-decoration: none; color: var(--text-1);">Profile</a>
                        <a href="logout.php" style="display: block; padding: 8px 20px; text-decoration: none; color: #f43f5e;">Logout</a>
                    </div>
                </div>
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
       <table>
    <thead>
        <tr>
            <th>Titre</th>
            <th>Description</th>
            <th>Date de début</th>
            <th>Date de fin</th>
            <th>Statut</th>
            <th>Timer</th> <!-- Nouvelle colonne pour le timer -->
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($exams as $exam): ?>
            <tr>
                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                <td><?php echo htmlspecialchars($exam['description']); ?></td>
                <td><?php echo htmlspecialchars($exam['start_date']); ?></td>
                <td><?php echo htmlspecialchars($exam['end_date']); ?></td>
                <td><?php echo htmlspecialchars($exam['status']); ?></td>
                <td class="timer-cell" data-end-date="<?php echo htmlspecialchars($exam['end_date']); ?>">
                    <!-- Le timer sera mis à jour par JavaScript -->
                </td>
                <td>
                    <div class="actions">
                        <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="delete_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-delete">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    <?php else: ?>
        <p>Aucun examen trouvé.</p>
    <?php endif; ?>
    <div class="container">
        <h1>Teacher Dashboard</h1>
        <?php if ($pendingCount > 0): ?>
            <div class="notification">
                You have <?= $pendingCount ?> pending student approvals.
                <a href="tech.php">Review now</a>
            </div>
        <?php endif; ?>
    </div>
    </div>



    <div class="floating-action-btn" id="addButton">
        <i class="fas fa-plus"></i>
        <div class="notification-badge">10</div>
</section>
</main>

<script>
    function updateTimers() {
    const timerCells = document.querySelectorAll('.timer-cell');
    const now = new Date().getTime(); // Temps actuel en millisecondes

    timerCells.forEach((cell) => {
        const endDateStr = cell.getAttribute('data-end-date');
        const endDate = new Date(endDateStr).getTime(); // Convertir la date de fin en millisecondes

        if (isNaN(endDate)) {
            cell.textContent = "Date invalide";
            return;
        }

        const remainingTime = endDate - now; // Temps restant en millisecondes

        if (remainingTime > 0) {
            // Convertir le temps restant en heures, minutes et secondes
            const hours = Math.floor(remainingTime / (1000 * 60 * 60));
            const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

            // Afficher le temps restant
            cell.textContent = `${hours}h ${minutes}m ${seconds}s`;
        } else {
            cell.textContent = "Time's up!";
        }
    });
}

// Mettre à jour les timers toutes les secondes
setInterval(updateTimers, 1000);

// Initialiser les timers au chargement de la page
window.onload = updateTimers;
 function updateTimers() {
        const timerCells = document.querySelectorAll('.timer-cell');
        const now = new Date().getTime();

        timerCells.forEach((cell) => {
            const endDateStr = cell.getAttribute('data-end-date');
            const endDate = new Date(endDateStr).getTime();

            if (isNaN(endDate)) {
                cell.textContent = "Date invalide";
                return;
            }

            const remainingTime = endDate - now;

            if (remainingTime > 0) {
                const hours = Math.floor(remainingTime / (1000 * 60 * 60));
                const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

                cell.textContent = `${hours}h ${minutes}m ${seconds}s`;
            } else {
                cell.textContent = "Time's up!";
            }
        });
    }

    setInterval(updateTimers, 1000);
    window.onload = updateTimers;
        

        const profileIcon = document.getElementById('profileIcon');
        const profileDropdown = document.querySelector('.profile-dropdown');
        
        profileIcon.addEventListener('click', () => {
            profileDropdown.style.display = profileDropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-profile')) {
                profileDropdown.style.display = 'none';
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            // Add your search logic here
        });

        // Add button click handler
        document.getElementById('addButton').addEventListener('click', () => {
            // Add your logic for the add button here
        });

        // Your existing card hover effects remain the same
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                card.style.transform = `
                perspective(1000px)
                rotateX(${(y - rect.height/2) / 20}deg)
                rotateY(${-(x - rect.width/2) / 20}deg)
                    translateZ(10px)
                    `;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'none';
            });
        });
        
        // Notification animation
        const notificationBadge = document.querySelector('.notification-badge');
        setInterval(() => {
            notificationBadge.style.transform = 'scale(1.2)';
            setTimeout(() => {
                notificationBadge.style.transform = 'scale(1)';
            }, 200);
        }, 3000);
        function showStudentDetails(studentData) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('studentDetailsContent');
    
            const resultsHtml = studentData.results.map(result => `
            <div class="border-b py-2">
            <div class="font-semibold">${result.exam_name}</div>
            <div class="text-sm">
            Score: ${result.score}%
            <br>
            Date: ${new Date(result.date_taken).toLocaleDateString()}
            </div>
            </div>
            `).join('');
            
            content.innerHTML = `
            <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
            <p><strong>ID:</strong> ${studentData.student.id}</p>
            <p><strong>Name:</strong> ${studentData.student.name}</p>
            <p><strong>Email:</strong> ${studentData.student.email}</p>
            </div>
            <div>
            <p><strong>Average Score:</strong> ${
                    studentData.results.length ? 
                    (studentData.results.reduce((sum, r) => sum + parseFloat(r.score), 0) / 
                    studentData.results.length).toFixed(1) + '%' : 'N/A'
                }</p>
                <p><strong>Total Exams:</strong> ${studentData.results.length}</p>
            </div>
            </div>
            <h4 class="font-bold mb-2">Exam History</h4>
            <div class="max-h-[300px] overflow-y-auto">
            ${resultsHtml || '<p>No exam results found.</p>'}
        </div>
        `;
        
        modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
    document.getElementById('detailsModal').classList.remove('flex');
}

function exportResults(studentId) {
    // Implement export functionality
    alert('Export feature will be implemented here');
}

// Close modal on outside click
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
