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

// Check if an user_id and action (activate/deactivate) are provided via GET request
if (isset($_GET['id'], $_GET['action'])) {
    $userId = intval($_GET['id']); // Sanitize the user_id as an integer
    $action = $_GET['action']; // Get the action (activate/deactivate)

    // Prepare and execute SQL query to update user_isActive based on action
    $sql = "";
    if ($action === 'activate') {
        $sql = "UPDATE users SET user_isActive = TRUE WHERE user_id = ?";
    } elseif ($action === 'deactivate') {
        $sql = "UPDATE users SET user_isActive = FALSE WHERE user_id = ?";
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);

        if ($stmt->execute()) {
            // Redirect back to user.php after activation/deactivation
            header("Location: user.php");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }

        $stmt->close();
    }
}


// Define the number of results per page
$results_per_page = 20;

// Get the search query if it exists
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Modify the SQL query to include the search filter if a search query is present
if (!empty($search_query)) {
    $search_query_sql = '%' . $conn->real_escape_string($search_query) . '%';
    $sql = "SELECT COUNT(*) AS total FROM users WHERE user_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $search_query_sql);
} else {
    $sql = "SELECT COUNT(*) AS total FROM users";
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

// Fetch users with limit for pagination
if (!empty($search_query)) {
    $sql = "SELECT * FROM users WHERE user_name LIKE ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $search_query_sql, $start_from, $results_per_page);
} else {
    $sql = "SELECT * FROM users LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $start_from, $results_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/user.css">
    <title>User | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>User</h1>
        <div class="border-wrapper">
            <div class="function">
                <form method="GET" action="user.php">
                    <input type="text" id="searchBar" name="search" placeholder="Search by Full Name..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" id="searchButton">Search</button>
                </form>
                <a href="newUser.php"><button id="addButton">Add</button></a>
            </div>
            <div class="table-list">
                <table id="eventTable">
                    <thead>
                        <tr>
                        <?php if (!isset($_GET['search']) || (isset($_GET['search']) && $_GET['search'] == '')): ?>
                                <th><button class="sortButton" onclick="sortTable(0, this)"><i class="fa-solid fa-sort"></i>&nbsp;No.</button></th>
                                <th><button class="sortButton" onclick="sortTable(1, this)"><i class="fa-solid fa-sort"></i>&nbsp;Full Name</button></th>
                                <th><button class="sortButton" onclick="sortTable(2, this)"><i class="fa-solid fa-sort"></i>&nbsp;Username</button></th>
                                <th><button class="sortButton" onclick="sortTable(3, this)"><i class="fa-solid fa-sort"></i>&nbsp;Contact Number</button></th>
                                <th><button class="sortButton" onclick="sortTable(4, this)"><i class="fa-solid fa-sort"></i>&nbsp;Email</button></th>
                                <th><button class="sortButton" onclick="sortTable(5, this)"><i class="fa-solid fa-sort"></i>&nbsp;Status</button></th>
                            <?php else: ?>
                                <th>No.</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Contact Number</th>
                                <th>Email</th>
                                <th>Status</th>
                            <?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                       $counter = $start_from + 1;  // Initialize the counter with the start record number
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter . "</td>";

                                // Make the full name a clickable link to userDetails.php
                                echo "<td><a href='userDetails.php?id=" . $row['user_id'] . "'>" . $row["user_name"] . "</a></td>";
                                echo "<td>" . $row["user_username"] . "</td>";
                                echo "<td>" . $row["user_contact"] . "</td>";
                                echo "<td>" . $row["user_email"] . "</td>";

                                // Check the value of user_isActive and set Status accordingly
                                $statusText = $row["user_isActive"] ? 'Active' : 'Inactive';
                                echo "<td>" . $statusText . "</td>";

                                // Action buttons for Activate and Deactivate
                                echo "<td class='action-btn'>";

                                // Conditional buttons based on user_isActive status
                                if ($row["user_isActive"]) {
                                    echo "<a href='user.php?id=" . $row['user_id'] . "&action=deactivate' onclick=\"return confirm('Are you sure you want to deactivate this user?');\"><button class='deactivate'>Deactivate</button></a>";
                                } else {
                                    echo "<a href='user.php?id=" . $row['user_id'] . "&action=activate' onclick=\"return confirm('Are you sure you want to activate this user?');\"><button class='activate'>Activate</button></a>";
                                }

                                echo "</td>";
                                echo "</tr>";
                                $counter++;  // Increment the counter
                            }
                        } else {
                            echo "<tr><td colspan='7'>No accounts found</td></tr>";  // Display message if no users found
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
                        echo "<a href='user.php?page=1&search=" . $encoded_search . "'>&lt;&lt;</a> ";
                    }
                    if ($page > 1) {
                        echo "<a href='user.php?page=" . ($page - 1) . "&search=" . $encoded_search . "'>&lt;</a> ";
                    }
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<a class='active' href='user.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        } else {
                            echo "<a href='user.php?page=" . $i . "&search=" . $encoded_search . "'>" . $i . "</a> ";
                        }
                    }
                    if ($page < $total_pages) {
                        echo "<a href='user.php?page=" . ($page + 1) . "&search=" . $encoded_search . "'>&gt;</a> ";
                    }
                    if ($page < $total_pages) {
                        echo "<a href='user.php?page=" . $total_pages . "&search=" . $encoded_search . "'>&gt;&gt;</a>";
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
            if (columnIndex === 0) { // For "No." column
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
// Close the database connection
$conn->close();
?>