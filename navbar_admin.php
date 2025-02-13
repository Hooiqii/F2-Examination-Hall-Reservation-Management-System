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
                    // Removed event.preventDefault(); to allow navigation to the specified URL

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
            if ($current_page == 'topLevel.php' || $current_page == 'lowerLevel.php' || $current_page == 'calendarDayTopLevel.php' || $current_page == 'calendarDayLowerLevel.php' || $current_page == 'setting.php') {
                echo "activateSubmenu('f2examination_submenu');";
            }
            // Add more conditions as needed for other pages and submenus
            else if (
                // $current_page == 'admin.php' ||
                // $current_page == 'adminDetails.php' ||
                // $current_page == 'newAdmin.php' ||
                // $current_page == 'staff.php' ||
                // $current_page == 'student.php' 

                $current_page == 'admin.php' ||
                $current_page == 'adminDetails.php' ||
                $current_page == 'newAdmin.php' ||
                $current_page == 'user.php' ||
                $current_page == 'userDetails.php' ||
                $current_page == 'newUser.php'
                ) {
                echo "activateSubmenu('user_submenu');";
            }
            ?>
        });
    </script>
</head>

<body>

    <div class="navbar">
        <img src="images/header_uthmLogo.png" alt="uthmLogo" id="uthmLogo">
        <div class="role">Role: Administrator</div>

        <!-- Main Navigation Links -->
        <a href="index.php" <?php if ($current_page == 'index.php') echo 'class="active"'; ?>><i class="fa-solid fa-house"></i>My Dashboard</a>

        <!-- F2 Examination Hall Dropdown -->
        <div class="dropdown-header f2examination_submenu">
            <i class="fa-solid fa-school"></i>F2 Examination Hall<i class="fa-solid fa-sort-down"></i>
        </div>
        <div class="sub-menu f2examination_submenu_content">
            <a href="topLevel.php" style="padding-left: 45px;" <?php if ($current_page == 'topLevel.php' || $current_page == 'calendarDayTopLevel.php') echo 'class="active"'; ?>><i class="fa-solid fa-arrow-up-short-wide"></i>Top Level</a>
            <a href="lowerLevel.php" style="padding-left: 45px;" <?php if ($current_page == 'lowerLevel.php' || $current_page == 'calendarDayLowerLevel.php') echo 'class="active"'; ?>><i class="fa-solid fa-arrow-down-wide-short"></i>Lower Level</a>
            <a href="setting.php" style="padding-left: 45px;" <?php if ($current_page == 'setting.php') echo 'class="active"'; ?>><i class="fa-solid fa-gear"></i>Settings</a>
        </div>

        <a href="reservation.php" <?php if ($current_page == 'reservation.php' || $current_page == 'reservationDetails.php') echo 'class="active"'; ?>><i class="fa-solid fa-table-list"></i>Reservation</a>


        <!-- User Dropdown -->
        <div class="dropdown-header user_submenu">
            <i class="fa-solid fa-user"></i>System User<i class="fa-solid fa-sort-down"></i>
        </div>
        <div class="sub-menu user_submenu_content">
            <a href="admin.php" style="padding-left: 45px;" <?php if ($current_page == 'admin.php'|| $current_page == 'newAdmin.php'|| $current_page == 'adminDetails.php') echo 'class="active"'; ?>><i class="fa-solid fa-user-gear"></i>Administrator</a>
            <!-- <a href="staff.php" style="padding-left: 45px;" ?php if ($current_page == 'staff.php') echo 'class="active"'; ?>><i class="fa-solid fa-chalkboard-user"></i>Staff</a>
            <a href="student.php" style="padding-left: 45px;" ?php if ($current_page == 'student.php') echo 'class="active"'; ?>><i class="fa-solid fa-user-graduate"></i>Student</a> -->
            <a href="user.php" style="padding-left: 45px;" <?php if ($current_page == 'user.php' || $current_page == 'newUser.php'|| $current_page == 'userDetails.php') echo 'class="active"'; ?>><i class="fa-solid fa-user-graduate"></i>User</a>
        </div>

        <a href="feedback_admin.php" <?php if ($current_page == 'feedback_admin.php'|| $current_page == 'feedback_graph.php') echo 'class="active"'; ?>><i class="fa-solid fa-comments"></i>Feedback</a>
        <a href="userManual_admin.php" <?php if ($current_page == 'userManual_admin.php') echo 'class="active"'; ?>><i class="fa-solid fa-circle-question"></i>User Manual</a>
    </div>

</body>

</html>