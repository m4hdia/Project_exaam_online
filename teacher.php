<?php
// teacher.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

try {
    // Get pending requests count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'pending'");
    $stmt->execute();
    $pendingCount = $stmt->fetchColumn();
    
    // Get exams with search functionality
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $examQuery = "SELECT * FROM exams";
    if (!empty($search)) {
        $examQuery .= " WHERE title LIKE :search OR description LIKE :search";
    }
    $examQuery .= " ORDER BY end_date DESC";
    
    $stmt = $pdo->prepare($examQuery);
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
    // Get stats
    $stats = [
        'student' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type='student' AND status = 'accepted'")->fetchColumn(),
        'teacher' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type='teacher' AND status = 'accepted'")->fetchColumn(),
    ];
    
    $groupedResults = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "A system error occurred. Please try again later.";
}

function calculateProgress($startDate, $endDate) {
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    $now = time();
    
    if ($now < $start) return 0;
    if ($now > $end) return 100;
    
    $total = $end - $start;
    $elapsed = $now - $start;
    
    return round(($elapsed / $total) * 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - ExamOnline</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
  /* Modern Theme - Light */
  --primary-color: #6366f1;
  --primary-light: #818cf8;
  --primary-dark: #4f46e5;
  --secondary-color: #10b981;
  --danger-color: #f43f5e;
  --warning-color: #f59e0b;
  --info-color: #0ea5e9;
  
  --text-primary: #111827;
  --text-secondary: #4b5563;
  --text-muted: #6b7280;
  
  --bg-primary: #ffffff;
  --bg-secondary: #f9fafb;
  --bg-tertiary: #f3f4f6;
  
  --border-color: #e5e7eb;
  --border-radius: 0.75rem;
  --border-radius-sm: 0.5rem;
  --border-radius-lg: 1rem;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
  --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
  
  --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  --container-max-width: 1400px;
}

[data-theme="dark"] {
  --primary-color: #818cf8;
  --primary-light: #a5b4fc;
  --primary-dark: #6366f1;
  --secondary-color: #34d399;
  --danger-color: #fb7185;
  --warning-color: #fbbf24;
  --info-color: #38bdf8;
  
  --text-primary: #f9fafb;
  --text-secondary: #e5e7eb;
  --text-muted: #9ca3af;
  
  --bg-primary: #1f2937;
  --bg-secondary: #111827;
  --bg-tertiary: #374151;
  
  --border-color: #374151;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  line-height: 1.6;
  overflow-x: hidden;
}

.app-container {
  display: flex;
  min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
  width: 280px;
  background-color: var(--bg-primary);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: fixed;
  height: 100vh;
  z-index: 1000;
  box-shadow: var(--shadow);
}

.sidebar-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
}

.logo {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
  display: flex;
  align-items: center;
}

.logo i {
  margin-right: 12px;
  font-size: 1.5rem;
}

.sidebar-close-btn {
  display: none;
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1.25rem;
  cursor: pointer;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.sidebar-close-btn:hover {
  background-color: var(--bg-tertiary);
  color: var(--primary-color);
}

.sidebar-nav {
  flex: 1;
  padding: 1.25rem 0;
  overflow-y: auto;
}

.nav-section {
  margin-bottom: 1.75rem;
}

.nav-section-title {
  padding: 0.5rem 1.5rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  font-weight: 600;
}

.nav-item {
  margin: 0.25rem 0;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.875rem 1.5rem;
  color: var(--text-secondary);
  text-decoration: none;
  transition: var(--transition);
  font-weight: 500;
  border-radius: 0.5rem;
  margin: 0 0.75rem;
}

.nav-link i {
  margin-right: 0.875rem;
  width: 20px;
  text-align: center;
  font-size: 1.125rem;
}

.nav-link:hover {
  color: var(--primary-color);
  background-color: rgba(99, 102, 241, 0.08);
}

.nav-item.active .nav-link {
  color: var(--primary-color);
  background-color: rgba(99, 102, 241, 0.1);
  font-weight: 600;
}

.sidebar-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
}

.user-profile {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border-radius: var(--border-radius);
  transition: var(--transition);
}

.user-profile:hover {
  background-color: var(--bg-tertiary);
}

.avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background-color: var(--primary-light);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.875rem;
  color: white;
  font-size: 1.25rem;
  font-weight: 600;
  box-shadow: var(--shadow-sm);
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.user-role {
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Main Content Styles */
.main-content {
  flex: 1;
  margin-left: 280px;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  max-width: 100%;
}

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: var(--bg-primary);
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--shadow-sm);
}

.header-left {
  display: flex;
  align-items: center;
}

.sidebar-toggle-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1.25rem;
  margin-right: 1rem;
  cursor: pointer;
  display: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.sidebar-toggle-btn:hover {
  background-color: var(--bg-tertiary);
  color: var(--primary-color);
}

.page-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.header-right {
  display: flex;
  align-items: center;
}

.search-box {
  position: relative;
  margin-right: 1rem;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  pointer-events: none;
}

.search-box input {
  padding: 0.625rem 1rem 0.625rem 2.75rem;
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--border-color);
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  width: 250px;
  transition: var(--transition);
  font-size: 0.875rem;
}

.search-box input::placeholder {
  color: var(--text-muted);
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-light);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.header-actions {
  display: flex;
  align-items: center;
}

.theme-btn, .notification-btn, .user-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1.25rem;
  margin-left: 1rem;
  cursor: pointer;
  position: relative;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: var(--transition);
}

.theme-btn:hover, .notification-btn:hover, .user-btn:hover {
  background-color: var(--bg-tertiary);
  color: var(--primary-color);
}

.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background-color: var(--danger-color);
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-btn .avatar {
  margin-right: 0;
  width: 36px;
  height: 36px;
  font-size: 1rem;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background-color: var(--bg-primary);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  padding: 0.5rem;
  min-width: 220px;
  z-index: 100;
  display: none;
  border: 1px solid var(--border-color);
  transform-origin: top right;
  animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.user-dropdown.show {
  display: block;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  color: var(--text-primary);
  text-decoration: none;
  display: flex;
  align-items: center;
  transition: var(--transition);
  border-radius: var(--border-radius-sm);
  margin-bottom: 0.25rem;
}

.dropdown-item:last-child {
  margin-bottom: 0;
}

.dropdown-item i {
  margin-right: 0.75rem;
  width: 20px;
  text-align: center;
}

.dropdown-item:hover {
  background-color: var(--bg-tertiary);
  color: var(--primary-color);
}

.content-wrapper {
  padding: 2rem;
  max-width: var(--container-max-width);
  margin: 0 auto;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.stat-card {
  background-color: var(--bg-primary);
  border-radius: var(--border-radius);
  padding: 1.75rem;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  transition: var(--transition);
  border: 1px solid transparent;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-light);
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background-color: rgba(99, 102, 241, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1.25rem;
  color: var(--primary-color);
  font-size: 1.5rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-muted);
  font-weight: 500;
}

/* Exams Section */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.375rem;
  font-weight: 700;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  white-space: nowrap;
}

.btn i {
  margin-right: 0.5rem;
  font-size: 1rem;
}

.primary-btn {
  background-color: var(--primary-color);
  color: white;
  box-shadow: 0 2px 5px rgba(99, 102, 241, 0.2);
}

.primary-btn:hover {
  background-color: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(99, 102, 241, 0.25);
}

.primary-btn:active {
  transform: translateY(0);
}

.secondary-btn {
  background-color: var(--bg-tertiary);
  color: var(--text-primary);
}

.secondary-btn:hover {
  background-color: var(--border-color);
}

.exams-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.exam-card {
  background-color: var(--bg-primary);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
  border: 1px solid transparent;
}

.exam-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-light);
}

.exam-card-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
}

.exam-status {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  border-radius: 2rem;
  text-transform: capitalize;
}

.exam-status.published {
  background-color: rgba(16, 185, 129, 0.1);
  color: var(--secondary-color);
}

.exam-status.draft {
  background-color: rgba(156, 163, 175, 0.1);
  color: var(--text-muted);
}

.exam-status.archived {
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
}

.exam-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  transition: var(--transition);
}

.action-btn:hover {
  background-color: var(--bg-tertiary);
}

.edit-btn:hover {
  color: var(--info-color);
}

.delete-btn:hover {
  color: var(--danger-color);
}

.exam-card-body {
  padding: 1.5rem;
  flex: 1;
}

.exam-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.exam-description {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
}

.exam-meta {
  display: flex;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.meta-item i {
  margin-right: 0.5rem;
}

.exam-progress {
  margin-bottom: 1.5rem;
}

.progress-bar {
  height: 8px;
  background-color: var(--bg-tertiary);
  border-radius: 4px;
  margin-bottom: 0.5rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: var(--primary-color);
  border-radius: 4px;
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.progress-time {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.exam-timer {
  display: flex;
  justify-content: space-between;
  background-color: var(--bg-secondary);
  padding: 1rem;
  border-radius: var(--border-radius);
  margin-bottom: 1.5rem;
}

.timer-item {
  text-align: center;
  padding: 0.5rem;
}

.timer-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
  display: block;
}

.timer-label {
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-top: 0.25rem;
  font-weight: 500;
}

.exam-card-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  gap: 0.75rem;
}

.publish-btn {
  background-color: var(--secondary-color);
  color: white;
  flex: 1;
}

.publish-btn:hover {
  background-color: #0d9e70;
}

.view-btn {
  background-color: var(--info-color);
  color: white;
  flex: 1;
}

.view-btn:hover {
  background-color: #0284c7;
}

/* Empty State */
.empty-state {
  background-color: var(--bg-primary);
  border-radius: var(--border-radius);
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: var(--shadow);
}

.empty-state-icon {
  font-size: 3.5rem;
  color: var(--primary-color);
  margin-bottom: 1.5rem;
  opacity: 0.8;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.empty-state p {
  color: var(--text-muted);
  margin-bottom: 2rem;
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-overlay.active {
  opacity: 1;
  visibility: visible;
}

.modal {
  background-color: var(--bg-primary);
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-lg);
  width: 90%;
  max-width: 550px;
  max-height: 90vh;
  overflow-y: auto;
  transform: translateY(20px);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: 1px solid var(--border-color);
}

.modal-overlay.active .modal {
  transform: translateY(0);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
}

.modal-title i {
  margin-right: 0.75rem;
  color: var(--primary-color);
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: var(--text-muted);
  cursor: pointer;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: var(--transition);
}

.modal-close:hover {
  background-color: var(--bg-tertiary);
  color: var(--danger-color);
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Status Selector */
.status-selector {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.status-option {
  flex: 1;
  text-align: center;
}

.status-option input[type="radio"] {
  display: none;
}

.status-option label {
  display: block;
  padding: 1rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: var(--transition);
  border: 1px solid var(--border-color);
  font-weight: 500;
}

.status-option input[type="radio"]:checked + label {
  border-color: var(--primary-color);
  background-color: rgba(99, 102, 241, 0.1);
  color: var(--primary-color);
  font-weight: 600;
  box-shadow: 0 2px 5px rgba(99, 102, 241, 0.15);
}

.status-option label i {
  display: block;
  margin: 0 auto 0.5rem;
  font-size: 1.5rem;
}

.status-option:nth-child(1) label i {
  color: var(--text-muted);
}

.status-option:nth-child(2) label i {
  color: var(--secondary-color);
}

.status-option:nth-child(3) label i {
  color: var(--danger-color);
}

.status-option input[type="radio"]:checked + label i {
  color: var(--primary-color);
}

/* Logout Section */
.logout-section {
  margin-top: auto;
  padding: 0.75rem 1.5rem;
}

.logout-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: var(--text-primary);
  text-decoration: none;
  transition: var(--transition);
  border-radius: var(--border-radius-sm);
  font-weight: 500;
}

.logout-link i {
  margin-right: 0.75rem;
  width: 20px;
  text-align: center;
}

.logout-link:hover {
  color: var(--danger-color);
  background-color: rgba(244, 63, 94, 0.08);
}

/* Animation Classes */
.fade-in {
  animation: fadeIn 0.3s ease forwards;
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

/* Timer ended state */
.timer-ended {
  font-weight: 600;
  color: var(--danger-color);
  text-align: center;
  width: 100%;
  font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
  }
  
  .sidebar.active {
    transform: translateX(0);
    box-shadow: var(--shadow-lg);
  }
  
  .sidebar-close-btn {
    display: flex;
  }
  
  .main-content {
    margin-left: 0;
  }
  
  .sidebar-toggle-btn {
    display: flex;
  }
  
  .exams-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

@media (max-width: 768px) {
  .header-right {
    flex-direction: row-reverse;
  }
  
  .search-box {
    margin-right: 0;
    margin-left: 1rem;
    width: auto;
  }
  
  .search-box input {
    width: 200px;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .content-wrapper {
    padding: 1.5rem;
  }
  
  .exams-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 576px) {
  .main-header {
    padding: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .header-left, .header-right {
    width: 100%;
    justify-content: space-between;
  }
  
  .search-box {
    margin-left: 0;
    flex-grow: 1;
  }
  
  .search-box input {
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .exam-card-footer .btn {
    font-size: 0.75rem;
    padding: 0.5rem;
  }
  
  .exam-timer {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .timer-item {
    flex: 1 0 40%;
  }}
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2 class="logo"><i class="fas fa-graduation-cap"></i>ExamOnline</h2>
                <button class="sidebar-close-btn" id="sidebarCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">Main</h3>
                    <ul>
                        <li class="nav-item active">
                            <a href="teacher.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="Addstudents.php" class="nav-link">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Users</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Exams</h3>
                    <ul>
                        <li class="nav-item">
                            <a href="createxam.php" class="nav-link">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create Exam</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="correction.php" class="nav-link">
                                <i class="fas fa-check-double"></i>
                                <span>Correction</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Students</h3>
                    <ul>
                        <li class="nav-item">
                            <a href="allstudent.php" class="nav-link">
                                <i class="fas fa-users"></i>
                                <span>View Students</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="logout-section">
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Teacher'); ?></span>
                        <span class="user-role">Teacher</span>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle-btn" id="sidebarOpenBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <form method="GET" action="teacher.php" class="search-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search exams..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" style="display: none;"></button>
                        </div>
                    </form>
                    
                    <div class="header-actions">
                        <div class="theme-switcher">
                            <button class="theme-btn" id="themeBtn">
                                <i class="fas fa-moon"></i>
                            </button>
                        </div>
                        
                        <div class="notifications">
                            <button class="notification-btn" onclick="window.location.href='tech.php'">
                                <i class="fas fa-bell"></i>
                                <?php if ($pendingCount > 0): ?>
                                <span class="notification-badge"><?= $pendingCount ?></span>
                                <?php endif; ?>
                            </button>
                        </div>
                        
                        <div class="user-menu">
                            <button class="user-btn" id="userBtn">
                                <div class="avatar">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </button>
                            <div class="user-dropdown" id="userDropdown">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="Settings.php" class="dropdown-item">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                                <a href="logout.php" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="content-wrapper">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?= number_format($stats['student']) ?></h3>
                            <p class="stat-label">Active Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?= number_format($stats['teacher']) ?></h3>
                            <p class="stat-label">Teachers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?= count($exams) ?></h3>
                            <p class="stat-label">Active Exams</p>
                        </div>
                    </div>
                </div>
                
                <!-- Exams Section -->
                <section class="exams-section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Recent Exams</h2>
                        <a href="createxam.php" class="btn primary-btn">
                            <i class="fas fa-plus"></i> Create New
                        </a>
                    </div>
                    
                    <?php if (!empty($exams)): ?>
                    <div class="exams-grid">
                        <?php foreach ($exams as $exam): ?>
                        <div class="exam-card">
                            <div class="exam-card-header">
                                <span class="exam-status <?= strtolower($exam['status']) ?>">
                                    <?= htmlspecialchars($exam['status']) ?>
                                </span>
                                <div class="exam-actions">
                                    <button class="action-btn edit-btn" onclick="openEditModal(<?= $exam['id'] ?>, '<?= htmlspecialchars($exam['status']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?= $exam['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="exam-card-body">
                                <h3 class="exam-title"><?= htmlspecialchars($exam['title']) ?></h3>
                                <p class="exam-description"><?= htmlspecialchars($exam['description']) ?></p>
                                
                                <div class="exam-meta">
                                    <div class="meta-item">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?= date('M d, Y', strtotime($exam['start_date'])) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-clock"></i>
                                        <span><?= date('H:i', strtotime($exam['start_date'])) ?> - <?= date('H:i', strtotime($exam['end_date'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="exam-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= calculateProgress($exam['start_date'], $exam['end_date']) ?>%"></div>
                                    </div>
                                    <div class="progress-time">
                                        <span><?= date('H:i', strtotime($exam['start_date'])) ?></span>
                                        <span><?= date('H:i', strtotime($exam['end_date'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="exam-timer" data-end-date="<?= htmlspecialchars($exam['end_date']) ?>">
                                    <div class="timer-item">
                                        <span class="timer-value days">00</span>
                                        <span class="timer-label">Days</span>
                                    </div>
                                    <div class="timer-item">
                                        <span class="timer-value hours">00</span>
                                        <span class="timer-label">Hours</span>
                                    </div>
                                    <div class="timer-item">
                                        <span class="timer-value minutes">00</span>
                                        <span class="timer-label">Mins</span>
                                    </div>
                                    <div class="timer-item">
                                        <span class="timer-value seconds">00</span>
                                        <span class="timer-label">Secs</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exam-card-footer">
                                <?php if ($exam['status'] !== 'published'): ?>
                                <button class="btn publish-btn" onclick="changeExamStatus(<?= $exam['id'] ?>, 'published')">
                                    <i class="fas fa-upload"></i> Publish
                                </button>
                                <?php else: ?>
                                <button class="btn view-btn" onclick="window.location.href='exam_results.php?id=<?= $exam['id'] ?>'">
                                    <i class="fas fa-chart-bar"></i> View Results
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>No Exams Available</h3>
                        <p>Create your first exam to get started</p>
                        <a href="createxam.php" class="btn primary-btn">
                            <i class="fas fa-plus"></i> Create Exam
                        </a>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    
    <!-- Edit Exam Status Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"></i> Change Exam Status</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="status-selector">
                    <div class="status-option">
                        <input type="radio" id="status-draft" name="status" value="draft">
                        <label for="status-draft">
                            <i class="fas fa-file-alt"></i> Draft
                        </label>
                    </div>
                    <div class="status-option">
                        <input type="radio" id="status-published" name="status" value="published">
                        <label for="status-published">
                            <i class="fas fa-upload"></i> Published
                        </label>
                    </div>
                    <div class="status-option">
                        <input type="radio" id="status-archived" name="status" value="archived">
                        <label for="status-archived">
                            <i class="fas fa-archive"></i> Archived
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn secondary-btn" onclick="closeModal('editModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn primary-btn" id="saveStatusBtn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Current exam ID for modal operations
        let currentExamId = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarOpenBtn = document.getElementById('sidebarOpenBtn');
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            
            sidebarOpenBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            sidebarCloseBtn.addEventListener('click', () => {
                sidebar.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (!sidebar.contains(e.target) && e.target !== sidebarOpenBtn) {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Theme switcher
            const themeBtn = document.getElementById('themeBtn');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            const currentTheme = localStorage.getItem('theme');
            
            // Set initial theme
            if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
            }
            
            // Toggle theme
            themeBtn.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                if (currentTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                    themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
                }
            });
            
            // Countdown timers for exams
            function updateTimers() {
                const timers = document.querySelectorAll('.exam-timer');
                
                timers.forEach(timer => {
                    const endDate = new Date(timer.dataset.endDate);
                    const now = new Date();
                    const diff = endDate - now;
                    
                    if (diff <= 0) {
                        timer.innerHTML = '<div class="timer-ended">Exam Ended</div>';
                        return;
                    }
                    
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    timer.querySelector('.days').textContent = String(days).padStart(2, '0');
                    timer.querySelector('.hours').textContent = String(hours).padStart(2, '0');
                    timer.querySelector('.minutes').textContent = String(minutes).padStart(2, '0');
                    timer.querySelector('.seconds').textContent = String(seconds).padStart(2, '0');
                });
            }
            
            // Update timers every second
            setInterval(updateTimers, 1000);
            updateTimers();
            
            // User dropdown menu
            const userBtn = document.getElementById('userBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                userDropdown.classList.remove('show');
            });
            
            // Search functionality
            const searchForm = document.querySelector('.search-form');
            const searchInput = searchForm.querySelector('input[name="search"]');
            
            // Debounce search input to prevent too many requests
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchForm.submit();
                }, 500);
            });
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function openEditModal(examId, currentStatus) {
            currentExamId = examId;
            // Set the current status
            document.querySelector(`input[name="status"][value="${currentStatus}"]`).checked = true;
            openModal('editModal');
        }
        
        // Save status changes
        document.getElementById('saveStatusBtn').addEventListener('click', function() {
            const selectedStatus = document.querySelector('input[name="status"]:checked').value;
            changeExamStatus(currentExamId, selectedStatus);
            closeModal('editModal');
        });
        
        // Modern alert confirmations
        function confirmDelete(examId) {
            Swal.fire({
                title: 'Delete Exam',
                text: 'Are you sure you want to delete this exam?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete_exam.php?id=${examId}`;
                }
            });
        }
        
        // Change exam status
        function changeExamStatus(examId, newStatus) {
            Swal.fire({
                title: 'Change Status',
                text: `Are you sure you want to change this exam's status to ${newStatus}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`change_exam_status.php?id=${examId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Exam status has been updated successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to update exam status.',
                                icon: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An unexpected error occurred. Please try again.',
                            icon: 'error'
                        });
                    });
                }
            });
        }
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });
    </script>
</body>
</html>