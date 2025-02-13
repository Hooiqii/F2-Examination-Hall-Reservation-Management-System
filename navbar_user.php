<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: Cambria, Georgia, serif;
        }

        .navbar {
            width: 240px;
            position: fixed;
            bottom: 0;
            top: 0;
            left: 0;
            background-color: #333;
            overflow-x: hidden;
            padding-top: 10px;
            color: #fff;
        }

        #uthmLogo {
            display: block;
            margin: 0 10px 20px 20px;
            width: 120px;
        }

        .role {
            padding: 10px 20px;
            text-align: left;
            border-bottom: 1px solid #555;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: lighter;
        }

        .navbar a {
            padding: 12px 15px;
            text-decoration: none;
            font-size: 16px;
            color: #ccc;
            display: flex;
            align-items: center;
            justify-content: start;
            transition: 0.3s;
        }

        .navbar a i {
            margin-right: 10px;
        }

        .navbar a:hover,
        .dropdown-header:hover {
            background-color: #555;
            color: #fff;
        }

        .sub-menu {
            display: none;
        }

        .sub-menu.active {
            display: block;
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            transition: 0.3s;
            color: #ccc;
        }

        .dropdown-header i {
            margin-right: 10px;
            width: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dropdown-header i.fa-sort-down {
            margin-left: 5px;
        }

        .dropdown-header:hover {
            cursor: pointer;
        }

        /* For main navigation link */
        .navbar a.active {
            background-color: #6F2F27;
            color: #FFFFFF;
        }

        /* For dropdown header when clicked */
        .dropdown-header.active {
            background-color: #6F2F27;
            color: #FFFFFF;
        }

        /* For submenu */
        .sub-menu a {
            background-color: #E7DDD1;
            color: black;
            /* Initial text color */
            display: block;
            /* Adjusting for better visual effect */
            padding: 10px 15px;
            /* Adjusting for better visual effect */
        }

        .sub-menu a.active {
            background-color: #C88879;
            color: white;
            /* Active text color */
        }
    </style>

<script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropdownHeaders = document.querySelectorAll('.dropdown-header');
            const navbarLinks = document.querySelectorAll('.navbar a');

            // Handling dropdown headers
            dropdownHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    dropdownHeaders.forEach(h => {
                        if (h !== this) {
                            h.classList.remove('active');
                            h.nextElementSibling.classList.remove('active');
                        }
                    });

                    this.classList.toggle('active');
                    const submenu = this.nextElementSibling;
                    submenu.classList.toggle('active');

                    navbarLinks.forEach(link => {
                        if (link.classList.contains('active') && !link.closest('.sub-menu')) {
                            link.classList.remove('active');
                        }
                    });
                });
            });

            // Handling main navigation links
            navbarLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    navbarLinks.forEach(l => l.classList.remove('active'));

                    this.classList.add('active');

                    if (!this.closest('.sub-menu')) {
                        dropdownHeaders.forEach(h => {
                            h.classList.remove('active');
                            h.nextElementSibling.classList.remove('active');
                        });
                    }
                });
            });

            // Function to activate submenu based on the current page
            function activateSubmenu(activeSubmenuClass) {
                const activeDropdownHeader = document.querySelector('.' + activeSubmenuClass);
                const activeSubmenuContent = activeDropdownHeader.nextElementSibling;

                if (activeDropdownHeader && activeSubmenuContent) {
                    activeDropdownHeader.classList.add('active');
                    activeSubmenuContent.classList.add('active');
                }
            }

            // Call the function with the specific submenu class name based on your page
            <?php
            if ($current_page == 'newBooking.php' || $current_page == 'bookingList.php' || $current_page == 'bookingDetails.php') {
                echo "activateSubmenu('reservation_submenu');";
            }
            // Add more conditions as needed for other pages and submenus
            else if ($current_page == 'topLevel.php' || $current_page == 'lowerLevel.php' || $current_page == 'calendarDayTopLevel.php' || $current_page == 'calendarDayLowerLevel.php') {
                echo "activateSubmenu('f2examination_submenu');"; 
            }
            ?>
        });
    </script>
</head>

<body>

    <div class="navbar">
        <img src="images/header_uthmLogo.png" alt="uthmLogo" id="uthmLogo">
        <div class="role">Role: User</div>

        <!-- Main Navigation Links -->
        <a href="index.php" <?php if ($current_page == 'index.php') echo 'class="active"'; ?>><i class="fa-solid fa-house"></i>My Dashboard</a>

        <!-- F2 Examination Hall Dropdown -->
        <div class="dropdown-header f2examination_submenu">
            <i class="fa-solid fa-school"></i>F2 Examination Hall<i class="fa-solid fa-sort-down"></i>
        </div>
        <div class="sub-menu f2examination_submenu_content">
            <a href="topLevel.php" style="padding-left: 45px;" <?php if ($current_page == 'topLevel.php' || $current_page == 'calendarDayTopLevel.php') echo 'class="active"'; ?>><i class="fa-solid fa-arrow-up-short-wide"></i>Top Level</a>
            <a href="lowerLevel.php" style="padding-left: 45px;" <?php if ($current_page == 'lowerLevel.php' || $current_page == 'calendarDayLowerLevel.php') echo 'class="active"'; ?>><i class="fa-solid fa-arrow-down-wide-short"></i>Lower Level</a>
        </div>

        <!-- Reservation Dropdown -->
        <div class="dropdown-header reservation_submenu">
            <i class="fa-solid fa-table-list"></i>Reservation<i class="fa-solid fa-sort-down"></i>
        </div>
        <div class="sub-menu reservation_submenu_content">
            <a href="newBooking.php" style="padding-left: 45px;" <?php if ($current_page == 'newBooking.php') echo 'class="active"'; ?>><i class="fa-solid fa-plus"></i>New Booking</a>
            <a href="bookingList.php" style="padding-left: 45px;" <?php if ($current_page == 'bookingList.php') echo 'class="active"'; ?>><i class="fa-solid fa-list"></i>Booking List</a>
        </div>

        <a href="profile.php" <?php if ($current_page == 'profile.php') echo 'class="active"'; ?>><i class="fa-solid fa-user"></i>Profile</a>
        <a href="feedback_user.php" <?php if ($current_page == 'feedback_user.php') echo 'class="active"'; ?>><i class="fa-solid fa-comments"></i>Feedback</a>
        <a href="userManual_user.php" <?php if ($current_page == 'userManual_user.php') echo 'class="active"'; ?>><i class="fa-solid fa-circle-question"></i>User Manual</a>
        <a href="contactUs.php" <?php if ($current_page == 'contactUs.php') echo 'class="active"'; ?>><i class="fa-solid fa-phone-flip"></i></i>Contact Us</a>
    </div>

</body>

</html>