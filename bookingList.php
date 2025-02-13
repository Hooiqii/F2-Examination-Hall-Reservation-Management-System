<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

$user_id = $_SESSION['user_id'];

// Handle cancellation request
if (isset($_POST['cancel_booking'])) {
    $bk_id = $_POST['bk_id'];

    // Check if the booking status is "Pending"
    $sql = "SELECT bk_status FROM booking WHERE bk_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $bk_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && $row['bk_status'] === 'Pending') {
        // Update the booking status to "Cancel"
        $update_sql = "UPDATE booking SET bk_status = 'Cancelled' WHERE bk_id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $bk_id, $user_id);
        $update_stmt->execute();
    }
}

$results_per_page = 20;

// Get the search query if it exists
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Modify the SQL query to include the search filter if a search query is present
if (!empty($search_query)) {
    $search_query_sql = '%' . $conn->real_escape_string($search_query) . '%';
    $sql = "SELECT COUNT(*) AS total FROM booking WHERE user_id = ?
    AND bk_purpose LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $search_query_sql);
} else {
    $sql = "SELECT COUNT(*) AS total FROM booking WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Determine the current page and calculate the start record for the current page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $results_per_page;

// Fetch admins with limit for pagination
if (!empty($search_query)) {
    $sql = "SELECT * FROM booking WHERE user_id = ? AND bk_purpose LIKE ? ORDER BY bk_dateTime DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isii', $user_id, $search_query_sql, $start_from, $results_per_page);
} else {
    $sql = "SELECT * FROM booking WHERE user_id = ? 
            ORDER BY bk_dateTime DESC
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $user_id, $start_from, $results_per_page);
}

$stmt->execute();
$result2 = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/bookingList.css">
    <title>Booking List | F2 Examination Hall</title>
    <style>
        .cancel-button:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
    </style>
    <script>
        function confirmCancel(event) {
            if (!confirm("Are you sure to cancel this booking?")) {
                event.preventDefault();
            }
        }
    </script>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Booking List</h1>
        <div class="border-wrapper">
            <div class="function">
                <form method="GET" action="bookingList.php">
                    <input type="text" id="searchBar" name="search" placeholder="Search Event..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" id="searchButton">Search</button>
                </form>
                <!-- <input type="text" id="searchBar" placeholder="Search Event..."> -->
                <a href="newBooking.php"><button id="addButton">Add</button></a>
            </div>
            <div class="table-list">
                <table id="eventTable">
                    <thead>
                        <tr>
                        <?php if (!isset($_GET['search']) || (isset($_GET['search']) && $_GET['search'] == '')): ?>
                                <th><button class="sortButton" onclick="sortTable(0, this)"><i class="fa-solid fa-sort"></i>&nbsp;No.</button></th>
                                <th><button class="sortButton" onclick="sortTable(1, this)"><i class="fa-solid fa-sort"></i>&nbsp;Event</button></th>
                                <th><button class="sortButton" onclick="sortTable(2, this)"><i class="fa-solid fa-sort"></i>&nbsp;Level</button></th>
                                <th><button class="sortButton" onclick="sortTable(3, this)"><i class="fa-solid fa-sort"></i>&nbsp;Start Date</button></th>
                                <th><button class="sortButton" onclick="sortTable(4, this)"><i class="fa-solid fa-sort"></i>&nbsp;End Date</button></th>
                                <th><button class="sortButton" onclick="sortTable(5, this)"><i class="fa-solid fa-sort"></i>&nbsp;Start Time</button></th>
                                <th><button class="sortButton" onclick="sortTable(6, this)"><i class="fa-solid fa-sort"></i>&nbsp;End Time</button></th>
                                <th><button class="sortButton" onclick="sortTable(7, this)"><i class="fa-solid fa-sort"></i>&nbsp;Participants</button></th>
                                <th><button class="sortButton" onclick="sortTable(8, this)"><i class="fa-solid fa-sort"></i>&nbsp;Status</button></th>
                            <?php else : ?>
                                <th>No.</th>
                                <th>Event</th>
                                <th>Level</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Participants</th>
                                <th>Status</th>
                            <?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = $start_from + 1;  // Initialize the counter with the start record number
                        if ($result2->num_rows > 0) {
                            while ($row = $result2->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter . "</td>";
                                // Wrap the event details in an anchor tag linking to bookingDetails.php with booking ID
                                echo "<td class='event-name'><a href='bookingDetails.php?id=" . $row['bk_id'] . "'>" . $row["bk_purpose"] . "</a></td>";
                                echo "<td>" . $row["bk_floorSelection"] . "</td>";
                                echo "<td>" . $row["bk_startDate"] . "</td>";
                                echo "<td>" . $row["bk_endDate"] . "</td>";
                                echo "<td>" . $row["bk_startTime"] . "</td>";
                                echo "<td>" . $row["bk_endTime"] . "</td>";
                                echo "<td>" . $row["bk_participantNo"] . "</td>";

                                // Check the value of bk_status and set color accordingly
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
                                    case 'Cancelled':  // Add this case for the 'Cancel' status
                                        $statusColor = '#FFF4D3';
                                        break;
                                    default:
                                        $statusColor = 'white';  // Default color if none of the above
                                        break;
                                }
                                echo "<td style='background-color: $statusColor;'>" . $row["bk_status"] . "</td>";  // Set the color

                                // Add the Cancel button if the status is Pending
                                echo "<td class='action-btn'>";
                                if ($row["bk_status"] === 'Pending') {
                                    echo "<form method='POST' action='' onsubmit='confirmCancel(event)'>";
                                    echo "<input type='hidden' name='bk_id' value='" . $row['bk_id'] . "'>";
                                    echo "<button type='submit' name='cancel_booking' class='cancel-button'>Cancel</button>";
                                    echo "</form>";
                                } else {
                                    echo "<button class='cancel-button' disabled>Cancel</button>";
                                }
                                echo "</td>";

                                echo "</tr>";
                                $counter++;  // Increment the counter
                            }
                        } else {
                            echo "<tr><td colspan='10'>No bookings found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <?php
                if ($total_pages > 1) {
                    $encoded_search = urlencode($search_query);
                    if ($page > 1) {
                        echo "<a href='bookingList.php?page=1&search=" . $encoded_search . "'>&lt;&lt;</a> ";
                    }
                    if ($page > 1) {
                        echo "<a href='bookingList.php?page=" . ($page - 1) . "&search=" . $encoded_search . "'>&lt;</a> ";
                    }
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<a class='active' href='bookingList.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        } else {
                            echo "<a href='bookingList.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        }
                    }
                    if ($page < $total_pages) {
                        echo "<a href='bookingList.php?page=" . ($page + 1) . "&search=" . $encoded_search . "'>&gt;</a> ";
                    }
                    if ($page < $total_pages) {
                        echo "<a href='bookingList.php?page=" . $total_pages . "&search=" . $encoded_search . "'>&gt;&gt;</a>";
                    }
                }
                ?>

            </div>

        </div>
    </div>
    <?php require_once('footer.php'); ?>
    <script>
        let sortStates = {};

        function sortTable(columnIndex, button) {
            const table = document.getElementById('eventTable');
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.rows);

            if (!sortStates[columnIndex]) {
                sortStates[columnIndex] = 'none'; // Possible states: 'none', 'asc', 'desc'
            }

            const nextState = getNextState(sortStates[columnIndex]);

            if (nextState === 'asc') {
                rows.sort((rowA, rowB) => compareCells(rowA.cells[columnIndex], rowB.cells[columnIndex], columnIndex));
            } else if (nextState === 'desc') {
                rows.sort((rowA, rowB) => compareCells(rowB.cells[columnIndex], rowA.cells[columnIndex], columnIndex));
            }

            if (nextState === 'none') {
                rows.sort((rowA, rowB) => compareCells(rowA.cells[0], rowB.cells[0], 0));
            }

            tbody.append(...rows);

            sortStates[columnIndex] = nextState;

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
            if (columnIndex === 0 || columnIndex === 7) {
                return parseInt(cellA.textContent) - parseInt(cellB.textContent);
            } else if (columnIndex === 1) { // For the "Event" column
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
// Close the database connection
$conn->close();
?>