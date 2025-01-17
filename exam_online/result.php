<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    exit('Unauthorized');
}
require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Exam Grading System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .neo-gradient {
            background: linear-gradient(120deg, #1a365d 0%, #2d3748 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .answer-card {
            transition: all 0.3s ease;
        }
        .answer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .correct-answer {
            border-left: 4px solid #48bb78;
            background: rgba(72, 187, 120, 0.1);
        }
        .incorrect-answer {
            border-left: 4px solid #f56565;
            background: rgba(245, 101, 101, 0.1);
        }
        .grade-input {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .grade-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        .progress-ring {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="neo-gradient min-h-screen text-gray-100">
    <div class="container mx-auto p-6">
        <!-- Header with Progress -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold">Exam Grading System</h1>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-sm opacity-80">Total Progress</div>
                    <div class="text-2xl font-bold" id="progressPercent">0%</div>
                </div>
                <svg class="w-16 h-16">
                    <circle class="progress-ring" 
                            stroke="rgba(255,255,255,0.2)"
                            stroke-width="4"
                            fill="transparent"
                            r="24"
                            cx="32"
                            cy="32"/>
                    <circle class="progress-ring"
                            stroke="#4299e1"
                            stroke-width="4"
                            fill="transparent"
                            r="24"
                            cx="32"
                            cy="32"/>
                </svg>
            </div>
        </div>

        <!-- Exam Selection -->
        <div class="glass-card rounded-xl p-6 mb-8">
            <div class="flex items-center space-x-4">
                <i class="fas fa-book-open text-2xl opacity-80"></i>
                <select id="examSelect" class="flex-1 bg-transparent border border-white border-opacity-20 rounded-lg p-3">
                    <option value="">Select an exam...</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, title FROM exams ORDER BY created_at DESC");
                    while ($exam = $stmt->fetch()) {
                        echo "<option value='{$exam['id']}'>{$exam['title']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Student List -->
        <div id="studentsList" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php
            if (isset($_GET['exam_id'])) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT 
                        u.user_id,
                        u.first_name,
                        u.last_name,
                        u.fillier,
                        u.group_column,
                        (SELECT COUNT(*) FROM student_answers WHERE student_id = u.user_id AND exam_id = :exam_id) as answers_count,
                        (SELECT COUNT(*) FROM student_answers WHERE student_id = u.user_id AND exam_id = :exam_id) as graded_count
                    FROM users u
                    JOIN student_answers sa ON u.user_id = sa.student_id
                    WHERE sa.exam_id = :exam_id
                ");
                $stmt->execute([':exam_id' => $_GET['exam_id']]);
                
                while ($student = $stmt->fetch()) {
                    $progress = $student['answers_count'] > 0 ? 
                        ($student['graded_count'] / $student['answers_count']) * 100 : 0;
                    ?>
                    <div class="glass-card rounded-xl p-6 answer-card">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h3>
                                <p class="text-sm opacity-80"><?= htmlspecialchars($student['fillier']) ?> - <?= htmlspecialchars($student['group_column']) ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold"><?= round($progress) ?>%</div>
                                <div class="text-sm opacity-80">Graded</div>
                            </div>
                        </div>
                        <button  onclick="showAnswers(<?= $student['user_id'] ?>)" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 transition-colors">
                                <a href="save_grade.php"></a>
                            Grade Answers
                        </button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>

        <!-- Answers Modal -->
        <div id="answersModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="container mx-auto p-6 h-full overflow-auto">
                <div class="max-w-4xl mx-auto bg-gray-900 rounded-xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Student Answers</h2>
                        <button onclick="closeAnswersModal()" class="text-2xl">&times;</button>
                    </div>
                    <div id="answersContainer" class="space-y-6">
                        <!-- Answers will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewResults(studentId) {
    window.location.href = `grade_exams.php?student_id=${studentId}`;
}
        // Enhanced grading functionality
        function showAnswers(studentId) {
            const examId = document.getElementById('examSelect').value;
            fetch(`get_student_answers.php?student_id=${studentId}&exam_id=${examId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('answersContainer');
                    container.innerHTML = '';
                    
                    data.answers.forEach(answer => {
                        const answerElement = document.createElement('div');
                        answerElement.className = 'glass-card rounded-xl p-6 mb-4';
                        
                        let answerContent = `
                            <div class="mb-4">
                                <h3 class="text-xl font-semibold mb-2">${answer.question_text}</h3>
                                <p class="opacity-80">Student's Answer: ${answer.answer_text}</p>
                            </div>
                        `;
                        
                        if (answer.type === 'mcq') {
                            // For MCQ, show correct answers and auto-grade
                            const isCorrect = answer.correct_answers.includes(answer.answer_text);
                            answerContent += `
                                <div class="mt-2 ${isCorrect ? 'text-green-400' : 'text-red-400'}">
                                    <i class="fas fa-${isCorrect ? 'check' : 'times'} mr-2"></i>
                                    ${isCorrect ? 'Correct' : 'Incorrect'}
                                </div>
                            `;
                        } else {
                            // For open questions, show grading input
                            answerContent += `
                                <div class="mt-4">
                                    <label class="block text-sm opacity-80 mb-2">Grade (max ${answer.points} points):</label>
                                    <input type="number" 
                                           class="grade-input rounded-lg px-4 py-2 w-24 text-white"
                                           min="0" 
                                           max="${answer.points}"
                                           value="${answer.given_points || ''}"
                                           onchange="saveGrade(${answer.submission_id}, this.value)">
                                </div>
                            `;
                        }
                        
                        answerElement.innerHTML = answerContent;
                        container.appendChild(answerElement);
                    });
                    
                    document.getElementById('answersModal').classList.remove('hidden');
                });
        }

        function closeAnswersModal() {
            document.getElementById('answersModal').classList.add('hidden');
        }

        function saveGrade(submissionId, points) {
            fetch('save_grade.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ submission_id: submissionId, points: points })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success indicator
                    const input = event.target;
                    input.classList.add('bg-green-500', 'bg-opacity-20');
                    setTimeout(() => {
                        input.classList.remove('bg-green-500', 'bg-opacity-20');
                    }, 1000);
                    
                    // Update progress
                    updateProgress();
                }
            });
        }

        function updateProgress() {
            fetch(`get_grading_progress.php?exam_id=${document.getElementById('examSelect').value}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('progressPercent').textContent = `${Math.round(data.progress)}%`;
                    updateProgressRing(data.progress);
                });
        }

        function updateProgressRing(percent) {
            const circles = document.querySelectorAll('.progress-ring');
            const radius = circles[0].r.baseVal.value;
            const circumference = radius * 2 * Math.PI;
            
            circles[0].style.strokeDasharray = `${circumference} ${circumference}`;
            circles[1].style.strokeDasharray = `${circumference} ${circumference}`;
            circles[1].style.strokeDashoffset = circumference - (percent / 100) * circumference;
        }

        // Initialize exam selection
        document.getElementById('examSelect').addEventListener('change', function() {
            window.location.href = `?exam_id=${this.value}`;
        });

        // Set initial exam selection if present in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('exam_id')) {
            document.getElementById('examSelect').value = urlParams.get('exam_id');
            updateProgress();
        }
    </script>
</body>
</html>