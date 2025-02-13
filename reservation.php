<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');
require_once('mailer.php'); // Include the mailer script

// Check if the logged-in user is an admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page or display an error page
    header('Location: login.php');
    exit();
}

// Fetch total number of pending bookings
$pending_sql = "SELECT COUNT(*) as pending FROM booking WHERE bk_status = 'Pending'";
$pending_result = $conn->query($pending_sql);
$pending_row = $pending_result->fetch_assoc();
$total_pending = $pending_row['pending'];

// Get the current page or set default to 1
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$results_per_page = 20;
$start_from = ($page - 1) * $results_per_page;

// Fetch total number of bookings
$total_sql = "SELECT COUNT(*) as total FROM booking WHERE bk_status != 'Deleted'";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Fetch bookings including user_id with pagination
$sql = "SELECT booking.*, users.user_email, users.user_name, admin.admin_name 
        FROM booking 
        LEFT JOIN users ON booking.user_id = users.user_id 
        LEFT JOIN admin ON booking.admin_id = admin.admin_id
        WHERE booking.bk_status != 'Deleted' 
        ORDER BY 
            CASE WHEN booking.bk_status = 'Pending' THEN 0 ELSE 1 END,
            booking.bk_startDate ASC,
            booking.bk_status ASC
        LIMIT $start_from, $results_per_page";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching bookings: " . $conn->error);
}

// Handle POST request to update booking status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookingId']) && isset($_POST['action'])) {
    $bookingId = intval($_POST['bookingId']);
    $action = $conn->real_escape_string($_POST['action']);
    $adminId = intval($_SESSION['admin_id']); // Assuming admin_id is stored in session

    if (!in_array($action, ['Pending', 'Approve', 'Reject', 'Deleted'])) {
        die("Invalid action.");
    }

    $conn->begin_transaction();

    try {
        if ($action === 'Deleted') {
            // Check if booking with status 'Approve' can be deleted
            $checkSql = "SELECT bk_endDate, bk_endTime, bk_status FROM booking WHERE bk_id = ?";
            $stmt = $conn->prepare($checkSql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('i', $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking = $result->fetch_assoc();
            $stmt->close();

            if ($booking['bk_status'] === 'Approve') {
                $currentDateTime = new DateTime();
                $endDateTime = new DateTime($booking['bk_endDate'] . ' ' . $booking['bk_endTime']);

                if ($endDateTime > $currentDateTime) {
                    throw new Exception("Cannot delete an approved booking that hasn't ended yet.");
                }
            }

            $deleteSql = "DELETE FROM booking WHERE bk_id = ?";
            $stmt = $conn->prepare($deleteSql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('i', $bookingId);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            echo "Booking deleted successfully.";
        } else {
            $updateSql = "UPDATE booking SET bk_status = ?, admin_id = ? WHERE bk_id = ?";
            $stmt = $conn->prepare($updateSql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('sii', $action, $adminId, $bookingId);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            // Send email if the status is changed to 'Approve' or 'Reject'
            if (in_array($action, ['Approve', 'Reject'])) {
                sendReservationEmail($conn, $bookingId, $action);
            }
            echo "Booking status updated successfully.";
        }

        $conn->commit();
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
    exit;
}

// Function to send reservation email
function sendReservationEmail($conn, $bookingId, $action)
{
    global $mail; // Access the mailer object

    // Fetch booking details
    $bookingDetailsSql = "SELECT booking.*, users.user_email, users.user_name 
                          FROM booking 
                          LEFT JOIN users ON booking.user_id = users.user_id 
                          WHERE booking.bk_id = ?";
    $stmt = $conn->prepare($bookingDetailsSql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        die("No booking found with the provided ID.");
    }

    // Determine floor description
    $floorDescription = $booking['bk_floorSelection'] === 'top' ? 'Top Floor' : 'Lower Floor';

    // Map action to status message
    $statusMessage = $action === 'Approve' ? 'Your reservation has been <b><span style="color: green;">approved</span></b>.' : 'Your reservation has been <b><span style="color: red;">rejected</span></b>.';

    // Prepare email content
    $subject = "Reservation Status Update";
    $message = "<p style='font-family: Arial, sans-serif; font-size: 16px;'>Dear " . htmlspecialchars($booking['user_name']) . ",</p>";
    $message .= "<p style='font-family: Arial, sans-serif; font-size: 16px;'>Your reservation with the following details has been updated:</p>";
    $message .= "<table border='1' cellpadding='10' cellspacing='0' style='font-family: Arial, sans-serif; font-size: 14px; border-collapse: collapse; width: 100%; max-width: 600px;'>";
    $message .= "<tr style='background-color: #f2f2f2;'><th style='padding: 8px; text-align: left;'>Event</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_purpose']) . "</td></tr>";
    $message .= "<tr><th style='padding: 8px; text-align: left;'>Reserved Place</th><td style='padding: 8px;'>" . $floorDescription . "</td></tr>";
    $message .= "<tr style='background-color: #f2f2f2;'><th style='padding: 8px; text-align: left;'>Number of Participants</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_participantNo']) . "</td></tr>";
    $message .= "<tr><th style='padding: 8px; text-align: left;'>Start Date</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_startDate']) . "</td></tr>";
    $message .= "<tr style='background-color: #f2f2f2;'><th style='padding: 8px; text-align: left;'>End Date</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_endDate']) . "</td></tr>";
    $message .= "<tr><th style='padding: 8px; text-align: left;'>Start Time</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_startTime']) . "</td></tr>";
    $message .= "<tr style='background-color: #f2f2f2;'><th style='padding: 8px; text-align: left;'>End Time</th><td style='padding: 8px;'>" . htmlspecialchars($booking['bk_endTime']) . "</td></tr>";
    $message .= "<tr><th style='padding: 8px; text-align: left;'>Status</th><td style='padding: 8px;'>" . $statusMessage . "</td></tr>";
    $message .= "</table>";
    // Include important information only if the booking is approved
    if ($action === 'Approve') {
        $message .= "<p style='font-family: Arial, sans-serif; font-size: 16px;'><strong>Important Information:</strong></p>";
        $message .= "<p style='font-family: Arial, sans-serif; font-size: 16px;'>Please note the following instructions for using the F2 Examination Hall:</p>";
        $message .= "<ul style='font-family: Arial, sans-serif; font-size: 16px;'>";
        $message .= "<li>Applicants must collect and return the ACS access card at the PPA counter during working days and office hours.</li>";
        $message .= "<li>ACS card collection must be completed one day before the date of use of the Examination Hall.</li>";
        $message .= "<li>ACS card return must be made one day after the date of use of the Examination Hall on a working day.</li>";
        $message .= "</ul>";
    }
    $message .= "<p style='font-family: Arial, sans-serif; font-size: 16px;'>Thank you for using the F2 Examination Hall Reservation Management System!</p>";
    $message .= "<p style='font-family: Arial, sans-serif; font-size: 14px; color: #777; margin-top: 20px;'>This is an auto-generated email. Please do not reply.</p>";

    // Set email recipients
    $mail->addAddress($booking['user_email']);

    // Set email subject and body
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->isHTML(true); // Set email format to HTML

    // Send email
    if (!$mail->send()) {
        error_log("Error sending email: " . $mail->ErrorInfo);
        echo "Error sending email: " . $mail->ErrorInfo;
    }

    // Clear email recipients
    $mail->clearAddresses();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/reservation.css">
    <title>Reservation | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Reservation</h1>
        <div class="border-wrapper">
            <div class="function">
                <input type="text" id="searchBar" placeholder="Search Event...">
                <div class="notification">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($total_pending > 0) : ?>
                        <span class="badge"><?php echo $total_pending; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-list">
                <table id="eventTable">
                    <thead>
                        <tr>
                            <th><button class="sortButton" onclick="sortTable(0, this)"><i class="fa-solid fa-sort"></i>&nbsp;No.</button></th>
                            <th><button class="sortButton" onclick="sortTable(1, this)"><i class="fa-solid fa-sort"></i>&nbsp;Event</button></th>
                            <th><button class="sortButton" onclick="sortTable(2, this)"><i class="fa-solid fa-sort"></i>&nbsp;Level</button></th>
                            <th><button class="sortButton" onclick="sortTable(3, this)"><i class="fa-solid fa-sort"></i>&nbsp;Start Date</button></th>
                            <th><button class="sortButton" onclick="sortTable(4, this)"><i class="fa-solid fa-sort"></i>&nbsp;End Date</button></th>
                            <th><button class="sortButton" onclick="sortTable(5, this)"><i class="fa-solid fa-sort"></i>&nbsp;Start Time</button></th>
                            <th><button class="sortButton" onclick="sortTable(6, this)"><i class="fa-solid fa-sort"></i>&nbsp;End Time</button></th>
                            <th><button class="sortButton" onclick="sortTable(7, this)"><i class="fa-solid fa-sort"></i>&nbsp;Qty.</button></th>
                            <th><button class="sortButton" onclick="sortTable(8, this)"><i class="fa-solid fa-sort"></i>&nbsp;Admin</button></th>
                            <th><button class="sortButton" onclick="sortTable(9, this)"><i class="fa-solid fa-sort"></i>&nbsp;Status</button></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = $start_from + 1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter . "</td>";
                                echo "<td class='event-name'><a href='reservationDetails.php?id=" . $row['bk_id'] . "'>" . $row["bk_purpose"] . "</a></td>";
                                echo "<td>" . $row["bk_floorSelection"] . "</td>";
                                echo "<td>" . $row["bk_startDate"] . "</td>";
                                echo "<td>" . $row["bk_startDate"] . "</td>";
                                echo "<td>" . $row["bk_startTime"] . "</td>";
                                echo "<td>" . $row["bk_endTime"] . "</td>";
                                echo "<td>" . $row["bk_participantNo"] . "</td>";
                                echo "<td>" . (!empty($row["admin_name"]) ? htmlspecialchars($row["admin_name"]) : '-') . "</td>";
                                $statusColor = '';
                                switch ($row["bk_status"]) {
                                    case 'Pending':
                                        $statusColor = 'white';
                                        break;
                                    case 'Approve':
                                        $statusColor = '#D4EDDA';
                                        break;
                                    case 'Reject':
                                        $statusColor = '#F8D7DA';
                                        break;
                                    case 'Cancelled':
                                        $statusColor = '#FFF4D3';
                                        break;
                                    default:
                                        $statusColor = 'white';
                                        break;
                                }
                                echo "<td style='background-color: $statusColor;'>" . $row["bk_status"] . "</td>";
                                echo "<td>";
                                // Check if the booking status is 'Cancelled' to disable buttons
                                $disabled = $row["bk_status"] === 'Cancelled' ? 'disabled' : '';

                                echo "<button class='action-button approve' onclick=\"updateStatus(" . $row['bk_id'] . ", 'Approve')\" $disabled>Approve</button>";
                                echo "<button class='action-button reject' onclick=\"updateStatus(" . $row['bk_id'] . ", 'Reject')\" $disabled>Reject</button>";
                                echo "<button class='action-button delete' onclick=\"deleteBooking(" . $row['bk_id'] . ")\">Delete</button>";
                                echo "</td>";
                                echo "</tr>";
                                $counter++;
                            }
                        } else {
                            echo "<tr><td colspan='11'>No bookings found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
                <?php
                // Display pagination only if there is more than one page
                if ($total_pages > 1) {
                    // Display '<<' button to go to the first page
                    if ($page > 1) {
                        echo "<a href='reservation.php?page=1'>&lt;&lt;</a> ";
                    }

                    // Display previous button
                    if ($page > 1) {
                        echo "<a href='reservation.php?page=" . ($page - 1) . "'>&lt;</a> ";
                    }

                    // Display page numbers
                    $start_page = max(1, $page - 1);
                    $end_page = min($total_pages, $start_page + 3);

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<a class='active' href='reservation.php?page=" . $i . "'>" . $i . "</a> ";
                        } else {
                            echo "<a href='reservation.php?page=" . $i . "'>" . $i . "</a> ";
                        }
                    }

                    // Display next button
                    if ($page < $total_pages) {
                        echo "<a href='reservation.php?page=" . ($page + 1) . "'>&gt;</a> ";
                    }

                    // Display '>>' button to go to the last page
                    if ($page < $total_pages) {
                        echo "<a href='reservation.php?page=" . $total_pages . "'>&gt;&gt;</a>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <?php require_once('footer.php'); ?>
    <script>
        document.getElementById("searchBar").addEventListener("keyup", filterTable);

        function filterTable() {
            const input = document.getElementById("searchBar");
            const filter = input.value.toUpperCase();
            const pagination = document.getElementById("pagination");
            if (filter === "") {
                // Reload the page if the input is empty
                location.reload();
                return;
            } else {
                pagination.style.display = 'none';
            }

            // AJAX request to fetch filtered data
            const xhr = new XMLHttpRequest();
            xhr.open("GET", `searchReservation.php?search=${filter}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    const table = document.querySelector(".table-list table");
                    updateTable(data, table);
                }
            };
            xhr.send();
        }

        function updateTable(data, table) {
            const tbody = table.querySelector("tbody");
            tbody.innerHTML = ""; // Clear existing rows

            if (data.length > 0) {
                data.forEach((booking, index) => {
                    const row = document.createElement("tr");

                    const noCell = document.createElement("td");
                    noCell.textContent = index + 1;
                    row.appendChild(noCell);

                    const nameCell = document.createElement("td");
                    nameCell.classList.add('event-name');
                    const nameLink = document.createElement("a");
                    nameLink.href = `reservationDetails.php?id=${booking.bk_id}`;
                    nameLink.textContent = booking.bk_purpose;
                    nameCell.appendChild(nameLink);
                    row.appendChild(nameCell);

                    const floorCell = document.createElement("td");
                    floorCell.textContent = booking.bk_floorSelection;
                    row.appendChild(floorCell);

                    const startDateCell = document.createElement("td");
                    startDateCell.textContent = booking.bk_startDate;
                    row.appendChild(startDateCell);

                    const endDateCell = document.createElement("td");
                    endDateCell.textContent = booking.bk_endDate; // Assuming `bk_endDate` exists
                    row.appendChild(endDateCell);

                    const startTimeCell = document.createElement("td");
                    startTimeCell.textContent = booking.bk_startTime;
                    row.appendChild(startTimeCell);

                    const endTimeCell = document.createElement("td");
                    endTimeCell.textContent = booking.bk_endTime;
                    row.appendChild(endTimeCell);

                    const participantNoCell = document.createElement("td");
                    participantNoCell.textContent = booking.bk_participantNo;
                    row.appendChild(participantNoCell);

                    const organizerCell = document.createElement("td");
                    organizerCell.textContent = booking.admin_name || '-';
                    row.appendChild(organizerCell);

                    const statusCell = document.createElement("td");
                    statusCell.style.backgroundColor = getStatusColor(booking.bk_status);
                    statusCell.textContent = booking.bk_status;
                    row.appendChild(statusCell);

                    const actionCell = document.createElement("td");
                    const disabled = booking.bk_status === 'Cancelled' ? 'disabled' : '';

                    actionCell.innerHTML = `
                <button class='action-button approve' onclick="updateStatus(${booking.bk_id}, 'Approve')" ${disabled}>Approve</button>
                <button class='action-button reject' onclick="updateStatus(${booking.bk_id}, 'Reject')" ${disabled}>Reject</button>
                <button class='action-button delete' onclick="deleteBooking(${booking.bk_id})">Delete</button>
            `;
                    row.appendChild(actionCell);

                    tbody.appendChild(row);
                });
            } else {
                const noBookingsRow = document.createElement("tr");
                const cell = document.createElement("td");
                cell.setAttribute("colspan", "11");
                cell.textContent = "No bookings found";
                noBookingsRow.appendChild(cell);
                tbody.appendChild(noBookingsRow);
            }
        }

        function getStatusColor(status) {
            switch (status) {
                case 'Pending':
                    return 'white';
                case 'Approve':
                    return '#D4EDDA';
                case 'Reject':
                    return '#F8D7DA';
                case 'Cancelled':
                    return '#FFF4D3';
                default:
                    return 'white';
            }
        }



        function updateStatus(bookingId, action) {
            if (confirm("Are you sure you want to " + action + " this booking?")) {
                fetch('reservation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'bookingId=' + bookingId + '&action=' + action
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the booking status.');
                    });
            }
        }

        function deleteBooking(bookingId) {
            if (confirm("Are you sure you want to delete this booking?")) {
                fetch('reservation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'bookingId=' + bookingId + '&action=Deleted'
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the booking.');
                    });
            }
        }

        let sortStates = {};

        function sortTable(columnIndex, button) {
            const table = document.getElementById('eventTable');
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.rows);

            // Initialize the state if it doesn't exist
            if (!sortStates[columnIndex]) {
                sortStates[columnIndex] = 'none'; // Possible states: 'none', 'asc', 'desc'
            }

            // Determine the next state
            const nextState = getNextState(sortStates[columnIndex]);

            // Sort rows based on the next state
            if (nextState === 'asc') {
                rows.sort((rowA, rowB) => compareCells(rowA.cells[columnIndex], rowB.cells[columnIndex], columnIndex));
            } else if (nextState === 'desc') {
                rows.sort((rowA, rowB) => compareCells(rowB.cells[columnIndex], rowA.cells[columnIndex], columnIndex));
            }

            // If next state is 'none', sort by the 'No.' column
            if (nextState === 'none') {
                rows.sort((rowA, rowB) => compareCells(rowA.cells[0], rowB.cells[0], 0));
            }

            // Append sorted rows back to the table body
            tbody.append(...rows);

            // Update the state for the column
            sortStates[columnIndex] = nextState;

            // Update the button icon and reset other buttons
            updateButtonIcons(button, nextState, columnIndex);
        }

        function getNextState(currentState) {
            switch (currentState) {
                case 'none':
                    return 'asc';
                case 'asc':
                    return 'desc';
                case 'desc':
                    return 'none';
            }
        }

        function compareCells(cellA, cellB, columnIndex) {
            if (columnIndex === 0 || columnIndex === 7) { // For "No." and "Qty." columns
                return parseInt(cellA.textContent) - parseInt(cellB.textContent);
            } else if (columnIndex === 1) { // For "Full Name" column
                return cellA.querySelector('a').textContent.localeCompare(cellB.querySelector('a').textContent);
            } else {
                return cellA.textContent.localeCompare(cellB.textContent);
            }
        }


        function updateButtonIcons(clickedButton, state, columnIndex) {
            const allButtons = document.querySelectorAll('th button');
            allButtons.forEach((button, index) => {
                const icon = button.querySelector('i');
                if (button === clickedButton) {
                    if (state === 'asc') {
                        icon.className = 'fa-solid fa-sort-up';
                    } else if (state === 'desc') {
                        icon.className = 'fa-solid fa-sort-down';
                    } else {
                        icon.className = 'fa-solid fa-sort';
                    }
                } else {
                    icon.className = 'fa-solid fa-sort';
                    sortStates[index] = 'none'; // Reset the sort state for other columns
                }
            });
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>