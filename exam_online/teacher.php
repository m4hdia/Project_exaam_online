<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'teacher') || ($_SESSION['status'] !== 'accepted')) {
    header("Location: game.php");
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
        'student' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type='student' AND status = 'accepted'")->fetchColumn(),
        'teacher' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type='teacher' AND status = 'accepted'")->fetchColumn(),
    ];
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
        color: inherit;
        /* Optional: Keep link text the same color as the surrounding text */
    }


    /* Compact Action Buttons */
    .actions {
        display: flex;
        gap: 8px;
        /* Espace entre les boutons */
        align-items: center;
        /* Alignement vertical */
    }


    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.2);
            opacity: 0.8;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }


    /* Responsive Design */
    @media (max-width: 768px) {

        /* Table adjustments */
        table {
            font-size: 12px;
        }

        th,
        td {
            padding: 12px;
        }



        /* Adjust button sizes */
        .btn-edit,
        .btn-delete {
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

        th,
        td {
            padding: 8px;
        }

        .btn-edit,
        .btn-delete {
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
        transform: translate(2px, 2px);
    }

    .floating-btn {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 4rem;
        height: 4rem;
        background: var(--gradient-1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
    }

    .floating-btn:hover {
        transform: scale(1.1) rotate(1deg);
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 600;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        }

        70% {
            transform: scale(1.1);
            box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
        }
    }

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

    .nav-link:nth-child(1) {
        animation: slideRight 0.5s ease 0.1s forwards;
    }

    .nav-link:nth-child(2) {
        animation: slideRight 0.5s ease 0.2s forwards;
    }

    .nav-link:nth-child(3) {
        animation: slideRight 0.5s ease 0.3s forwards;
    }

    .nav-link:nth-child(4) {
        animation: slideRight 0.5s ease 0.4s forwards;
    }

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

    .stat-card:nth-child(1) {
        animation: slideUp 0.5s ease 0.6s forwards;
    }

    .stat-card:nth-child(2) {
        animation: slideUp 0.5s ease 0.7s forwards;
    }

    .stat-card:nth-child(3) {
        animation: slideUp 0.5s ease 0.8s forwards;
    }

    .stat-card:nth-child(4) {
        animation: slideUp 0.5s ease 0.9s forwards;
    }

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

    .students-results {
        margin-top: 2rem;
        opacity: 0;
        animation: fadeIn 0.5s ease 1.2s forwards;
    }

    .card {
        background: var(--surface-1);
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    /* Large devices (desktops, less than 1200px) */
    @media (max-width: 1200px) {
        .exams-layout {
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
    }

    /* Medium devices (tablets, less than 992px) */
    @media (max-width: 992px) {
        body {
            grid-template-columns: 1fr;
        }

        .sidebar {
            position: fixed;
            left: -280px;
            height: 100vh;
            transition: 0.3s all ease;
            z-index: 1000;
        }

        .sidebar.active {
            left: 0;
        }

        .menu-toggle {
            display: block;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--gradient-1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
            padding-top: 4rem;
        }

        .header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .search-bar {
            width: 100%;
        }

        .user-menu {
            justify-content: flex-end;
        }
    }

    /* Small devices (landscape phones, less than 768px) */
    @media (max-width: 768px) {
        .exams-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
            padding: 1rem;
        }

        .exam-card-inner {
            padding: 1rem;
        }

        .exam-timing {
            flex-direction: column;
            gap: 0.5rem;
        }

        .timer-container {
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .timer-block {
            min-width: 45%;
            margin-bottom: 0.5rem;
        }

        .exam-actions {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1 1 calc(50% - 0.25rem);
            justify-content: center;
        }
    }

    /* Extra small devices (phones, less than 576px) */
    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stat-card {
            padding: 1rem;
        }

        .exam-header {
            flex-direction: column;
            gap: 0.5rem;
        }

        .exam-header h3 {
            font-size: 1.25rem;
        }

        .action-btn {
            flex: 1 1 100%;
        }

        .floating-btn {
            width: 50px;
            height: 50px;
            bottom: 1rem;
            right: 1rem;
        }

        .timer-block {
            min-width: calc(50% - 0.5rem);
        }

        .timer-block .time {
            font-size: 1.25rem;
        }

        .timer-block .label {
            font-size: 0.7rem;
        }

        .Btn {
            width: 40px;
            height: 40px;
        }

        .Btn:hover {
            width: 110px;
        }
    }

    /* Touch device optimizations */
    @media (hover: none) {
        .exam-card:hover {
            transform: none;
            box-shadow: var(--card-shadow);
        }

        .stat-card:hover {
            transform: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .activity-item:hover {
            transform: none;
        }

        .floating-action-btn:hover {
            transform: none;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
    }

    /* Accessibility improvements */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }
    /* Sidebar and Overlay for Mobile */
.menu-toggle {
    display: none; /* Hidden by default */
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: var(--gradient-1);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
}

.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.overlay.active {
    display: block;
}

@media (max-width: 992px) {
    .sidebar {
        position: fixed;
        left: -280px;
        height: 100vh;
        transition: 0.3s all ease;
        z-index: 1000;
    }

    .sidebar.active {
        left: 0;
    }

    .menu-toggle {
        display: block; /* Show toggle button on smaller screens */
    }

    .main-content {
        margin-left: 0;
        padding: 1rem;
        padding-top: 4rem;
    }
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
        <a href="tech.php" class="floating-btn">
            <i class="fas fa-plus"></i>
            <?php if ($pendingCount > 0): ?>
                <div class="notification-badge"><?php echo $pendingCount; ?></div>
            <?php endif; ?>
        </a>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Create menu toggle button
            const menuToggle = document.createElement('button');
            menuToggle.className = 'menu-toggle';
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(menuToggle);

            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);

            // Get sidebar
            const sidebar = document.querySelector('.sidebar');

            // Toggle menu function
            function toggleMenu() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            // Event listeners
            menuToggle.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);

            // Close menu on window resize if open
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                    toggleMenu();
                }
            });

            // Handle search functionality for mobile
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    if (window.innerWidth <= 640) {
                        window.scrollTo(0, this.offsetTop - 10);
                    }
                });
            }

            // Improve touch response
            document.querySelectorAll('.nav-link, .action-btn').forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.7';
                });

                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
    // Create menu toggle button
    const menuToggle = document.createElement('button');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(menuToggle);

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    document.body.appendChild(overlay);

    // Get sidebar
    const sidebar = document.querySelector('.sidebar');

    // Toggle menu function
    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    // Event listeners
    menuToggle.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);

    // Close menu on window resize if open
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && sidebar.classList.contains('active')) {
            toggleMenu();
        }
    });
});
    </script>

</body>

</html>