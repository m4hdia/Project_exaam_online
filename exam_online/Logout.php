<?php
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other application-specific cookies if they exist
setcookie('remember_me', '', time() - 3600, '/');
setcookie('user_preferences', '', time() - 3600, '/');

// Log the logout activity
try {
    require_once 'config.php';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Unknown';
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (action_type, description, user_id) 
        VALUES ('logout', 'User logged out', ?)
    ");
    $stmt->execute([$user_id]);
} catch (PDOException $e) {
    error_log("Logout activity logging failed: " . $e->getMessage());
}

// Redirect to login page with a logout message
header('Location: Login.php?msg=logout_success');
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .logout-message {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-message">
        <div class="spinner"></div>
        <p>Logging you out...</p>
    </div>
</body>
</html>