<?php
// take_exam.php - For students to take the exam
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Fetch exam details
    $stmt = $pdo->prepare("
        SELECT * FROM exams 
        WHERE id = ? AND NOW() BETWEEN start_date AND end_date
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        die("Exam not found or not available.");
    }

    // Check if student already took this exam
    $stmt = $pdo->prepare("
        SELECT * FROM exam_results 
        WHERE student_id = ? AND exam_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $exam_id]);
    if ($stmt->fetch()) {
        die("You have already taken this exam.");
    }

    // Fetch questions
    $stmt = $pdo->prepare("
        SELECT q.*, GROUP_CONCAT(
            CONCAT(a.id, ':', a.answer_text)
            SEPARATOR '||'
        ) as answers
        FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle exam submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();
        
        $total_points = 0;
        $earned_points = 0;

        foreach ($questions as $question) {
            $total_points += $question['points'];
            $answer = $_POST['answer_' . $question['id']] ?? '';

            if ($question['type'] === 'mcq') {
                // For MCQ, check if selected answer is correct
                $stmt = $pdo->prepare("
                    SELECT is_correct FROM answers 
                    WHERE id = ? AND question_id = ?
                ");
                $stmt->execute([$answer, $question['id']]);
                $is_correct = $stmt->fetchColumn();
                
                if ($is_correct) {
                    $earned_points += $question['points'];
                }
            } else {
                // For open questions, store answer for manual grading
                // You could implement an auto-grading system here
                $stmt = $pdo->prepare("
                    INSERT INTO student_answers (
                        student_id, question_id, answer_text
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $question['id'],
                    $answer
                ]);
            }
        }

        // Calculate score as percentage
        $score = ($earned_points / $total_points) * 100;

        // Store exam result
        $stmt = $pdo->prepare("
            INSERT INTO exam_results (
                student_id, exam_id, score, submitted_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $exam_id,
            $score
        ]);

        $pdo->commit();
        header("Location: student.php#results");
        exit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($exam['title']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --surface: #ffffff;
            --background: #f3f4f6;
            --text: #1f2937;
            --error: #ef4444;
            --success: #10b981;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.5;
            margin: 0;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .exam-header {
            background: var(--surface);
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .question-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .timer {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--surface);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            margin-top: 2rem;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }

        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .radio-option:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="exam-header">
            <h1><?= htmlspecialchars($exam['title']) ?></h1>
            <p><?= htmlspecialchars($exam['description']) ?></p>
            <p><strong>Duration:</strong> <?= htmlspecialchars($exam['duration']) ?> minutes</p>
        </div>

        <div class="timer" id="examTimer"></div>

        <form id="examForm" method="POST">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <h3>Question <?= $index + 1 ?></h3>
                    <p><?= htmlspecialchars($question['question_text']) ?></p>
                    
                    <?php if ($question['file_path']): ?>
                        <div class="question-file">
                            <?php
                            $ext = pathinfo($question['file_path'], PATHINFO_EXTENSION);
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                            ?>
                                <img src="<?= htmlspecialchars($question['file_path']) ?>" 
                                     alt="Question image" style="max-width: 100%">
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($question['file_path']) ?>" 
                                   target="_blank">View attached file</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($question['type'] === 'mcq'): ?>
                        <div class="radio-group">
                            <?php 
                            $answers = explode('||', $question['answers']);
                            foreach ($answers as $answer):
                                list($id, $text) = explode(':', $answer);
                            ?>
                                <label class="radio-option">
                                    <input type="radio" 
                                           name="answer_<?= $question['id'] ?>" 
                                           value="<?= $id ?>" 
                                           required>
                                    <?= htmlspecialchars($text) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <textarea name="answer_<?= $question['id'] ?>" 
                                  rows="4" 
                                  required 
                                  placeholder="Enter your answer here..."></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Submit Exam
            </button>
        </form>
    </div>

    <script>
        // Set up exam timer
        const duration = <?= $exam['duration'] ?>;
        const endTime = new Date(new Date().getTime() + duration * 60000);

        function updateTimer() {
            const now = new Date();
            const timeLeft = endTime - now;

            if (timeLeft <= 0) {
                document.getElementById('examForm').submit();
                return;
            }

            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);

            document.getElementById('examTimer').innerHTML = 
                `Time remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Warn before leaving page
        window.onbeforeunload = function() {
            return "Are you sure you want to leave? Your answers will not be saved.";
        };

        // Remove warning when submitting form
        document.getElementById('examForm').onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html>