<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');
require_once('mailer.php'); // Include the mailer script


$search = isset($_GET['search']) ? $_GET['search'] : '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT booking.*, users.user_email, users.user_name, admin.admin_name 
        FROM booking 
        LEFT JOIN users ON booking.user_id = users.user_id 
        LEFT JOIN admin ON booking.admin_id = admin.admin_id
        WHERE booking.bk_status != 'Deleted' 
        AND (booking.bk_purpose LIKE '%$search%')
        ORDER BY 
            CASE WHEN booking.bk_status = 'Pending' THEN 0 ELSE 1 END,
            booking.bk_startDate ASC,
            booking.bk_status ASC";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();
echo json_encode($data);

?>