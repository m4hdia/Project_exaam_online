<?php
include 'config.php';

$sql = "SELECT zzzzz`user_id`, `first_name`, `last_name`, `email`, `fillier`, `group_column` 
        FROM `users` 
        WHERE `status` = 'accepted'";
$result = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .action-buttons a {
            text-decoration: none;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            color: white;
            font-size: 14px;
        }
        .edit-btn {
            background-color: #2196F3;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .results-btn {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <h2>List of Accepted Students</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Fillier</th>
                <th>Group</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["user_id"] . "</td>";
                    echo "<td>" . $row["first_name"] . "</td>";
                    echo "<td>" . $row["last_name"] . "</td>";
                    echo "<td>" . $row["email"] . "</td>";
                    echo "<td>" . $row["fillier"] . "</td>";
                    echo "<td>" . $row["group_column"] . "</td>";
                    echo "<td class='action-buttons'>
                            <a href='edit_student.php?id=" . $row["user_id"] . "' class='edit-btn'>Edit</a>
                            <a href='delete_student.php?id=" . $row["user_id"] . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this student?\");'>Delete</a>
                            <a href='view_results.php?id=" . $row["user_id"] . "' class='results-btn'>View Results</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No students found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>