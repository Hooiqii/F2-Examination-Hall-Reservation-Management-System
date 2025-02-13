<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if admin or user is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is an admin or user to load appropriate navbar
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Fetch hall_capacity for F2 Top Level
$sqlTop = "SELECT hall_capacity FROM hall WHERE hall_floor = 'top'";
$resultTop = mysqli_query($conn, $sqlTop);
$rowTop = mysqli_fetch_assoc($resultTop);
$capacityTop = $rowTop['hall_capacity'] ?? 'N/A'; // Default value if no result is found

// Fetch hall_capacity for F2 Lower Level
$sqlLow = "SELECT hall_capacity FROM hall WHERE hall_floor = 'low'";
$resultLow = mysqli_query($conn, $sqlLow);
$rowLow = mysqli_fetch_assoc($resultLow);
$capacityLow = $rowLow['hall_capacity'] ?? 'N/A'; // Default value if no result is found

// Set the timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDate = date('Y-m-d');

// Fetch total and active admin counts
$sqlTotalAdmin = "SELECT COUNT(*) AS total FROM admin";
$resultTotalAdmin = mysqli_query($conn, $sqlTotalAdmin);
$rowTotalAdmin = mysqli_fetch_assoc($resultTotalAdmin);
$totalAdmin = htmlspecialchars($rowTotalAdmin['total']);

$sqlActiveAdmin = "SELECT COUNT(*) AS active FROM admin WHERE admin_isActive = 1";
$resultActiveAdmin = mysqli_query($conn, $sqlActiveAdmin);
$rowActiveAdmin = mysqli_fetch_assoc($resultActiveAdmin);
$activeAdmin = htmlspecialchars($rowActiveAdmin['active']);

// Fetch total and active user counts
$sqlTotalUser = "SELECT COUNT(*) AS total FROM users";
$resultTotalUser = mysqli_query($conn, $sqlTotalUser);
$rowTotalUser = mysqli_fetch_assoc($resultTotalUser);
$totalUser = htmlspecialchars($rowTotalUser['total']);

$sqlActiveUser = "SELECT COUNT(*) AS active FROM users WHERE user_isActive = 1";
$resultActiveUser = mysqli_query($conn, $sqlActiveUser);
$rowActiveUser = mysqli_fetch_assoc($resultActiveUser);
$activeUser = htmlspecialchars($rowActiveUser['active']);

?>


<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/index.css">
    <title>Dashboard | F2 Examination Hall</title>
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
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <h1>My Dashboard</h1>
        <div class="quickAccess">
            <div class="row">
                <div class="column">
                    <h3>F2 Top Level</h3>
                    <!-- the number will be retrieved from the database later -->
                    <p id="top">Maximum Capacity: <?php echo $capacityTop; ?></p>
                    <hr>
                    <!-- Change link text and URL based on user type -->
                    <?php if ($isAdmin) : ?>
                        <a href="setting.php">Manage Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    <?php elseif ($isUser) : ?>
                        <a href="newBooking.php">Book Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <h3>F2 Lower Level</h3>
                    <!-- the number will be retrieved from the database later -->
                    <p id="low">Maximum Capacity: <?php echo $capacityLow; ?></p>
                    <hr>
                    <!-- Change link text and URL based on user type -->
                    <?php if ($isAdmin) : ?>
                        <a href="setting.php">Manage Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    <?php elseif ($isUser) : ?>
                        <a href="newBooking.php">Book Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <br>
            <!-- Display only for admin login -->
            <?php if ($isAdmin) : ?>
                <div class="row">
                    <div class="column">
                        <h3>Administrator Account</h3>
                        <p>Active: <?php echo $activeAdmin; ?> / <?php echo $totalAdmin; ?></p>
                        <hr>
                        <a href="admin.php">Manage Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    </div>
                    <div class="column">
                        <h3>User Account</h3>
                        <p>Active: <?php echo $activeUser; ?> / <?php echo $totalUser; ?></p>
                        <hr>
                        <a href="user.php">Manage Now<i class="fa-solid fa-circle-arrow-right"></i></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <div class="image-slideshow">
            <div class="slideshow-container">
                <img id="slideshowImg" src="images/banner1.png" alt="Slideshow Image">
                <div class="slideshow-indicators"></div>
            </div>
        </div>


    </div>
    <!-- End Content Wrapper -->


    <?php require_once('footer.php'); ?>
    <script>
        function updateRemainingCapacity(floor, startDate, timeSlot, index) {
            $.post("getRemainingCapacity.php", {
                floorSelection: floor,
                startDate: startDate,
                timeSlot: timeSlot
            }, function(data) {
                if (floor === 'top') {
                    document.getElementById(`topRemaining${index}`).textContent = data;
                } else if (floor === 'low') {
                    document.getElementById(`lowRemaining${index}`).textContent = data;
                }
            });
        }

        var images = ['banner1.png', 'banner2.png', 'banner3.png', 'banner4.png'];
        var currentIndex = 0;
        var slideshowImg = document.getElementById('slideshowImg');
        var slideshowIndicators = document.querySelector('.slideshow-indicators');
        var intervalId; // Store interval id to clear it later


        function startSlideshow() {
            if (intervalId) {
                clearInterval(intervalId); // Clear previous interval if exists
            }


            intervalId = setInterval(function() {
                navigateToSlide((currentIndex + 1) % images.length); // Auto-transition to the next slide
            }, 3000);


            // Update indicators
            slideshowIndicators.innerHTML = ''; // Clear existing indicators
            for (let i = 0; i < images.length; i++) {
                slideshowIndicators.innerHTML += '<div class="slideshow-indicator" onclick="navigateToSlide(' + i + ')"></div>';
            }


            // Highlight active indicator
            var indicators = document.querySelectorAll('.slideshow-indicator');
            indicators[currentIndex].classList.add('active');
        }


        function navigateToSlide(index) {
            clearInterval(intervalId); // Clear interval to prevent auto-transitioning
            currentIndex = index;
            slideshowImg.src = 'images/' + images[currentIndex];


            // Remove active class from all indicators
            var indicators = document.querySelectorAll('.slideshow-indicator');
            indicators.forEach(indicator => indicator.classList.remove('active'));


            // Add active class to clicked indicator
            indicators[currentIndex].classList.add('active');


            // Restart the slideshow after manual navigation
            startSlideshow();
        }


        // Start the slideshow initially
        startSlideshow();
    </script>

</body>


</html>