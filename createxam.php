<?php
// create_exam.php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    $_SESSION['error_message'] = "You must be logged in as a teacher to create an exam.";
    header("Location: login.php");
    exit();
}

// Initialize variables
$filieres = [];
$groups = [];
$error_message = '';
$success_message = '';

// Fetch filieres from database
try {
    $stmt = $pdo->query("SELECT id, name FROM filieres ORDER BY name");
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching filieres: " . $e->getMessage();
}

// Fetch groups from database
try {
    $stmt = $pdo->query("SELECT id, name FROM student_groups ORDER BY name");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching groups: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate and sanitize inputs
        $title = htmlspecialchars(trim($_POST['examTitle']));
        $description = htmlspecialchars(trim($_POST['examDescription']));
        $start_date = $_POST['examStartDate'];
        $end_date = $_POST['examEndDate'];
        $duration = (int)$_POST['examDuration'];
        $filiere_id = isset($_POST['examFiliere']) ? (int)$_POST['examFiliere'] : null;
        $group_id = isset($_POST['examGroup']) ? (int)$_POST['examGroup'] : null;
        $teacher_id = $_SESSION['user_id'];
        $current_timestamp = date('Y-m-d H:i:s');
        
        // Validation
        $errors = [];
        
        if (empty($title)) {
            $errors[] = "Exam title is required";
        }
        
        if (empty($start_date) || empty($end_date)) {
            $errors[] = "Start and end dates are required";
        } elseif (strtotime($start_date) >= strtotime($end_date)) {
            $errors[] = "End date must be after start date";
        }
        
        if ($duration <= 0) {
            $errors[] = "Duration must be a positive number";
        }
        
        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        // Insert exam details
        $stmt = $pdo->prepare("
            INSERT INTO exams (
                title, 
                description, 
                start_date, 
                end_date, 
                duration, 
                filiere_id, 
                group_id, 
                teacher_id,
                created_at, 
                updated_at,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, 
            $description, 
            $start_date, 
            $end_date, 
            $duration, 
            $filiere_id, 
            $group_id, 
            $teacher_id,
            $current_timestamp, 
            $current_timestamp,
            'published'
        ]);

        $exam_id = $pdo->lastInsertId();

        // Process questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $questionId => $question) {
                $q_text = htmlspecialchars(trim($question['text']));
                $q_points = (int)$question['points'];
                $q_type = isset($question['type']) ? $question['type'] : 'mcq';

                // Validate question
                if (empty($q_text)) {
                    throw new Exception("Question text cannot be empty");
                }
                
                if ($q_points <= 0) {
                    throw new Exception("Question points must be a positive number");
                }

                // Handle file upload
                $file_path = null;
                if (!empty($_FILES['questions']['name'][$questionId]['file'])) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_name = basename($_FILES['questions']['name'][$questionId]['file']);
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                    
                    if (!in_array($file_ext, $allowed_ext)) {
                        throw new Exception("Invalid file type. Allowed: JPG, PNG, PDF, DOC, DOCX");
                    }
                    
                    $file_path = $upload_dir . uniqid() . '_' . $file_name;
                    
                    if (!move_uploaded_file($_FILES['questions']['tmp_name'][$questionId]['file'], $file_path)) {
                        throw new Exception("Failed to upload file for question");
                    }
                }

                // Insert question
                $stmt = $pdo->prepare("
                    INSERT INTO questions (
                        exam_id, 
                        question_text, 
                        points, 
                        question_type, 
                        type,
                        file_path
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$exam_id, $q_text, $q_points, $q_type, $q_type, $file_path]);

                $question_id = $pdo->lastInsertId();

                // Process MCQ options
                if ($q_type === 'mcq' && isset($question['answers']) && is_array($question['answers'])) {
                    $option_order = 1;
                    $has_correct_answer = false;
                    
                    foreach ($question['answers'] as $answer) {
                        $a_text = htmlspecialchars(trim($answer['text']));
                        $is_correct = isset($answer['correct']) && $answer['correct'] ? 1 : 0;
                        
                        if ($is_correct) {
                            $has_correct_answer = true;
                        }
                        
                        if (empty($a_text)) {
                            throw new Exception("Answer text cannot be empty");
                        }

                        // Insert MCQ option
                        $stmt = $pdo->prepare("
                            INSERT INTO question_options (
                                question_id, 
                                option_text, 
                                is_correct,
                                option_order
                            ) VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$question_id, $a_text, $is_correct, $option_order]);
                        $option_order++;
                    }
                    
                    if (!$has_correct_answer) {
                        throw new Exception("Multiple choice questions must have at least one correct answer");
                    }
                }
            }
        } else {
            throw new Exception("Exam must have at least one question");
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Exam created successfully!";
        header("Location: teacher.php");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = 'Error creating exam: ' . $e->getMessage();
        header("Location: create_exam.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7C5DFA;
            --primary-hover: #9277FF;
            --secondary: #252945;
            --success: #33D69F;
            --info: #7E88C3;
            --warning: #FF8F00;
            --danger: #EC5757;
            --dark: #0C0E16;
            --light: #F8F8FB;
            --bg-primary: #141625;
            --bg-secondary: #1E2139;
            --bg-card: #252945;
            --text-primary: #FFFFFF;
            --text-secondary: #DFE3FA;
            --border-color: #393A51;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .header {
            padding: 2.5rem 0;
            text-align: center;
            margin-bottom: 2.5rem;
            background: var(--bg-secondary);
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .page-title {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 2.5rem;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-weight: 400;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .card {
            background: var(--bg-card);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }
        
        .card-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .form-control, .form-select {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-secondary);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 93, 250, 0.25);
            color: var(--text-primary);
        }
        
        .form-control::placeholder {
            color: var(--info);
            opacity: 0.7;
        }
        
        .btn {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            box-shadow: 0 4px 15px rgba(124, 93, 250, 0.35);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(124, 93, 250, 0.5);
        }
        
        .btn-outline-primary {
            color: var(--text-primary);
            border: 2px solid var(--primary);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-outline-secondary {
            color: var(--text-primary);
            border: 2px solid var(--text-secondary);
            background: transparent;
        }
        
        .btn-outline-secondary:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-outline-success {
            color: var(--success);
            border: 2px solid var(--success);
            background: transparent;
        }
        
        .btn-outline-success:hover {
            background: var(--success);
            color: var(--dark);
            transform: translateY(-3px);
        }
        
        .btn-outline-info {
            color: var(--info);
            border: 2px solid var(--info);
            background: transparent;
        }
        
        .btn-outline-info:hover {
            background: var(--info);
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-outline-danger {
            color: var(--danger);
            border: 2px solid var(--danger);
            background: transparent;
        }
        
        .btn-outline-danger:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-3px);
        }
        
        .question-card {
            position: relative;
            border-radius: 1rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .mcq-card {
            border-left: 6px solid var(--success);
        }
        
        .open-card {
            border-left: 6px solid var(--info);
        }
        
        .question-type-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .answer-option {
            background-color: var(--bg-secondary);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .answer-option:hover {
            background-color: var(--secondary);
            transform: translateX(5px);
        }
        
        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: 0.2rem;
            cursor: pointer;
            background-color: var(--bg-secondary);
            border: 2px solid var(--info);
            border-radius: 0.25rem;
        }
        
        .form-check-input:checked {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .form-check-label {
            cursor: pointer;
            font-weight: 500;
            color: var(--text-primary);
            padding-left: 0.5rem;
        }
        
        .delete-btn {
            color: var(--danger);
            background: rgba(236, 87, 87, 0.1);
            border: none;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .delete-btn:hover {
            background-color: rgba(236, 87, 87, 0.3);
            transform: scale(1.05);
        }
        
        .container {
            max-width: 1200px;
            padding: 2rem 1rem;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Alert Message */
        .alert {
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-danger {
            background-color: rgba(236, 87, 87, 0.2);
            color: var(--danger);
        }
        
        .alert-success {
            background-color: rgba(51, 214, 159, 0.2);
            color: var(--success);
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        /* Date/time inputs */
        input[type="datetime-local"] {
            color-scheme: dark;
        }
        
        /* Add question buttons */
        .add-question-btn-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            margin: 2.5rem 0;
        }
        
        .add-question-btn {
            padding: 1.25rem 2rem;
            border-radius: 1rem;
            font-size: 1rem;
            font-weight: 600;
            min-width: 240px;
            transition: all 0.4s ease;
        }
        
        .add-question-btn:hover {
            transform: translateY(-5px) scale(1.05);
        }
        
        /* Submit button area */
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .form-actions .btn {
            min-width: 180px;
        }

        /* Invalid feedback */
        .invalid-feedback {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="page-title">Create New Exam</h1>
            <p class="page-subtitle">Design your perfect assessment with multiple question types</p>
        </header>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error!</strong>
                    <p class="mb-0"><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong>
                    <p class="mb-0"><?= $_SESSION['success_message'] ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <form id="examForm" class="needs-validation" method="POST" action="" enctype="multipart/form-data" novalidate>
            <!-- Exam Details Card -->
            <div class="card fade-in">
                <h4 class="card-title">
                    <i class="fas fa-clipboard-list me-2"></i>Exam Details
                </h4>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examTitle" class="form-label">
                                <i class="fas fa-heading me-2"></i>Exam Title
                            </label>
                            <input type="text" id="examTitle" name="examTitle" class="form-control" 
                                   placeholder="Enter exam title" required>
                            <div class="invalid-feedback">Please provide an exam title.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examDuration" class="form-label">
                                <i class="fas fa-clock me-2"></i>Duration (minutes)
                            </label>
                            <input type="number" id="examDuration" name="examDuration" class="form-control" 
                                   placeholder="Duration in minutes" required min="1">
                            <div class="invalid-feedback">Please provide a valid duration.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label for="examDescription" class="form-label">
                                <i class="fas fa-align-left me-2"></i>Description
                            </label>
                            <textarea id="examDescription" name="examDescription" class="form-control" 
                                      rows="3" placeholder="Enter exam description" required></textarea>
                            <div class="invalid-feedback">Please provide a description.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examFiliere" class="form-label">
                                <i class="fas fa-graduation-cap me-2"></i>Filière
                            </label>
                            <select id="examFiliere" name="examFiliere" class="form-select" required>
                                <option value="" disabled selected>Select Filière</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?= htmlspecialchars($filiere['id']) ?>">
                                        <?= htmlspecialchars($filiere['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a filière.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examGroup" class="form-label">
                                <i class="fas fa-users me-2"></i>Student Group
                            </label>
                            <select id="examGroup" name="examGroup" class="form-select" required>
                                <option value="" disabled selected>Select Group</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= htmlspecialchars($group['id']) ?>">
                                        <?= htmlspecialchars($group['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a group.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examStartDate" class="form-label">
                                <i class="fas fa-calendar-plus me-2"></i>Start Date & Time
                            </label>
                            <input type="datetime-local" id="examStartDate" name="examStartDate" 
                                   class="form-control" required>
                            <div class="invalid-feedback">Please provide a start date and time.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="examEndDate" class="form-label">
                                <i class="fas fa-calendar-minus me-2"></i>End Date & Time
                            </label>
                            <input type="datetime-local" id="examEndDate" name="examEndDate" 
                                   class="form-control" required>
                            <div class="invalid-feedback">Please provide an end date and time.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Container -->
            <div id="questionsContainer"></div>

            <!-- Add Question Buttons -->
            <div class="add-question-btn-container">
                <button type="button" class="btn btn-outline-success add-question-btn" onclick="addQuestion('mcq')">
                    <i class="fas fa-plus-circle me-2"></i>Add Multiple Choice
                </button>
                <button type="button" class="btn btn-outline-info add-question-btn" onclick="addQuestion('open')">
                    <i class="fas fa-pen me-2"></i>Add Open Question
                </button>
            </div>

            <!-- Submit Buttons -->
            <div class="form-actions">
                <a href="teacher.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Create Exam
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize date/time inputs with current time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            
            document.getElementById('examStartDate').min = localISOTime;
            document.getElementById('examEndDate').min = localISOTime;
        });

        // Form validation
        (function() {
            'use strict';
            
            // Fetch all forms we want to apply validation to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Check for questions
                    const questionsContainer = document.getElementById('questionsContainer');
                    if (questionsContainer.children.length === 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        alert('Please add at least one question.');
                        return;
                    }

                    // Validate MCQ answers
                    if (!validateMCQAnswers()) {
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }

                    // Validate date range
                    const startDate = new Date(document.getElementById('examStartDate').value);
                    const endDate = new Date(document.getElementById('examEndDate').value);
                    
                    if (endDate <= startDate) {
                        event.preventDefault();
                        event.stopPropagation();
                        alert('End date must be after start date.');
                        return;
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Function to add a new question
        function addQuestion(type) {
            const questionsContainer = document.getElementById('questionsContainer');
            const questionIndex = questionsContainer.children.length;

            const questionCard = document.createElement('div');
            questionCard.className =` card question-card fade-in` + (type === 'mcq' ? ' mcq-card' : ' open-card');
            questionCard.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">
                            <span class="question-type-badge ${type === 'mcq' ? 'bg-success' : 'bg-info'}">
                                <i class="fas fa-${type === 'mcq' ? 'list-ul' : 'pen'}"></i>
                                ${type === 'mcq' ? 'Multiple Choice Question' : 'Open Question'}
                            </span>
                        </h5>
                        <button type="button" class="btn btn-outline-danger delete-btn" onclick="deleteQuestion(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <label for="questionText_${questionIndex}" class="form-label">
                            <i class="fas fa-question-circle me-2"></i>Question Text
                        </label>
                        <textarea id="questionText_${questionIndex}" name="questions[${questionIndex}][text]" 
                                  class="form-control" rows="3" placeholder="Enter your question here..." required></textarea>
                        <div class="invalid-feedback">Please provide a question text.</div>
                    </div>
                    
                    <input type="hidden" name="questions[${questionIndex}][type]" value="${type}">
                    
                    <div class="mb-4">
                                               <label for="questionPoints_${questionIndex}" class="form-label">
                            <i class="fas fa-star me-2"></i>Points
                        </label>
                        <input type="number" id="questionPoints_${questionIndex}" 
                               name="questions[${questionIndex}][points]" class="form-control" 
                               placeholder="Points for this question" required min="1">
                        <div class="invalid-feedback">Please provide valid points (minimum 1).</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="questionFile_${questionIndex}" class="form-label">
                            <i class="fas fa-paperclip me-2"></i>Attachment (Optional)
                        </label>
                        <input type="file" id="questionFile_${questionIndex}" 
                               name="questions[${questionIndex}][file]" class="form-control" 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Allowed: JPG, PNG, PDF, DOC, DOCX (Max 5MB)</small>
                    </div>
                    
                    ${type === 'mcq' ? `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="fas fa-list-ol me-2"></i>Answer Options
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="addAnswerOption(${questionIndex})">
                                <i class="fas fa-plus me-1"></i>Add Option
                            </button>
                        </div>
                        
                        <div id="answerOptions_${questionIndex}" class="answer-options-container">
                            <!-- Answer options will be added here -->
                        </div>
                        <div id="mcqError_${questionIndex}" class="text-danger small mt-2 d-none">
                            At least one correct answer must be selected.
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            questionsContainer.appendChild(questionCard);
            
            // For MCQ questions, add initial answer options
            if (type === 'mcq') {
                addAnswerOption(questionIndex);
                addAnswerOption(questionIndex);
            }
            
            // Scroll to the newly added question
            questionCard.scrollIntoView({ behavior: 'smooth' });
        }

        // Function to add answer options for MCQ questions
        function addAnswerOption(questionIndex) {
            const answerOptionsContainer = document.getElementById(`answerOptions_${questionIndex}`);   
            const optionIndex = answerOptionsContainer.children.length;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'answer-option d-flex align-items-center gap-3';
            optionDiv.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                           name="questions[${questionIndex}][answers][${optionIndex}][correct]" 
                           id="correct_${questionIndex}_${optionIndex}">
                    <label class="form-check-label" for="correct_${questionIndex}_${optionIndex}">
                        Correct Answer
                    </label>
                </div>
                
                <input type="text" class="form-control flex-grow-1" 
                       name="questions[${questionIndex}][answers][${optionIndex}][text]" 
                       placeholder="Enter answer option" required>
                
                <button type="button" class="btn btn-outline-danger delete-btn" 
                        onclick="deleteAnswerOption(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            answerOptionsContainer.appendChild(optionDiv);
        }

        // Function to delete an answer option
        function deleteAnswerOption(button) {
            const optionDiv = button.closest('.answer-option');
            optionDiv.remove();
            
            // Revalidate MCQ answers
            validateMCQAnswers();
        }

        // Function to delete a question
        function deleteQuestion(button) {
            if (confirm('Are you sure you want to delete this question?')) {
                const questionCard = button.closest('.question-card');
                questionCard.remove();
            }
        }

        // Function to validate that all MCQ questions have at least one correct answer
        function validateMCQAnswers() {
            let isValid = true;
            
            document.querySelectorAll('.mcq-card').forEach((card, index) => {
                const questionIndex = Array.from(document.getElementById('questionsContainer').children).indexOf(card);
                const hasCorrectAnswer = card.querySelectorAll('input[type="checkbox"]:checked').length > 0;
                const errorElement = document.getElementById(`mcqError_${questionIndex}`);
                
                if (!hasCorrectAnswer) {
                    errorElement.classList.remove('d-none');
                    isValid = false;
                } else {
                    errorElement.classList.add('d-none');
                }
            });
            
            return isValid;
        }

        // Set minimum end date based on start date
        document.getElementById('examStartDate').addEventListener('change', function() {
            const startDate = this.value;
            document.getElementById('examEndDate').min = startDate;
            
            // If current end date is before new start date, update it
            if (document.getElementById('examEndDate').value < startDate) {
                document.getElementById('examEndDate').value = startDate;
            }
        });
    </script>
</body>
</html>