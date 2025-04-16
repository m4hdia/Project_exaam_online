<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is a student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Check the student's approval status
$stmt = $pdo->prepare("SELECT status, first_name FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// If the student is approved, redirect to student dashboard
if ($user['status'] === 'accepted') {
    header("Location: student.php");
    exit();
}

// Store first name for use in the HTML
$firstName = isset($user['first_name']) ? $user['first_name'] : 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval - ExamOnline</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #0ea5e9;
            --accent: #06b6d4;
            --success: #10b981;
            --background: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --card-bg: rgba(30, 41, 59, 0.8);
            --error: #ef4444;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .animated-background {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, var(--background) 0%, var(--surface) 100%);
            overflow: hidden;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 30%),
                        radial-gradient(circle at 20% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 30%);
            animation: backgroundAnimation 20s ease infinite;
            opacity: 0.5;
        }

        @keyframes backgroundAnimation {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .navbar {
            padding: 1.25rem 5%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
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
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            color: var(--primary);
            transition: transform 0.3s ease;
        }

        .logo:hover i {
            transform: rotate(20deg);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: var(--text);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 5%;
            margin-bottom: 2rem;
        }

        .waiting-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 800px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .waiting-card h2 {
            margin-bottom: 1.5rem;
            color: var(--text);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .waiting-card h2 i {
            color: var(--primary);
        }

        .waiting-card p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2rem 0;
        }

        .status-dot {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--primary);
            margin-right: 10px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        .status-text {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text);
        }

        .game-container {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .game-container h2 {
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--text);
            font-size: 1.5rem;
        }

        .game-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .card {
            aspect-ratio: 3/4;
            background-color: var(--primary-dark);
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            transform-style: preserve-3d;

        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
        }

        .card.flipped {
            transform: rotateY(180deg);
            background-color: var(--surface);
            color: var(--text);
        }

        .card.matched {
            background-color: var(--success);
            color: var(--text);
            pointer-events: none;
        }

        .game-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
        }

        .score, .timer {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .score span, .timer span {
            color: var(--primary);
        }

        .game-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .btn-reset {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-reset:hover {
            background: var(--primary-dark);
        }

        .refresh-section {
            margin-top: 2rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .refresh-button {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--surface);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .refresh-button:hover {
            background: var(--primary-dark);
        }

        footer {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem 5%;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .game-board {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .waiting-card, .game-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .game-board {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .navbar {
                padding: 1rem 5%;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .waiting-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>
    
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                ExamOnline
            </a>
            <a href="logout.php" class="btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="waiting-card">
            <h2><i class="fas fa-clock"></i> Account Pending Approval</h2>
            <p>Hi <?php echo htmlspecialchars($firstName); ?>, your account is currently pending approval from an administrator.</p>
            <p>Once approved, you'll be able to access all features of ExamOnline including upcoming exams, study materials, and more.</p>
            
            <div class="status-indicator">
                <span class="status-dot"></span>
                <span class="status-text">Waiting for approval...</span>
            </div>
            
            <p>This page will automatically redirect you once your account is approved. You don't need to refresh the page.</p>
            
            <div class="refresh-section">
                <p>If you've been waiting for a while and believe your account should be approved by now, you can manually check your status:</p>
                <button id="refresh-button" class="refresh-button">
                    <i class="fas fa-sync-alt"></i> Check Status
                </button>
            </div>
        </div>
        
        <div class="game-container">
            <h2>While you wait, enjoy a memory game!</h2>
            <div class="game-stats">
                <div class="score">Score: <span id="score">0</span></div>
                <div class="timer">Time: <span id="timer">0</span>s</div>
            </div>
            <div class="game-board" id="game-board"></div>
            <div class="game-controls">
                <button id="start-game" class="btn">
                    <i class="fas fa-play"></i> Start Game
                </button>
                <button id="reset-game" class="btn btn-reset">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-text">
                &copy; <?php echo date("Y"); ?> ExamOnline. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script>
        // Auto-refresh to check status every 30 seconds
        setInterval(function() {
            location.reload();
        }, 40000);
        
        // Manual refresh button
        document.getElementById('refresh-button').addEventListener('click', function() {
            location.reload();
        });
        
        // Memory Game Logic
        document.addEventListener('DOMContentLoaded', function() {
            const gameBoard = document.getElementById('game-board');
            const startButton = document.getElementById('start-game');
            const resetButton = document.getElementById('reset-game');
            const scoreDisplay = document.getElementById('score');
            const timerDisplay = document.getElementById('timer');
            
            const emojis = [ '✏', '🔍', '📝', '💻', '🧮', '📊'];
            const gameEmojis = [...emojis, ...emojis];
            
            let flippedCards = [];
            let matchedPairs = 0;
            let score = 0;
            let timer = 0;
            let gameInterval;
            let isPlaying = false;
            
            // Initialize the game board
            function initializeGame() {
                gameBoard.innerHTML = '';
                shuffleArray(gameEmojis).forEach(emoji => {
                    const card = document.createElement('div');
                    card.className = 'card';
                    card.dataset.value = emoji;
                    card.addEventListener('click', flipCard);
                    gameBoard.appendChild(card);
                });
                
                matchedPairs = 0;
                score = 0;
                timer = 0;
                scoreDisplay.textContent = score;
                timerDisplay.textContent = timer;
            }
            
            // Shuffle array using Fisher-Yates algorithm
            function shuffleArray(array) {
                const newArray = [...array];
                for (let i = newArray.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
                }
                return newArray;
            }
            
            // Start the game
            function startGame() {
                if (!isPlaying) {
                    initializeGame();
                    isPlaying = true;
                    startButton.textContent = 'Game in Progress';
                    startButton.disabled = true;
                    
                    // Start timer
                    clearInterval(gameInterval);
                    gameInterval = setInterval(function() {
                        timer++;
                        timerDisplay.textContent = timer;
                    }, 1000);
                }
            }
            
            // Reset the game
            function resetGame() {
                clearInterval(gameInterval);
                isPlaying = false;
                flippedCards = [];
                startButton.innerHTML = '<i class="fas fa-play"></i> Start Game';
                startButton.disabled = false;
                initializeGame();
            }
            
            // Flip card
            function flipCard() {
                if (!isPlaying || flippedCards.length >= 2 || this.classList.contains('flipped') || this.classList.contains('matched')) {
                    return;
                }
                
                this.classList.add('flipped');
                this.textContent = this.dataset.value;
                flippedCards.push(this);
                
                if (flippedCards.length === 2) {
                    checkForMatch();
                }
            }
            
            // Check if the flipped cards match
            function checkForMatch() {
                const [card1, card2] = flippedCards;
                
                if (card1.dataset.value === card2.dataset.value) {
                    // Cards match
                    card1.classList.add('matched');
                    card2.classList.add('matched');
                    score += 10;
                    scoreDisplay.textContent = score;
                    matchedPairs++;
                    
                    if (matchedPairs === emojis.length) {
                        // Game completed
                        clearInterval(gameInterval);
                        setTimeout(() => {
                            alert('Congratulations! You found all pairs!');
                            resetGame();
                        }, 500);
                    }
                } else {
                    // Cards don't match
                    setTimeout(() => {
                        card1.classList.remove('flipped');
                        card2.classList.remove('flipped');
                        card1.textContent = '';
                        card2.textContent = '';
                        score = Math.max(0, score - 1);
                        scoreDisplay.textContent = score;
                    }, 1000);
                }
                
                flippedCards = [];
            }
            
            // Event listeners
            startButton.addEventListener('click', startGame);
            resetButton.addEventListener('click', resetGame);
            
            // Initialize on load
            initializeGame();
        });
    </script>
</body>
</html>