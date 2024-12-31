<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}
try {
  
    $stats = [
        'students' => $pdo->query("SELECT COUNT(*) FROM students ")->fetchColumn(),
        'teachers' => $pdo->query("SELECT COUNT(*) FROM teachers ")->fetchColumn(),
       
    ];

   
    $stmt = $pdo->prepare("
        SELECT al.action_type, 
               al.description, 
               al.created_at,
               CASE 
                   WHEN al.action_type LIKE '%student%' THEN 'fa-user-graduate'
                   WHEN al.action_type LIKE '%teacher%' THEN 'fa-chalkboard-teacher'
                   WHEN al.action_type LIKE '%class%' THEN 'fa-school'
                   ELSE 'fa-history'
               END as icon_class
        FROM activity_logs al
        ORDER BY al.created_at DESC 
        LIMIT 5
    ");
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
    <style>
        :root {
            --gradient-1: linear-gradient(135deg, #6366f1, #8b5cf6);
            --gradient-2: linear-gradient(135deg, #3b82f6, #2dd4bf);
            --gradient-3: linear-gradient(135deg, #f43f5e, #f97316);
            --surface-1: #ffffff;
            --surface-2: #f8fafc;
            --text-1: #0f172a;
            --text-2: #475569;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--surface-2);
            color: var(--text-1);
            min-height: 100vh;
            display: grid;
            grid-template-columns: auto 1fr;
        }

        .sidebar {
            width: 280px;
            background: var(--surface-1);
            padding: 2rem;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 10;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: slideRight 0.5s ease forwards;
            opacity: 0;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            text-decoration: none;
            color: var(--text-2);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateX(-20px);
        }

        .nav-link:nth-child(1) { animation: slideRight 0.5s ease 0.1s forwards; }
        .nav-link:nth-child(2) { animation: slideRight 0.5s ease 0.2s forwards; }
        .nav-link:nth-child(3) { animation: slideRight 0.5s ease 0.3s forwards; }
        .nav-link:nth-child(4) { animation: slideRight 0.5s ease 0.4s forwards; }

        .nav-link:hover {
            background: var(--surface-2);
            color: var(--text-1);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--gradient-1);
            color: white;
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeIn 0.5s ease 0.5s forwards;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface-1);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .stat-card:nth-child(1) { animation: slideUp 0.5s ease 0.6s forwards; }
        .stat-card:nth-child(2) { animation: slideUp 0.5s ease 0.7s forwards; }
        .stat-card:nth-child(3) { animation: slideUp 0.5s ease 0.8s forwards; }
        .stat-card:nth-child(4) { animation: slideUp 0.5s ease 0.9s forwards; }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .activities {
            background: var(--surface-1);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            opacity: 0;
            animation: fadeIn 0.5s ease 1s forwards;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--surface-2);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            transform: translateX(10px);
            background: var(--surface-2);
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .search-bar {
            position: relative;
            width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: none;
            border-radius: 0.75rem;
            background: var(--surface-2);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #6366f1;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-2);
        }

        @keyframes slideRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .floating-action-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
            opacity: 0;
            animation: fadeIn 0.5s ease 1.1s forwards;
        }

        .floating-action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.6);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--gradient-3);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">SchoolAdmin</div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="students.php" class="nav-link">
                <i class="fas fa-users"></i>
                Students
            </a>
            <a href="teachers.php" class="nav-link">
                <i class="fas fa-chalkboard-teacher"></i>
                Teachers
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i>
                Settings
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

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" style="background: #fee2e2; color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-graduate" style="color: #6366f1;"></i>
                <div class="stat-value"><?php echo number_format($stats['students']); ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher" style="color: #3b82f6;"></i>
                <div class="stat-value"><?php echo number_format($stats['teachers']); ?></div>
                <div class="stat-label">Teachers</div>
            </div>
         
           
        </div>

        <section class="activities">
            <h2>Recent Activities</h2>
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas <?php echo htmlspecialchars($activity['icon_class']); ?>"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">
                                <?php echo htmlspecialchars($activity['description']); ?>
                            </div>
                            <div class="activity-time" style="color: var(--text-2); font-size: 0.875rem;">
                                <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent activities found.</p>
            <?php endif; ?>
        </section>

        <div class="floating-action-btn" id="addButton">
            <i class="fas fa-plus"></i>
            <div class="notification-badge">3</div>
        </div>
    </main>

    <script>
        // Profile dropdown toggle
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
    </script>
</body>
</html>