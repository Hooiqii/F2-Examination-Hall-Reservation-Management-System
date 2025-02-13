<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the logged-in user is an admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page or display an error page
    header('Location: login.php');
    exit();
}

// Fetch feedback data from the database
$sql = "SELECT f.*, u.user_username 
        FROM feedback f
        INNER JOIN users u ON f.user_id = u.user_id";
$feedback_result = $conn->query($sql);

if ($feedback_result->num_rows > 0) {
    // Set headers to download file rather than display
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="feedback_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    // Begin writing the Excel file
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>No.</th>';
    echo '<th>Satisfaction</th>';
    echo '<th>Ease Of Use</th>';
    echo '<th>Functionality</th>';
    echo '<th>Feature Suggestion</th>';
    echo '<th>Performance</th>';
    echo '<th>Additional Comment</th>';
    echo '<th>User</th>';
    echo '</tr>';

    $counter = 1;
    while ($row = $feedback_result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $counter . '</td>';
        echo '<td>' . $row['satisfaction'] . '</td>';
        echo '<td>' . $row['easeOfUse'] . '</td>';
        echo '<td>' . $row['functionality'] . '</td>';
        echo '<td>' . ($row['feature'] != '' ? $row['feature'] : '-') . '</td>';
        echo '<td>' . $row['performance'] . '</td>';
        echo '<td>' . ($row['comment'] != '' ? $row['comment'] : '-') . '</td>';
        echo '<td>' . $row['user_username'] . '</td>';
        echo '</tr>';
        $counter++;
    }

    echo '</table>';
}
exit;
