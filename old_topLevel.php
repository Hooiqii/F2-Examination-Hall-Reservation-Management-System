<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if user is an admin or user to load appropriate navbar
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

include('topLevel_update.php');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/level.css">
    <title>Top Level | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <!-- Load appropriate navbar based on user type -->
    <?php
    if ($isAdmin) {
        require_once('navbar_admin.php');
    } else if ($isUser) {
        require_once('navbar_user.php');
    }
    ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Top Level Calendar</h1>
        <div class="border-wrapper">
            <div class="calendar">
                <div class="calendar-function">
                    <h2 class="calendar-title"></h2>
                    <div class="calendar-arrow">
                        <button id="today-button">Today</button>
                        <i class="fa-solid fa-angle-left" id="prev-month"></i>
                        <i class="fa-solid fa-angle-right" id="next-month"></i>
                    </div>
                </div>
                <div class="calendar-header">
                    <div class="calendar-day">Sunday</div>
                    <div class="calendar-day">Monday</div>
                    <div class="calendar-day">Tuesday</div>
                    <div class="calendar-day">Wednesday</div>
                    <div class="calendar-day">Thursday</div>
                    <div class="calendar-day">Friday</div>
                    <div class="calendar-day">Saturday</div>
                </div>
                <div class="calendar-body" id="calendar-body">
                    <!-- JavaScript will populate this section -->
                </div>
            </div>
            <div class="legend">
                <div class="legend-item">
                    <strong>
                        <p>Legend:</p>
                    </strong>
                </div>
                <div class="legend-item">
                    <div class="color-box white-box"></div>
                    <span>Unoccupied</span>
                </div>
                <div class="legend-item">
                    <div class="color-box green-box"></div>
                    <span>Occupied But Not Fully-Booked</span>
                </div>
                <div class="legend-item">
                    <div class="color-box red-box"></div>
                    <span>Fully-Booked For Every Hour</span>
                </div>
            </div>
            <div class="instruction">
                <strong>
                    <p>Instruction:</p>
                </strong>
                <p class="no-pd-bt">1. Please select 'View' for additional details.</p>
            </div>
        </div>
    </div>
    <?php require_once('footer.php'); ?>
</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const calendarBody = document.getElementById("calendar-body");
        const prevMonthBtn = document.getElementById("prev-month");
        const nextMonthBtn = document.getElementById("next-month");
        const todayBtn = document.getElementById("today-button");

        let currentYear, currentMonth;
        //let remainingCapacityByDate = ?php echo json_encode($remainingCapacityByDate); ?>;
        let remainingCapacityByDate = <?php echo json_encode($_SESSION['remaining_capacity']); ?>;
        let bookedCapacityByDate = <?php echo json_encode($_SESSION['booked_capacity']); ?>;

        const urlParams = new URLSearchParams(window.location.search);
        // Get the value of the 'nextYear' parameter from the URL
        const Year = parseInt(urlParams.get('year')) || new Date().getFullYear();
        // Get the value of the 'nextMonth' parameter from the URL
        const Month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;

        // Call the renderCalendar function with the retrieved parameters
        renderCalendar(Year, Month);

        function renderCalendar(year, month) {
            calendarBody.innerHTML = ""; // Clear previous content

            const today = new Date();
            currentYear = year;
            currentMonth = month;

            // Pass month and year parameters in the URL
            console.log(`CurrentYear: ${currentYear}, Month: ${currentMonth}`);
            window.history.replaceState({}, '', `?year=${currentYear}&month=${currentMonth}`);

            const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1); // Adjust month indexing
            const lastDayOfMonth = new Date(currentYear, currentMonth, 0); // Adjust month indexing
            const daysInMonth = lastDayOfMonth.getDate();
            const firstDayOfWeek = firstDayOfMonth.getDay(); // 0 for Sunday, 1 for Monday, etc.

            // Calculate the date of the first cell in the calendar
            const firstDateDisplayed = new Date(currentYear, currentMonth - 1, 1 - firstDayOfWeek); // Adjust month indexing

            // Calculate the date of the last cell in the calendar
            const lastDateDisplayed = new Date(currentYear, currentMonth - 1, daysInMonth + (6 - lastDayOfMonth.getDay())); // Adjust month indexing

            for (let currentDate = new Date(firstDateDisplayed); currentDate <= lastDateDisplayed; currentDate.setDate(currentDate.getDate() + 1)) {
                const date = document.createElement("div");
                date.classList.add("calendar-date");

                const day = currentDate.getDate();
                const monthDisplayed = currentDate.getMonth() + 1; // Adjust month indexing

                // Check if the date is in the current month, previous month, or next month
                if (monthDisplayed < currentMonth) {
                    date.classList.add("disabled");
                    date.innerHTML = '<span class="other-month">' + day + '</span>'; // Previous month
                } else if (monthDisplayed > currentMonth) {
                    date.classList.add("disabled");
                    date.innerHTML = '<span class="other-month">' + day + '</span>'; // Next month
                } else {
                    date.innerHTML = '<span>' + day + '</span>'; // Current month
                    const viewButton = document.createElement("button");
                    viewButton.textContent = "View";
                    viewButton.classList.add("view-button");
                    viewButton.addEventListener("click", function() {
                        handleViewButtonClick(day, currentYear, currentMonth);
                    });

                    // Check if remaining capacity is 0 for that date
                    if (remainingCapacityByDate[day] === 0) {
                        viewButton.disabled = true;
                        viewButton.textContent = "Full";
                        viewButton.style.backgroundColor = "#dc3545";
                        viewButton.style.cursor = "not-allowed";
                        // Add class for fully booked
                        date.style.backgroundColor = "#f7e1e3";
                    }

                    if (bookedCapacityByDate[day] >= 1 && remainingCapacityByDate[day] != 0) {
                        date.style.backgroundColor = "#e4f5e7"; // Occupied but not fully booked
                    }

                    date.appendChild(viewButton);

                    // Highlight today's date
                    highlightToday(date, currentDate, today);
                }

                calendarBody.appendChild(date);

            }

            // Display current month and year
            const monthYearString = new Date(currentYear, currentMonth - 1).toLocaleDateString('en-US', { // Adjust month indexing
                month: 'long',
                year: 'numeric'
            });
            document.querySelector('.calendar-title').textContent = monthYearString;

            // Disable view buttons for dates with zero remaining capacity
            disableViewButtonsForZeroCapacity();
        }

        // Function to highlight today's date
        function highlightToday(dateElement, currentDate, today) {
            if (
                currentDate.getFullYear() === today.getFullYear() &&
                currentDate.getMonth() === today.getMonth() &&
                currentDate.getDate() === today.getDate()
            ) {
                dateElement.classList.add("current-date");
            }
        }

        // Function to disable view buttons for dates with zero remaining capacity
        function disableViewButtonsForZeroCapacity() {
            const viewButtons = document.querySelectorAll('.view-button');
            viewButtons.forEach(button => {
                const day = parseInt(button.parentElement.textContent, 10);
                if (remainingCapacityByDate[day] === 0) {
                    button.disabled = true;
                } else {
                    button.disabled = false;
                }
            });
        }

        // Event listeners for navigation buttons
        prevMonthBtn.addEventListener('click', function() {
            const prevMonth = currentMonth === 1 ? 12 : currentMonth - 1;
            const prevYear = currentMonth === 1 ? currentYear - 1 : currentYear;
            currentMonth = prevMonth; // Update currentMonth variable
            currentYear = prevYear; // Update currentYear variable
            window.location.replace(`topLevel.php?year=${currentYear}&month=${currentMonth}`);
        });

        nextMonthBtn.addEventListener('click', function() {
            const nextMonth = currentMonth === 12 ? 1 : currentMonth + 1;
            const nextYear = currentMonth === 12 ? currentYear + 1 : currentYear;
            currentMonth = nextMonth; // Update currentMonth variable
            currentYear = nextYear; // Update currentYear variable
            window.location.replace(`topLevel.php?year=${currentYear}&month=${currentMonth}`);
        });

        todayBtn.addEventListener('click', function() { // Add Today button functionality
            const today = new Date();
            const todayYear = today.getFullYear();
            const todayMonth = today.getMonth() + 1;
            currentMonth = todayMonth;
            currentYear = todayYear;
            window.location.replace(`topLevel.php?year=${currentYear}&month=${currentMonth}`);
        });

        // Event listener to capture the selected date
        function handleViewButtonClick(day, year, month) {
            const formattedDate = `${year}-${month}-${day}`;
            const url = `calendarDayTopLevel.php?selectedDate=${formattedDate}`;
            window.location.href = url;
        }
    });
</script>

</html>