<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .breadcrumbs {
            margin: 70px 0 10px 260px;
            font-size: 16px;
        }

        .breadcrumbs a {
            color: #333;
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }
    </style>

</head>

<body>
    <?php
    // Get the current PHP file name
    $current_page = basename($_SERVER['PHP_SELF']);

    // Array to hold breadcrumb items and their corresponding links
    $breadcrumbs = [];

    // Add a default home breadcrumb
    $breadcrumbs[] = ['name' => 'Home', 'link' => 'index.php'];

    // Check user role from session
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    switch ($userRole) {
        case 'admin':
            // Add admin-specific breadcrumbs based on the current page
            switch ($current_page) {
                case 'index.php':
                    $breadcrumbs[] = ['name' => 'Dashboard', 'link' => 'index.php'];
                    break;

                case 'topLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Top Level', 'link' => 'topLevel.php'];
                    break;

                case 'calendarDayTopLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Top Level', 'link' => 'topLevel.php'];
                    $breadcrumbs[] = ['name' => 'Day'];
                    break;

                case 'lowerLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Lower Level', 'link' => 'lowerLevel.php'];
                    break;

                case 'calendarDayLowerLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Lower Level', 'link' => 'lowerLevel.php'];
                    $breadcrumbs[] = ['name' => 'Day'];
                    break;

                case 'setting.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Settings', 'link' => 'lsetting.php'];
                    break;

                case 'reservation.php':
                    $breadcrumbs[] = ['name' => 'Reservation', 'link' => 'reservation.php'];
                    break;

                case 'reservationDetails.php':
                    $breadcrumbs[] = ['name' => 'Reservation', 'link' => 'reservation.php'];
                    $breadcrumbs[] = ['name' => 'Reservation Details'];
                    break;

                case 'admin.php':
                    $breadcrumbs[] = ['name' => 'System User'];
                    $breadcrumbs[] = ['name' => 'Administrator', 'link' => 'admin.php'];
                    break;

                case 'adminDetails.php':
                    $breadcrumbs[] = ['name' => 'Sysgtem User'];
                    $breadcrumbs[] = ['name' => 'Administrator', 'link' => 'admin.php'];
                    $breadcrumbs[] = ['name' => 'Administrator Details'];
                    break;

                case 'newAdmin.php':
                    $breadcrumbs[] = ['name' => 'System User'];
                    $breadcrumbs[] = ['name' => 'Administrator', 'link' => 'admin.php'];
                    $breadcrumbs[] = ['name' => 'New Administrator', 'link' => 'newAdmin.php'];
                    break;

                case 'user.php':
                    $breadcrumbs[] = ['name' => 'System User'];
                    $breadcrumbs[] = ['name' => 'User', 'link' => 'user.php'];
                    break;

                case 'userDetails.php':
                    $breadcrumbs[] = ['name' => 'System User'];
                    $breadcrumbs[] = ['name' => 'User', 'link' => 'user.php'];
                    $breadcrumbs[] = ['name' => 'User Details'];
                    break;

                case 'newUser.php':
                    $breadcrumbs[] = ['name' => 'System User'];
                    $breadcrumbs[] = ['name' => 'User', 'link' => 'user.php'];
                    $breadcrumbs[] = ['name' => 'New User', 'link' => 'newUser.php'];
                    break;

                case 'feedback_admin.php':
                    $breadcrumbs[] = ['name' => 'Feedback', 'link' => 'feedback_admin.php'];
                    break;

                case 'feedback_graph.php':
                    $breadcrumbs[] = ['name' => 'Feedback', 'link' => 'feedback_admin.php'];
                    break;

                case 'userManual_admin.php':
                    $breadcrumbs[] = ['name' => 'User Manual', 'link' => 'userManual.php'];
                    break;

                default:
                    $breadcrumbs[] = ['name' => 'Dashboard', 'link' => 'index.php'];
                    break;
            }
            break;
        case 'user':
            switch ($current_page) {
                case 'index.php':
                    $breadcrumbs[] = ['name' => 'Dashboard', 'link' => 'index.php'];
                    break;

                case 'topLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Top Level', 'link' => 'topLevel.php'];
                    break;

                case 'calendarDayTopLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Top Level', 'link' => 'topLevel.php'];
                    $breadcrumbs[] = ['name' => 'Day'];
                    break;

                case 'lowerLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Lower Level', 'link' => 'lowerLevel.php'];
                    break;

                case 'calendarDayLowerLevel.php':
                    $breadcrumbs[] = ['name' => 'F2 Examination Hall'];
                    $breadcrumbs[] = ['name' => 'Lower Level', 'link' => 'lowerLevel.php'];
                    $breadcrumbs[] = ['name' => 'Day'];
                    break;

                case 'newBooking.php':
                    $breadcrumbs[] = ['name' => 'Reservation'];
                    $breadcrumbs[] = ['name' => 'New Booking', 'link' => 'newBooking.php'];
                    break;

                case 'bookingList.php':
                    $breadcrumbs[] = ['name' => 'Reservation'];
                    $breadcrumbs[] = ['name' => 'Booking List', 'link' => 'bookingList.php'];
                    break;

                case 'bookingDetails.php':
                    $breadcrumbs[] = ['name' => 'Reservation'];
                    $breadcrumbs[] = ['name' => 'Booking List', 'link' => 'bookingList.php'];
                    $breadcrumbs[] = ['name' => 'Booking Details'];
                    break;

                case 'profile.php':
                    $breadcrumbs[] = ['name' => 'Profile', 'link' => 'profile.php'];
                    break;

                case 'feedback_user.php':
                    $breadcrumbs[] = ['name' => 'Feedback', 'link' => 'feedback.php'];
                    break;

                case 'userManual_user.php':
                    $breadcrumbs[] = ['name' => 'User Manual', 'link' => 'userManual.php'];
                    break;
                    
                case 'contactUs.php':
                    $breadcrumbs[] = ['name' => 'Contact Us', 'link' => 'contactUs.php'];
                    break;

                default:
                    $breadcrumbs[] = ['name' => 'Dashboard', 'link' => 'index.php'];
                    break;
            }
            break;

            // Add other cases for different user roles if needed in the future
        default:
            // Default breadcrumbs for unknown or unauthenticated users
            $breadcrumbs[] = ['name' => 'Default Page', 'link' => 'default.php']; // Replace with actual link if you have one
            break;
    }

    // Display breadcrumbs
    echo '<div class="breadcrumbs">';
    $total_breadcrumbs = count($breadcrumbs);
    $current_index = 1;
    foreach ($breadcrumbs as $breadcrumb) {
        echo '<a href="' . (isset($breadcrumb['link']) ? $breadcrumb['link'] : '#') . '">' . $breadcrumb['name'] . '</a>';

        // Add separator if it's not the last breadcrumb
        if ($current_index < $total_breadcrumbs) {
            echo ' &raquo; ';
        }

        $current_index++;
    }

    echo '</div>';
    ?>

</body>

</html>