<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-3P4Sq54rgi7T5YUuP/0qrdf9wOwLQlq+Y2t5pTRyxR1irOzEy2lKpPIUatTwF9OB9w5MjJW1bey9K2dV3kA1Dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: Cambria, Georgia, serif;
            background-color: #f4f4f4;
        }

        * {
            box-sizing: border-box;
        }

        .header {
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            z-index: 1000;
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            width: calc(100% - 240px);
        }

        .right-wrapper {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .right-wrapper span {
            margin-right: 15px;
            font-weight: bold;
        }

        .right-wrapper a {
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background-color: #555;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .right-wrapper a:hover {
            color: white;
            background-color: #777;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="right-wrapper">
            <!-- Retrieve and display username from the session -->
            <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Username'; ?></span>
            <a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</body>


</html>