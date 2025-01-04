<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}
try {
  
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
<body>
   <aside class="sidebar">
        <div class="logo">SchoolTeacher</div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active">
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
                <i class="fas fa-bell" id="notificationIcon" style="margin-right: 1rem; cursor: pointer;"></i>
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
    <section class="students-results">
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Students and Results</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 border-b">ID</th>
                        <th class="px-6 py-3 border-b">Name</th>
                        <th class="px-6 py-3 border-b">Email</th>
                        <th class="px-6 py-3 border-b">Recent Exams</th>
                        <th class="px-6 py-3 border-b">Average Score</th>
                        <th class="px-6 py-3 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                   
                </tbody>
            </table>
        </div>
    </div>



    <div class="floating-action-btn" id="addButton">
        <i class="fas fa-plus"></i>
        <div class="notification-badge">10</div>
</section>
</main>

<script>
        

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