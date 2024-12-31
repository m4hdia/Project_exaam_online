<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard - Exam System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Teacher Dashboard</h1>
            <div class="flex items-center space-x-4">
                <a href="my_exams.php" class="hover:text-gray-200">My Exams</a>
                <a href="create_exam.php" class="hover:text-gray-200">Create Exam</a>
                <a href="view_results.php" class="hover:text-gray-200">Results</a>
                <a href="logout.php" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Active Exams -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">Active Exams</h3>
                <?php
                $teacher_id = $_SESSION['user_id'];
                $sql = "SELECT * FROM exams WHERE created_by = ? AND status = 'active'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='border-b py-3'>";
                    echo "<h4 class='font-medium'>" . htmlspecialchars($row['title']) . "</h4>";
                    echo "<p class='text-sm text-gray-600'>Duration: " . $row['duration'] . " minutes</p>";
                    echo "<div class='mt-2'>";
                    echo "<a href='view_exam.php?id=" . $row['id'] . "' class='text-blue-500 hover:text-blue-700 mr-3'>View</a>";
                    echo "<a href='edit_exam.php?id=" . $row['id'] . "' class='text-green-500 hover:text-green-700'>Edit</a>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
            
            <!-- Student Results -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">Recent Results</h3>
                <?php
                $sql = "SELECT e.title, u.email, er.score, er.completed_at 
                        FROM exam_results er 
                        JOIN exams e ON er.exam_id = e.id 
                        JOIN users u ON er.student_id = u.id 
                        WHERE e.created_by = ? 
                        ORDER BY er.completed_at DESC 
                        LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='border-b py-3'>";
                    echo "<h4 class='font-medium'>" . htmlspecialchars($row['title']) . "</h4>";
                    echo "<p class='text-sm text-gray-600'>Student: " . htmlspecialchars($row['email']) . "</p>";
                    echo "<p class='text-sm text-gray-600'>Score: " . $row['score'] . "%</p>";
                    echo "<p class='text-xs text-gray-500'>Completed: " . $row['completed_at'] . "</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>