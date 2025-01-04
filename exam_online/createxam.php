<?php 

require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['examTitle']);
    $description = $conn->real_escape_string($_POST['examDescription']);
    $start_date = $conn->real_escape_string($_POST['examStartDate']);
    $end_date = $conn->real_escape_string($_POST['examEndDate']);
    $duration = (int)$_POST['examDuration'];

    // Insert exam details
    $sql = "INSERT INTO exams (title, description, start_date, end_date, duration) 
            VALUES ('$title', '$description', '$start_date', '$end_date', $duration)";
    
    if ($conn->query($sql)) {
        $exam_id = $conn->insert_id;
        
        // Insert questions
        foreach ($_POST['questions'] as $q_id => $question) {
            $q_text = $conn->real_escape_string($question['text']);
            $q_points = (int)$question['points'];
            $q_type = isset($question['answers']) ? 'mcq' : 'open';
            
            $sql = "INSERT INTO questions (exam_id, question_text, points, type) 
                    VALUES ($exam_id, '$q_text', $q_points, '$q_type')";
            
            if ($conn->query($sql)) {
                $question_id = $conn->insert_id;
                
                // Insert answers for MCQ
                if (isset($question['answers'])) {
                    foreach ($question['answers'] as $a_id => $answer) {
                        $a_text = $conn->real_escape_string($answer['text']);
                        $is_correct = isset($answer['correct']) ? 1 : 0;
                        
                        $sql = "INSERT INTO answers (question_id, answer_text, is_correct) 
                                VALUES ($question_id, '$a_text', $is_correct)";
                        $conn->query($sql);
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Exam created successfully']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Error creating exam']);
    exit;
}
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Exam Creator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f6f8ff 0%, #ffffff 100%);
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --danger: #ef4444;
            --card-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.1);
            --card-glow: 0 0 20px rgba(79, 70, 229, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 2rem;
            color: #1f2937;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .exam-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            animation: slideInUp 0.5s ease;
        }

        .card:hover {
            box-shadow: var(--card-glow);
            transform: translateY(-4px);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            color: #374151;
        }

        .input-field {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .question-card {
            position: relative;
            overflow: hidden;
            animation: scaleIn 0.4s ease;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .question-type {
            padding: 0.75rem 1.5rem;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.95rem;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .answer-option {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
            animation: slideInLeft 0.3s ease;
            transition: all 0.3s ease;
        }

        .answer-option:hover {
            background: #f1f5f9;
            transform: scale(1.02);
        }

        .checkbox-wrapper {
            position: relative;
        }

        .custom-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .custom-checkbox:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 16px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            animation: fadeInUp 0.5s ease;
        }

        .delete-btn {
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            color: #94a3b8;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            color: var(--danger);
            background: #fee2e2;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Create New Exam</h1>
            <p>Design your perfect assessment</p>
        </header>

        <form class="exam-form">
             <section class="card">
                <!-- Previous title and description fields remain -->

                <!-- New date and time fields -->
                <div class="input-group">
                    <label for="examStartDate">Start Date & Time</label>
                    <input type="datetime-local" id="examStartDate" name="examStartDate" 
                           class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examEndDate">End Date & Time</label>
                    <input type="datetime-local" id="examEndDate" name="examEndDate" 
                           class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examDuration">Duration (minutes)</label>
                    <input type="number" id="examDuration" name="examDuration" 
                           class="input-field" required min="1">
                </div>
            </section>
            <section class="card">
                <div class="input-group">
                    <label for="examTitle">Exam Title</label>
                    <input type="text" id="examTitle" class="input-field" placeholder="Enter exam title" required>
                </div>

                <div class="input-group">
                    <label for="examDescription">Description</label>
                    <textarea id="examDescription" class="input-field" rows="4" placeholder="Provide exam description" required></textarea>
                </div>

             
            </section>

            <div id="questionsContainer"></div>

            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" onclick="addQuestion('mcq')">
                    <i class="fas fa-plus-circle"></i>
                    Add Multiple Choice
                </button>
                <button type="button" class="btn btn-secondary" onclick="addQuestion('open')">
                    <i class="fas fa-pen"></i>
                    Add Open Question
                </button>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-save"></i>
                    Save Draft
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Publish Exam
                </button>
            </div>
        </form>
    </div>

    <script>
        function addQuestion(type) {
            const container = document.getElementById('questionsContainer');
            const questionId = Date.now();
            const questionDiv = document.createElement('div');
            questionDiv.className = 'card question-card';
            
            const typeText = type === 'mcq' ? 'Multiple Choice' : 'Open Question';
            const typeIcon = type === 'mcq' ? 'fas fa-list-ul' : 'fas fa-paragraph';

            questionDiv.innerHTML = `
                <div class="question-header">
                    <div class="question-type">
                        <i class="${typeIcon}"></i>
                        ${typeText}
                    </div>
                    <button type="button" class="delete-btn" onclick="removeQuestion(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="input-group">
                    <label>Question Text</label>
                    <textarea name="questions[${questionId}][text]" class="input-field" required></textarea>
                </div>

                <div class="input-group">
                    <label>Points</label>
                    <input type="number" name="questions[${questionId}][points]" class="input-field" required min="1">
                </div>

                ${type === 'mcq' ? `
                    <div class="answers-container" id="answers_${questionId}">
                        <div class="action-buttons" style="margin-top: 1rem;">
                            <button type="button" class="btn btn-secondary" onclick="addAnswer(${questionId})">
                                <i class="fas fa-plus"></i>
                                Add Option
                            </button>
                        </div>
                    </div>
                ` : ''}
            `;

            container.appendChild(questionDiv);
            if (type === 'mcq') {
                addAnswer(questionId);
                addAnswer(questionId);
            }
        }

        function addAnswer(questionId) {
            const container = document.querySelector(`#answers_${questionId}`);
            const answerDiv = document.createElement('div');
            answerDiv.className = 'answer-option';
            
            const answerId = Date.now();
            answerDiv.innerHTML = `
                <div class="checkbox-wrapper">
                    <input type="checkbox" class="custom-checkbox" name="questions[${questionId}][answers][${answerId}][correct]">
                </div>
                <input type="text" name="questions[${questionId}][answers][${answerId}][text]" 
                       class="input-field" placeholder="Enter answer option" required>
                <button type="button" class="delete-btn" onclick="this.closest('.answer-option').remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.insertBefore(answerDiv, container.lastElementChild);
        }

        function removeQuestion(button) {
            button.closest('.question-card').remove();
        } document.getElementById('examForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate dates and duration
            const startDate = new Date(document.getElementById('examStartDate').value);
            const endDate = new Date(document.getElementById('examEndDate').value);
            const duration = parseInt(document.getElementById('examDuration').value);
            
            if (endDate <= startDate) {
                alert('End date must be after start date');
                return;
            }
            
            const durationInMs = duration * 60 * 1000;
            const availableTime = endDate - startDate;
            
            if (durationInMs > availableTime) {
                alert('Exam duration cannot be longer than the available time window');
                return;
            }
            
            // Submit form via AJAX
            try {
                const response = await fetch('create_exam.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.href = 'exam_list.php';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error creating exam: ' + error.message);
            }
        });
    </script>
</body>
</html>