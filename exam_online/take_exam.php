<?php
session_start();

// Vérifier si l'étudiant est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Récupérer les détails de l'examen
    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM exams e
        WHERE e.id = :exam_id
    ");
    $stmt->execute(['exam_id' => $exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $error_message = "Examen non trouvé.";
    } else {
        // Récupérer toutes les questions et les réponses correctes
        $stmt = $pdo->prepare("
            SELECT q.*, a.answer_text as correct_answer,
                   GROUP_CONCAT(DISTINCT CASE 
                       WHEN q.type = 'mcq' THEN a.answer_text 
                       ELSE NULL 
                   END SEPARATOR '|||') as mcq_options
            FROM questions q
            LEFT JOIN answers a ON q.id = a.question_id
            WHERE q.exam_id = :exam_id 
            GROUP BY q.id
            ORDER BY q.id ASC
        ");
        $stmt->execute(['exam_id' => $exam_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Démarrer le timer si ce n'est pas déjà fait
        if (!isset($_SESSION['exam_duration'])) {
            $_SESSION['exam_duration'] = time() + ($exam['duration'] * 60);
        }
    }

    // Gérer la soumission de l'examen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
        foreach ($_POST['answers'] as $question_id => $answers) {
            // Si c'est une question à choix multiples, les réponses sont un tableau
            $answer_text = is_array($answers) ? implode('|||', $answers) : $answers;

            $stmt = $pdo->prepare("
                INSERT INTO student_answers 
                (student_id, exam_id, question_id, answer_text, submitted_at) 
                VALUES (:student_id, :exam_id, :question_id, :answer_text, NOW())
                ON DUPLICATE KEY UPDATE answer_text = :answer_text, submitted_at = NOW()
            ");
            $stmt->execute([
                'student_id' => $_SESSION['user_id'],
                'exam_id' => $exam_id,
                'question_id' => $question_id,
                'answer_text' => $answer_text
            ]);
        }

        // Enregistrer la fin de l'examen
        $stmt = $pdo->prepare("
            INSERT INTO exam_results 
            (student_id, exam_id, submitted_at, completion_time) 
            VALUES (:student_id, :exam_id, NOW(), :completion_time)
        ");
        $stmt->execute([
            'student_id' => $_SESSION['user_id'],
            'exam_id' => $exam_id,
            'completion_time' => time() - ($_SESSION['exam_duration'] - ($exam['duration'] * 60))
        ]);

        // Rediriger vers une page de confirmation
        unset($_SESSION['exam_duration']);
        header("Location: exam_complete.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Une erreur s'est produite. Veuillez réessayer plus tard.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($exam) ? htmlspecialchars($exam['title']) : 'Examen' ?> - ExamOnline</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto p-4 max-w-4xl">
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?= htmlspecialchars($error_message) ?></p>
                </div>
                <a href="student_dashboard.php" class="mt-4 inline-block bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition duration-200">
                    Retour au tableau de bord
                </a>
            </div>
        <?php elseif (isset($exam) && !empty($questions)): ?>
            <!-- Timer -->
            <div id="timer" class="fixed top-4 right-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl shadow-lg text-lg font-semibold">
                <i class="fas fa-clock mr-2"></i>
                <span>Calcul...</span>
            </div>

            <!-- En-tête de l'examen -->
            <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($exam['title']) ?></h1>
                <div class="text-gray-600 space-y-2">
                    <p class="text-lg"><?= htmlspecialchars($exam['description']) ?></p>
                    <p class="flex items-center">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        Durée: <?= htmlspecialchars($exam['duration']) ?> minutes
                    </p>
                </div>
            </div>

            <!-- Formulaire de l'examen -->
            <form id="examForm" method="POST" action="" class="space-y-8">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="bg-white rounded-xl shadow-md p-8 transition duration-200 hover:shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-800">
                                Question <?= $index + 1 ?>
                            </h3>
                            <span class="bg-blue-100 text-blue-800 px-4 py-1 rounded-full text-sm">
                                <?= htmlspecialchars($question['points']) ?> points
                            </span>
                        </div>
                        
                        <p class="text-gray-700 mb-6"><?= htmlspecialchars($question['question_text']) ?></p>

                        <?php if ($question['type'] === 'mcq'): ?>
                            <div class="space-y-3">
                                <?php foreach (explode('|||', $question['mcq_options']) as $option): ?>
                                    <?php if ($option): ?>
                                        <label class="flex items-center p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition duration-200">
                                            <input 
                                                type="checkbox" 
                                                name="answers[<?= $question['id'] ?>][]" 
                                                value="<?= htmlspecialchars($option) ?>" 
                                                class="form-checkbox h-5 w-5 text-blue-600"
                                            >
                                            <span class="ml-3 text-gray-700"><?= htmlspecialchars($option) ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <textarea 
                                name="answers[<?= $question['id'] ?>]" 
                                rows="4" 
                                required
                                class="w-full p-4 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                placeholder="Écrivez votre réponse ici..."
                            ></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button 
                    type="submit" 
                    name="submit_exam" 
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white text-lg font-semibold px-8 py-4 rounded-xl hover:from-blue-600 hover:to-blue-700 transition duration-200 shadow-md hover:shadow-lg"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Soumettre l'examen
                </button>
            </form>

            <script>
                // Fonctionnalité du timer
                const endTime = <?= $_SESSION['exam_duration'] ?> * 1000;

                function updateTimer() {
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    if (distance <= 0) {
                        clearInterval(timerInterval);
                        document.getElementById('timer').innerHTML = '<i class="fas fa-clock mr-2"></i>Temps écoulé!';
                        document.getElementById('examForm').submit();
                        return;
                    }

                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    document.getElementById('timer').innerHTML = 
                        `<i class="fas fa-clock mr-2"></i>${minutes}m ${seconds}s`;

                    // Avertissement visuel lorsque moins de 5 minutes
                    if (minutes < 5) {
                        document.getElementById('timer').classList.add('animate-pulse');
                        document.getElementById('timer').classList.remove('from-blue-500', 'to-blue-600');
                        document.getElementById('timer').classList.add('from-red-500', 'to-red-600');
                    }
                }

                updateTimer();
                const timerInterval = setInterval(updateTimer, 1000);

                // Empêcher la navigation hors de la page
                window.onbeforeunload = () => "Êtes-vous sûr de vouloir quitter l'examen ? Vos réponses ne seront pas sauvegardées.";
                document.getElementById('examForm').onsubmit = () => window.onbeforeunload = null;
            </script>
        <?php endif; ?>
    </div>
</body>
</html>