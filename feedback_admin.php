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

// Define the number of results per page
$results_per_page = 20;

// Get the search query if it exists
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Modify the SQL query to include the search filter if a search query is present
if (!empty($search_query)) {
    $search_query_sql = '%' . $conn->real_escape_string($search_query) . '%';
    $sql = "SELECT COUNT(*) AS total FROM feedback f
    INNER JOIN users u ON f.user_id = u.user_id
    WHERE u.user_username LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $search_query_sql);
} else {
    $sql = "SELECT COUNT(*) AS total FROM feedback";
    $stmt = $conn->prepare($sql);
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
    $sql = "SELECT f.*, u.user_username 
            FROM feedback f
            INNER JOIN users u ON f.user_id = u.user_id 
            WHERE u.user_username LIKE ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $search_query_sql, $start_from, $results_per_page);
} else {
    $sql = "SELECT f.*, u.user_username 
            FROM feedback f
            INNER JOIN users u ON f.user_id = u.user_id 
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $start_from, $results_per_page);
}

$stmt->execute();
$feedback_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/feedback_admin.css">
    <title>Feedback | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Feedback</h1>
        <div class="border-wrapper">
            <div class="function">
                <form method="GET" action="feedback_admin.php">
                    <input type="text" id="searchBar" name="search" placeholder="Search by User..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" id="searchButton">Search</button>
                </form>
                <!-- <input type="text" id="searchBar" placeholder="Search User..."> -->
                <div class="function-button">
                    <a href="feedback_admin_excel.php"><button id="excelButton">Excel</button></a>
                    <a href="feedback_graph.php"><button id="graphButton">Graph View</button></a>
                </div>
            </div>
            <div class="table-list">
                <table id="eventTable">
                    <thead>
                    <?php if (!isset($_GET['search']) || (isset($_GET['search']) && $_GET['search'] == '')): ?>
                            <th><button class="sortButton" onclick="sortTable(0,this)"><i class="fa-solid fa-sort"></i>&nbsp;No.</button></th>
                            <th><button class="sortButton" onclick="sortTable(1,this)"><i class="fa-solid fa-sort"></i>&nbsp;Satisfaction</button></th>
                            <th><button class="sortButton" onclick="sortTable(2,this)"><i class="fa-solid fa-sort"></i>&nbsp;Ease Of Use</button></th>
                            <th><button class="sortButton" onclick="sortTable(3,this)"><i class="fa-solid fa-sort"></i>&nbsp;Functionality</button></th>
                            <th><button class="sortButton" onclick="sortTable(4,this)"><i class="fa-solid fa-sort"></i>&nbsp;Feature Suggestion</button></th>
                            <th><button class="sortButton" onclick="sortTable(5,this)"><i class="fa-solid fa-sort"></i>&nbsp;Performance</button></th>
                            <th><button class="sortButton" onclick="sortTable(6,this)"><i class="fa-solid fa-sort"></i>&nbsp;Additional Comment</button></th>
                            <th><button class="sortButton" onclick="sortTable(7,this)"><i class="fa-solid fa-sort"></i>&nbsp;User</button></th>
                        <?php else : ?>
                            <th>No.</th>
                            <th>Satisfaction</th>
                            <th>Ease Of Use</th>
                            <th>Functionality</th>
                            <th>Feature Suggestion</th>
                            <th>Performance</th>
                            <th>Additional Comment</th>
                            <th>User</th>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php
                        $counter = $start_from + 1;  // Initialize the counter with the start record number
                        if ($feedback_result->num_rows > 0) {
                            while ($row = $feedback_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter . "</td>";
                                echo "<td>" . $row["satisfaction"] . "</td>";
                                echo "<td>" . $row["easeOfUse"] . "</td>";
                                echo "<td>" . $row["functionality"] . "</td>";
                                echo "<td>" . ($row["feature"] != '' ? $row["feature"] : '-') . "</td>";
                                echo "<td>" . $row["performance"] . "</td>";
                                echo "<td>" . ($row["comment"] != '' ? $row["comment"] : '-') . "</td>";
                                echo "<td>" . $row["user_username"] . "</td>";
                                echo "</tr>";
                                $counter++;
                            }
                        } else {
                            echo "<tr><td colspan='8'>No feedback found</td></tr>";
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
                        echo "<a href='feedback_admin.php?page=1&search=" . $encoded_search . "'>&lt;&lt;</a> ";
                    }
                    if ($page > 1) {
                        echo "<a href='feedback_admin.php?page=" . ($page - 1) . "&search=" . $encoded_search . "'>&lt;</a> ";
                    }
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<a class='active' href='feedback_admin.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        } else {
                            echo "<a href='feedback_admin.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        }
                    }
                    if ($page < $total_pages) {
                        echo "<a href='feedback_admin.php?page=" . ($page + 1) . "&search=" . $encoded_search . "'>&gt;</a> ";
                    }
                    if ($page < $total_pages) {
                        echo "<a href='feedback_admin.php?page=" . $total_pages . "&search=" . $encoded_search . "'>&gt;&gt;</a>";
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
            if (columnIndex === 0 || columnIndex === 1 || columnIndex === 2 || columnIndex === 3 || columnIndex === 5) { // For "No." and "Qty." columns
                return parseInt(cellA.innerHTML) - parseInt(cellB.innerHTML);
            } else {
                return cellA.innerHTML.localeCompare(cellB.innerHTML);
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
