<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to create an exam.";
    exit();
}

try {
    $stmt = $pdo->query("SELECT DISTINCT fillier FROM users WHERE fillier IS NOT NULL");
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT DISTINCT group_column FROM users WHERE group_column IS NOT NULL");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Error fetching filières or groups: ' . $e->getMessage());
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();

        $title = htmlspecialchars(trim($_POST['examTitle']));
        $description = htmlspecialchars(trim($_POST['examDescription']));
        $start_date = $_POST['examStartDate'];
        $end_date = $_POST['examEndDate'];
        $duration = (int)$_POST['examDuration'];
        $filiere_id = (int)$_POST['examFiliere'];
        $group_id = (int)$_POST['examGroup'];
        $current_timestamp = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO exams (
                title, 
                description, 
                start_date, 
                end_date, 
                duration, 
                filiere_id, 
                group_id, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, 
            $description, 
            $start_date, 
            $end_date, 
            $duration, 
            $filiere_id, 
            $group_id, 
            $current_timestamp, 
            $current_timestamp
        ]);

        $exam_id = $pdo->lastInsertId();

        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $questionId => $question) {
                $q_text = htmlspecialchars(trim($question['text']));
                $q_points = (int)$question['points'];
                $q_type = isset($question['answers']) && is_array($question['answers']) ? 'mcq' : 'open';

                $file_path = null;
                if (isset($_FILES['questions']['tmp_name'][$questionId]['file']) && 
                    $_FILES['questions']['tmp_name'][$questionId]['file']) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $file_name = basename($_FILES['questions']['name'][$questionId]['file']);
                    $file_path = $upload_dir . uniqid() . '_' . $file_name;
                    move_uploaded_file($_FILES['questions']['tmp_name'][$questionId]['file'], $file_path);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO questions (
                        exam_id, 
                        question_text, 
                        points, 
                        type, 
                        file_path
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$exam_id, $q_text, $q_points, $q_type, $file_path]);

                $question_id = $pdo->lastInsertId();

                if ($q_type === 'mcq' && isset($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        $a_text = htmlspecialchars(trim($answer['text']));
                        $is_correct = !empty($answer['correct']) ? 1 : 0;

                        $stmt = $pdo->prepare("
                            INSERT INTO answers (
                                question_id, 
                                answer_text, 
                                is_correct
                            ) VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$question_id, $a_text, $is_correct]);
                    }
                }
            }
        }

        $pdo->commit();

        $_SESSION['success_message'] = "Exam created successfully!";
        header("Location: teacher.php");
        exit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'Error creating exam: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="createxam.css" rel="stylesheet">
</head>
<style>
 /* Modern CSS Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #4f46e5;
    --primary-hover: #4338ca;
    --danger-color: #ef4444;
    --danger-hover: #dc2626;
    --success-color: #22c55e;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --radius-md: 0.5rem;
    --radius-sm: 0.375rem;
}

body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background-color: var(--background-color);
    color: var(--text-primary);
    line-height: 1.5;
}

.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.header p {
    color: var(--text-secondary);
    font-size: 1.125rem;
}

.card {
    background: var(--card-background);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
}

.question-card {
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.05);
}

.input-group {
    margin-bottom: 1.5rem;
}

.input-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.input-field {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.input-field:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

select.input-field {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    appearance: none;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.answers-container {
    margin-top: 1rem;
}

.answer-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: var(--background-color);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
}

.custom-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.25rem;
    border: 2px solid var(--border-color);
    cursor: pointer;
    transition: all 0.2s ease;
}

.custom-checkbox:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
}

.btn-secondary {
    background-color: white;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background-color: var(--background-color);
}

.delete-btn {
    background: none;
    border: none;
    color: var(--danger-color);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
}

.delete-btn:hover {
    background-color: #fee2e2;
    color: var(--danger-hover);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

/* File input styling */
input[type="file"] {
    padding: 0.5rem 0;
}

input[type="file"]::file-selector-button {
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background-color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-right: 1rem;
}

input[type="file"]::file-selector-button:hover {
    background-color: var(--background-color);
}

/* Small text styling */
small {
    color: var(--text-secondary);
    display: block;
    margin-top: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        padding: 0 1rem;
        margin: 1rem auto;
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

    .answer-option {
        flex-direction: column;
        gap: 0.5rem;
    }
}
/* Style for the return button */
.btn-return {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: var(--primary-color);
    color: white;
    text-decoration: none; /* Remove underline */
    border: none;
    font-size: 1rem;
}

.btn-return:hover {
    background-color: var(--primary-hover);
    text-decoration: none; /* Ensure no underline on hover */
}
</style>
<body>
    <div class="container">
        <header class="header">
            <h1>Create New Exam</h1>
            <p>Design your perfect assessment</p>
        </header>

        <form id="examForm" class="exam-form" method="POST" action="" enctype="multipart/form-data">
            <section class="card">
                <div class="input-group">
                    <label for="examTitle">Exam Title</label>
                    <input type="text" id="examTitle" name="examTitle" class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examDescription">Description</label>
                    <textarea id="examDescription" name="examDescription" class="input-field" rows="4" required></textarea>
                </div>

                <div class="input-group">
                    <label for="examFiliere">Filière</label>
                    <select id="examFiliere" name="examFiliere" class="input-field" required>
                        <option value="" disabled selected>Select Filière</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= htmlspecialchars($filiere['fillier']) ?>">
                                <?= htmlspecialchars($filiere['fillier']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="examGroup">Group</label>
                    <select id="examGroup" name="examGroup" class="input-field" required>
                        <option value="" disabled selected>Select Group</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['group_column']) ?>">
                                <?= htmlspecialchars($group['group_column']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="examStartDate">Start Date & Time</label>
                    <input type="datetime-local" id="examStartDate" name="examStartDate" class="input-field custom-datetime" required>
                </div>

                <div class="input-group">
                    <label for="examEndDate">End Date & Time</label>
                    <input type="datetime-local" id="examEndDate" name="examEndDate" class="input-field custom-datetime" required>
                </div>

                <div class="input-group">
                    <label for="examDuration">Duration (minutes)</label>
                    <input type="number" id="examDuration" name="examDuration" class="input-field" required min="1" placeholder="Enter duration">
                </div>
            </section>

            <div id="questionsContainer"></div>

            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" onclick="addQuestion('mcq')">
                    <i class="fas fa-plus-circle"></i> Add Multiple Choice
                </button>
                <button type="button" class="btn btn-secondary" onclick="addQuestion('open')">
                    <i class="fas fa-pen"></i> Add Open Question
                </button>
            </div>

           <div class="action-buttons">
    <a href="teacher.php" class="btn btn-return">
        <i class="fas fa-arrow-left"></i> Back to Teacher Page
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> Create Exam
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
                        <i class="${typeIcon}"></i> ${typeText}
                    </div>
                    <button type="button" class="delete-btn" onclick="removeQuestion(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="input-group">
                    <label>Upload File (Optional)</label>
                    <input type="file" name="questions[${questionId}][file]" class="input-field" 
                           accept=".jpg,.jpeg,.png,.pdf">
                    <small>Allowed files: JPG, PNG, PDF (Max 5MB)</small>
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
                                <i class="fas fa-plus"></i> Add Option
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
                    <input type="checkbox" class="custom-checkbox" 
                           name="questions[${questionId}][answers][${answerId}][correct]">
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
        }
    </script>
</body>
</html>