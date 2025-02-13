<?php
// Include necessary files and database connection
require_once('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $floorSelection = $_POST['floorSelection'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];

    if ($endDate == $startDate){$endDate = $startDate;}
    // Your SQL query to fetch the remaining capacity based on the selected floor, date, start time, and end time
    $sql_capacity = "SELECT hall_capacity FROM hall WHERE hall_floor = ?";
    $stmt_capacity = $conn->prepare($sql_capacity);
    $stmt_capacity->bind_param("s", $floorSelection);
    $stmt_capacity->execute();
    $result_capacity = $stmt_capacity->get_result();
    $data_capacity = $result_capacity->fetch_assoc();
    $hall_capacity = $data_capacity['hall_capacity'];

    if ($endDate>$startDate){
        $startDateObj = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);

        $interval = $startDateObj->diff($endDateObj);
        $diffInDays = $interval->days;

        $total_capacity_booked = 0;

        for ($i = 0; $i < $diffInDays; $i++) {
            $currentDate = clone $startDateObj; // Clone the start date object
            $currentDate->modify("+$i day");
            $sql_total_capacity = "SELECT SUM(bk_participantNo) as total_capacity FROM booking 
            WHERE bk_floorSelection = ? AND bk_startDate <= ? AND bk_endDate >= ? AND ((bk_startTime < ? AND bk_endTime > ?) OR (bk_startTime = ? AND bk_endTime = ?)) AND (bk_status='Approve' OR bk_status='Pending')";
            $stmt_total_capacity = $conn->prepare($sql_total_capacity);
            $currentDateStr = $currentDate->format('Y-m-d'); 
            $stmt_total_capacity->bind_param("sssssss", $floorSelection, $currentDateStr, $currentDateStr, $endTime, $startTime, $startTime, $endTime);
            $stmt_total_capacity->execute();
            $result_total_capacity = $stmt_total_capacity->get_result();
            $data_total_capacity = $result_total_capacity->fetch_assoc();
            if ($data_total_capacity['total_capacity'] > $total_capacity_booked ){
                $total_capacity_booked = $data_total_capacity['total_capacity'];
            }
        }
    }else{
        // Your SQL query to calculate the total capacity booked for the selected time range and floor
        $sql_total_capacity = "SELECT SUM(bk_participantNo) as total_capacity FROM booking 
        WHERE bk_floorSelection = ? AND bk_startDate <= ? AND bk_endDate >= ? AND ((bk_startTime < ? AND bk_endTime > ?) OR (bk_startTime = ? AND bk_endTime = ?)) AND (bk_status='Approve' OR bk_status='Pending')";
        $stmt_total_capacity = $conn->prepare($sql_total_capacity);
        $stmt_total_capacity->bind_param("sssssss", $floorSelection, $endDate, $startDate, $endTime, $startTime, $startTime, $endTime);
        $stmt_total_capacity->execute();
        $result_total_capacity = $stmt_total_capacity->get_result();
        $data_total_capacity = $result_total_capacity->fetch_assoc();
        $total_capacity_booked = $data_total_capacity['total_capacity'];

    }

    // Calculate remaining capacity
    $remaining_capacity = $hall_capacity - ($total_capacity_booked ?? 0);

    
    if($remaining_capacity<0){
        echo 0;
    }else{
        echo $remaining_capacity;
    }
    // echo $remaining_capacity;
}
